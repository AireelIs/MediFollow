<?php
// Compatibility for an already-open recorder using the earlier save script.
// Its hidden "action" field shadowed form.action and produced this malformed URL.
// Forward internally only with a same-host record-page referrer; record.php still
// validates the session, CSRF token, doctor role, record ownership and upload.
$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && basename($requestPath) === '[object HTMLInputElement]'
    && ($_POST['action'] ?? null) === 'aftercare') {
    $source = parse_url($_SERVER['HTTP_REFERER'] ?? '');
    $currentHost = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    if (is_array($source) && ($source['host'] ?? '') === $currentHost
        && basename($source['path'] ?? '') === 'record.php') {
        parse_str($source['query'] ?? '', $sourceQuery);
        $sourceId = filter_var($sourceQuery['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($sourceId) {
            $_GET = ['id' => $sourceId];
            require __DIR__ . '/record.php';
            exit;
        }
    }
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'The save address is outdated. Keep a copy of your instructions before refreshing.']);
    exit;
}

// 1. Load security/database helpers and require a signed-in account.
require dirname(__DIR__) . '/app/auth.php';
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/chat.php';

$account = requireAccount(['patient', 'doctor', 'clinic_staff']);
$canUpdate = $account['role'] === 'clinic_staff';
$roleLabel = accountRoles()[$account['role']]['label'];
$csrf = csrfToken();

// 2. Read the search, page, and status-filter options from the URL.
$query = is_string($_GET['q'] ?? null)
    ? substr(trim($_GET['q']), 0, 100)
    : '';

// Follow-ups is the single landing page. Keep old History URLs compatible below.
$view = 'followups';

$filter = in_array(
    $_GET['filter'] ?? '',
    ['all', 'upcoming', 'overdue', 'completed', 'attention', 'unanswered'],
    true
) ? $_GET['filter'] : 'all';

// History links remain compatible, but completed records now live in Follow-ups.
$tab = ($_GET['tab'] ?? '') === 'completed' || ($_GET['view'] ?? '') === 'history' || $filter === 'completed'
    ? 'completed' : 'active';
if ($tab === 'completed') $filter = 'all';

// Defaults let the page show a helpful empty/error state if the database fails.
$connected = false;
$records = [];
$patients = [];
$doctors = [];
$patientCount = 0;
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$failed = false;

// 3. Connect and load the patient/doctor choices used by staff follow-up forms.
try {
    $pdo = database();
    $pdo->exec("SET time_zone = '+08:00'");
    $connected = true;

    if ($canUpdate) {
        $patients = $pdo->query(
            'SELECT patient_id, name, ic_number FROM patient ORDER BY name, patient_id'
        )->fetchAll();

        $doctors = $pdo->query(
            'SELECT doctor_id, name FROM doctor ORDER BY name, doctor_id'
        )->fetchAll();
    }
} catch (PDOException $error) {
    $connected = false;
}

// Check both the date format and whether the calendar date actually exists.
function validFollowUpDate($value): bool
{
    if (!is_string($value)) return false;

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

// 4. Handle submitted create/update forms. Only Clinic Staff can save changes.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? 'update';

    if (!$canUpdate) {
        http_response_code(403);
        $flash = 'Your account cannot change follow-up records.';
        $failed = true;
    } elseif (!validCsrf($_POST['csrf'] ?? null)) {
        http_response_code(403);
        $flash = 'The form expired. Refresh the page and try again.';
        $failed = true;
    } elseif (!$connected) {
        http_response_code(503);
        $flash = 'Your changes could not be saved. Please try again shortly.';
        $failed = true;
    } elseif (!is_string($action) || !in_array($action, ['create', 'update'], true)) {
        http_response_code(400);
        $flash = 'Invalid follow-up action.';
        $failed = true;
    } else {
        $date = $_POST['date'] ?? null;
        $time = $_POST['time'] ?? null;
        // New follow-ups are always pending, even if a client submits a completion flag.
        $completed = $action === 'create' ? '0' : ($_POST['completed'] ?? null);
        $purpose = is_string($_POST['purpose'] ?? null)
            ? trim($_POST['purpose'])
            : '';

        // Validate the date, 24-hour time, completion choice, and purpose.
        if (
            !validFollowUpDate($date)
            || !is_string($time)
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time)
            || !is_string($completed)
            || !in_array($completed, ['0', '1'], true)
            || $purpose === ''
            || strlen($purpose) > 255
        ) {
            http_response_code(400);
            $flash = $action === 'create'
                ? 'Enter a valid date, time and purpose (up to 255 bytes).'
                : 'Enter a valid date, time, purpose (up to 255 bytes) and follow-up progress.';
            $failed = true;
        } else {
            try {
                $status = $completed === '1'
                    ? 'Completed'
                    : ($date < date('Y-m-d') ? 'Overdue' : 'Upcoming');

                if ($action === 'create') {
                    // A new follow-up must belong to existing patient/doctor accounts.
                    $patientId = filter_var(
                        $_POST['patient_id'] ?? null,
                        FILTER_VALIDATE_INT,
                        ['options' => ['min_range' => 1]]
                    );
                    $doctorId = filter_var(
                        $_POST['doctor_id'] ?? null,
                        FILTER_VALIDATE_INT,
                        ['options' => ['min_range' => 1]]
                    );

                    if (
                        !$patientId
                        || !$doctorId
                        || !in_array($patientId, array_map('intval', array_column($patients, 'patient_id')), true)
                        || !in_array($doctorId, array_map('intval', array_column($doctors, 'doctor_id')), true)
                    ) {
                        http_response_code(400);
                        $flash = 'Choose an existing patient and doctor.';
                        $failed = true;
                    } else {
                        $write = $pdo->prepare(
                            'INSERT INTO follow_up (patient_id,doctor_id,followup_date,followup_time,purpose,status) VALUES (?,?,?,?,?,?)'
                        );
                        $write->execute([$patientId, $doctorId, $date, $time, $purpose, $status]);
                        $_SESSION['flash'] = 'Follow-up created.';
                    }
                } else {
                    // Check that the follow-up still exists before updating it.
                    $id = filter_var(
                        $_POST['id'] ?? null,
                        FILTER_VALIDATE_INT,
                        ['options' => ['min_range' => 1]]
                    );
                    $check = $pdo->prepare('SELECT followup_id FROM follow_up WHERE followup_id = ?');
                    $check->execute([$id ?: 0]);

                    if (!$id || !$check->fetch()) {
                        http_response_code(404);
                        $flash = 'That follow-up record no longer exists.';
                        $failed = true;
                    } else {
                        $write = $pdo->prepare(
                            'UPDATE follow_up SET followup_date=?,followup_time=?,purpose=?,status=? WHERE followup_id=?'
                        );
                        $write->execute([$date, $time, $purpose, $status, $id]);
                        $_SESSION['flash'] = 'Follow-up updated.';
                    }
                }

                // Redirect after saving so refresh does not submit the form again.
                if (!$failed) {
                    header('Location: index.php?view=followups', true, 303);
                    exit;
                }
            } catch (PDOException $error) {
                http_response_code(503);
                $flash = 'Your changes could not be saved. Please refresh and try again.';
                $failed = true;
            }
        }
    }
}

// 5. Retrieve only the records the current role is allowed to see.
if ($connected) {
    try {
        $scope = '';
        $parameters = [];

        if ($account['role'] === 'patient') {
            $scope = ' WHERE f.patient_id = ?';
            $parameters[] = $account['id'];
        }
        if ($account['role'] === 'doctor') {
            $scope = ' WHERE f.doctor_id = ?';
            $parameters[] = $account['id'];
        }

        // Include the latest patient condition, latest aftercare note, and open doctor review requests.
        $statement = $pdo->prepare('SELECT f.followup_id AS id, p.patient_id,
            p.name AS full_name, p.ic_number,
            f.followup_date AS follow_up_date, f.followup_time, f.purpose AS reason, f.status, d.name AS doctor_name,
            (SELECT ps.`condition` FROM patient_status_update ps WHERE ps.followup_id=f.followup_id ORDER BY ps.submitted_at DESC,ps.update_id DESC LIMIT 1) AS patient_condition,
            (SELECT a.instruction FROM aftercare_instruction a WHERE a.followup_id=f.followup_id ORDER BY a.created_at DESC,a.aftercare_id DESC LIMIT 1) AS instruction,
            EXISTS(SELECT 1 FROM followup_doctor_review rv WHERE rv.followup_id=f.followup_id
                AND rv.review_status IN ("Pending","Sent to doctor")) AS doctor_review_requested
            FROM follow_up f JOIN patient p ON p.patient_id=f.patient_id JOIN doctor d ON d.doctor_id=f.doctor_id' . $scope . '
            ORDER BY f.followup_date,f.followup_time,f.followup_id');

        $statement->execute($parameters);
        $records = $statement->fetchAll();
        $patientCount = $canUpdate
            ? count($patients)
            : count(array_unique(array_column($records, 'patient_id')));
    } catch (PDOException $error) {
        $connected = false;
        $records = [];
        $patientCount = 0;
    }
}

// 6. Helpers for identifying attention requests, applying filters, and showing dates.
function needsAttention(array $r): bool
{
    return (bool) $r['doctor_review_requested'];
}

function matchFilter(array $r, string $filter): bool
{
    if ($filter === 'all') return true;
    if ($filter === 'attention') return needsAttention($r);
    if ($filter === 'unanswered') return !$r['patient_condition'] && followUpStatus($r) !== 'Completed';

    return strtolower(followUpStatus($r)) === $filter;
}

function prettyDate(string $date): string
{
    return (new DateTimeImmutable($date))->format('j M Y');
}

// 7. Prepare summary totals, table records, attention requests, and the page title.
$totals = ['Upcoming' => 0, 'Completed' => 0, 'Overdue' => 0];
foreach ($records as $record) $totals[followUpStatus($record)]++;

$baseRecords = array_values(array_filter(
    $records,
    fn($r) => ($tab === 'completed') === (followUpStatus($r) === 'Completed')
));

// Server-side filtering works without JavaScript; JavaScript adds live filtering.
$visible = array_values(array_filter(
    $baseRecords,
    fn($r) => matchFilter($r, $filter) && (
        $query === ''
        || stripos(implode(' ', [
            $r['full_name'],
            $r['ic_number'] ?? '',
            $r['reason'],
            $r['doctor_name'],
            $r['follow_up_date'],
            prettyDate($r['follow_up_date']),
            (new DateTimeImmutable($r['follow_up_date']))->format('j/n'),
            (new DateTimeImmutable($r['follow_up_date']))->format('d/m'),
            $r['followup_time'],
            $r['patient_condition'] ?? '',
            followUpStatus($r)
        ]), $query) !== false
    )
));

$title = $account['role'] === 'patient' ? 'My follow-ups' : 'Follow-ups';

// Chat failure must not hide the clinic's follow-up records.
$unreadChats=[];
if ($connected) {
    try {$unreadChats=chatUnreadCounts($pdo,$account,array_column($records,'id'));}
    catch(PDOException $chatError) {$unreadChats=[];}
}

// 8. Render the interface separately from the request/database logic above.
define('MEDIFOLLOW_DASHBOARD', true);
require dirname(__DIR__) . '/app/views/dashboard.php';
