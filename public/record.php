<?php
// Full follow-up record: doctor aftercare, patient updates, and doctor review requests.
require dirname(__DIR__) . '/app/auth.php';
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/followups.php';
require dirname(__DIR__) . '/app/aftercare-audio.php';
require dirname(__DIR__) . '/app/chat.php';

$account = requireAccount(['patient', 'doctor', 'clinic_staff']);
$roleLabel = accountRoles()[$account['role']]['label'];
$csrf = csrfToken();
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$error = '';
$flash = $_SESSION['record_flash'] ?? '';
unset($_SESSION['record_flash']);
$record = null;
$history = ['aftercare' => [], 'updates' => [], 'requests' => []];
$instruction = requestText($_POST, 'instruction');
$condition = requestText($_POST, 'condition');
$details = requestText($_POST, 'details');
// Accept the old field from already-open forms during this UI update.
$wantDoctorReview = ($_POST['doctor_review'] ?? $_POST['contact'] ?? '') === '1';
$unavailable = false;
$audioRecordings = [];
$aftercareEntries = [];
$createdAudioFile = null;
$chatUnread=0;
$jsonRequest = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

try {
    $pdo = database();
    $pdo->exec("SET time_zone = '+08:00'");
    $record = $id ? visibleFollowUp($pdo, $account, $id) : null;
    if (!$record) {
        http_response_code(404);
        $error = 'That follow-up is not available in your workspace.';
    } elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = requestText($_POST, 'action');
        $allowed = ($action === 'aftercare' && $account['role'] === 'doctor')
            || ($action === 'condition' && $account['role'] === 'patient');

        if (!$allowed) {
            http_response_code(403);
            $error = 'Your account cannot perform this action.';
        } elseif (!validCsrf($_POST['csrf'] ?? null)) {
            http_response_code(403);
            $error = 'The form expired. Refresh the page and try again.';
        } else {
            // Lock and check ownership again inside the transaction before any write.
            $pdo->beginTransaction();
            $locked = visibleFollowUp($pdo, $account, $id, true);
            if (!$locked) {
                http_response_code(404);
                $error = 'That follow-up is no longer available.';
            } elseif ($action === 'aftercare') {
                try {
                    saveAftercareEntry($pdo,$account,$id,$instruction,is_array($_FILES['recording'] ?? null) ? $_FILES['recording'] : null,$createdAudioFile);
                    $flash = 'Aftercare saved.';
                } catch (InvalidArgumentException $invalid) {
                    http_response_code(400);
                    $error = $invalid->getMessage();
                }
            } elseif ($action === 'condition') {
                if ($locked['status'] === 'Completed') {
                    http_response_code(409);
                    $error = 'This follow-up is completed. You can view its history, but cannot add a condition update.';
                } elseif (!in_array($condition, patientConditionChoices(), true) || mb_strlen($details) > 2000) {
                    http_response_code(400);
                    $error = 'Choose a condition and keep additional details within 2,000 characters.';
                } else {
                    $write = $pdo->prepare('INSERT INTO patient_status_update
                        (followup_id,`condition`,details,submitted_at) VALUES (?,?,?,NOW())');
                    $write->execute([$id,$condition,$details]);
                    if ($wantDoctorReview) {
                        $review = doctorReview($pdo, $id);
                        if (!$review || $review['review_status'] === 'Resolved') {
                            $note = $details !== '' ? $details : 'Condition: ' . $condition;
                            $write = $pdo->prepare("INSERT INTO followup_doctor_review (followup_id,patient_note,requested_at) VALUES (?,?,NOW())
                                ON DUPLICATE KEY UPDATE review_status='Pending',patient_note=VALUES(patient_note),requested_at=NOW(),
                                referred_doctor_id=NULL,referred_by=NULL,referred_at=NULL,resolved_at=NULL");
                            $write->execute([$id,$note]);
                        }
                        // An existing pending/referred review stays with its current handler.
                        $flash = 'Condition update saved. Your doctor review request is with the clinic.';
                    } else {
                        $flash = 'Condition update saved.';
                    }
                }
            }
            if ($error === '') {
                $pdo->commit();
                $createdAudioFile = null;
                if ($jsonRequest) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['saved'=>true]);
                    exit;
                }
                $_SESSION['record_flash'] = $flash;
                header('Location: record.php?id=' . $id, true, 303);
                exit;
            }
            $pdo->rollBack();
        }
    }
    if ($record) {
        $history = recordHistories($pdo, $id);
        $audioRecordings = aftercareAudioList($pdo, $id);
        $aftercareEntries = groupedAftercareEntries($history['aftercare'],$audioRecordings);
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $instruction = $history['aftercare'][0]['instruction'] ?? '';
        }
    }
} catch (PDOException | RuntimeException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    if ($createdAudioFile && is_file($createdAudioFile)) @unlink($createdAudioFile);
    http_response_code(503);
    $unavailable = true;
    $record = null;
    $error = 'The follow-up is temporarily unavailable. Please try again shortly.';
}

if ($jsonRequest) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error'=>$error ?: 'Aftercare could not be saved.']);
    exit;
}
if ($record) {
    try {$chatUnread=chatUnreadCounts($pdo,$account,[$id])[$id] ?? 0;}
    catch(PDOException $chatError) {$chatUnread=0;}
}
define('MEDIFOLLOW_RECORD', true);
require dirname(__DIR__) . '/app/views/record.php';
