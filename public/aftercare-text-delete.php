<?php
require dirname(__DIR__) . '/app/auth.php';
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/followups.php';
$account=requireAccount(['doctor']);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');http_response_code(405);exit('Use the Delete text button.');
}
if (!validCsrf($_POST['csrf'] ?? null)) {http_response_code(403);exit('The form expired. Refresh and try again.');}
$id=filter_var($_POST['aftercare_id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if (!$id) {http_response_code(404);exit('Instructions not available.');}
try {
    $pdo=database();
    $q=$pdo->prepare('SELECT followup_id FROM aftercare_instruction WHERE aftercare_id=?');$q->execute([$id]);$followupId=$q->fetchColumn();
    if (!$followupId) {http_response_code(404);exit('Instructions not available.');}
    $pdo->beginTransaction();
    // Lock the owning record before checking and changing the selected entry.
    if (!visibleFollowUp($pdo,$account,(int)$followupId,true)) {
        $pdo->rollBack();http_response_code(404);exit('Instructions not available.');
    }
    $q=$pdo->prepare('SELECT followup_id,doctor_id,instruction FROM aftercare_instruction WHERE aftercare_id=? FOR UPDATE');$q->execute([$id]);$entry=$q->fetch();
    if (!$entry || (int)$entry['followup_id']!==(int)$followupId || (int)$entry['doctor_id']!==(int)$account['id'] || $entry['instruction']==='') {
        $pdo->rollBack();http_response_code(404);exit('Instructions not available.');
    }
    $q=$pdo->prepare('SELECT audio_id FROM aftercare_audio WHERE aftercare_id=? LIMIT 1');$q->execute([$id]);
    if ($q->fetch()) {
        // Keep the entry ID, timestamp and all linked recordings unchanged.
        $q=$pdo->prepare("UPDATE aftercare_instruction SET instruction='' WHERE aftercare_id=? AND doctor_id=?");
    } else {
        $q=$pdo->prepare('DELETE FROM aftercare_instruction WHERE aftercare_id=? AND doctor_id=?');
    }
    $q->execute([$id,$account['id']]);$pdo->commit();
    $_SESSION['record_flash']='Written instructions deleted.';
    header('Location: record.php?id='.(int)$followupId.'#aftercare',true,303);
    exit;
} catch (PDOException | RuntimeException $error) {
    if(isset($pdo) && $pdo->inTransaction())$pdo->rollBack();
    http_response_code(503);exit('Instructions could not be deleted. Please try again shortly.');
}
