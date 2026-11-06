<?php
require dirname(__DIR__) . '/app/auth.php';
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/followups.php';
require dirname(__DIR__) . '/app/aftercare-audio.php';
$account = requireAccount(['doctor']);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST'); http_response_code(405); exit('Use the recording delete button.');
}
if (!validCsrf($_POST['csrf'] ?? null)) { http_response_code(403); exit('The form expired. Refresh and try again.'); }
$id=filter_var($_POST['audio_id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if (!$id) { http_response_code(404); exit('Recording not available.'); }
$original = $quarantine = null;
$committed = false;
try {
    $pdo=database(); $pdo->beginTransaction();
    $q=$pdo->prepare('SELECT aftercare_id,followup_id,doctor_id,storage_key FROM aftercare_audio WHERE audio_id=? FOR UPDATE');
    $q->execute([$id]); $audio=$q->fetch();
    if (!$audio || (int)$audio['doctor_id'] !== (int)$account['id']
        || !visibleFollowUp($pdo,$account,(int)$audio['followup_id'],true)
        || !preg_match('/\A[a-f0-9]{64}\z/',$audio['storage_key'])) {
        $pdo->rollBack(); http_response_code(404); exit('Recording not available.');
    }
    $original=aftercareAudioDirectory().'/'.$audio['storage_key'];
    // Move aside first so a failed database write can restore the original file.
    if (is_file($original)) {
        $quarantine=dirname($original).'/.delete-'.bin2hex(random_bytes(32));
        if (!@rename($original,$quarantine)) throw new RuntimeException('Unable to remove recording.');
    }
    $q=$pdo->prepare('DELETE FROM aftercare_audio WHERE audio_id=? AND doctor_id=?');
    $q->execute([$id,$account['id']]);
    // Remove an empty audio-only shell, but keep written instructions intact.
    if ($audio['aftercare_id']) {
        $q=$pdo->prepare("DELETE FROM aftercare_instruction WHERE aftercare_id=? AND doctor_id=? AND instruction='' AND NOT EXISTS (SELECT 1 FROM aftercare_audio WHERE aftercare_id=?)");
        $q->execute([$audio['aftercare_id'],$account['id'],$audio['aftercare_id']]);
    }
    $pdo->commit(); $committed=true;
    if ($quarantine && !@unlink($quarantine)) {
        // The file is already inaccessible via playback; report private cleanup failure.
        error_log('MediFollow: private audio deletion cleanup requires attention.');
        $_SESSION['record_flash']='Recording removed from the record; storage cleanup requires administrator attention.';
    } else {
        $_SESSION['record_flash']='Recording deleted.';
    }
    header('Location: record.php?id='.(int)$audio['followup_id'].'#aftercare',true,303);
    exit;
} catch (PDOException | RuntimeException $error) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    if (!$committed && $quarantine && is_file($quarantine)) {
        if (!@rename($quarantine,$original)) error_log('MediFollow: audio deletion rollback requires administrator attention.');
    }
    http_response_code(503);
    exit('Recording could not be deleted. Please refresh the record and try again.');
}
