<?php
require dirname(__DIR__) . '/app/auth.php';
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/followups.php';
require dirname(__DIR__) . '/app/aftercare-audio.php';
$account = requireAccount(['doctor', 'clinic_staff', 'patient']);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
function audioError(int $status, string $message): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message]);
    exit;
}
if (!in_array($method, ['GET','HEAD','POST'], true)) {
    header('Allow: GET, HEAD, POST'); audioError(405, 'Method not allowed.');
}
$destination = null;
try {
    $pdo = database();
    $pdo->exec("SET time_zone = '+08:00'");
    if ($method === 'POST') {
        if ($account['role'] !== 'doctor') audioError(403, 'Only the assigned doctor can save recordings.');
        if (!validCsrf($_POST['csrf'] ?? null)) audioError(403, 'The form expired. Refresh and try again.');
        $id = filter_var($_GET['followup'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id || !visibleFollowUp($pdo, $account, $id)) audioError(404, 'Follow-up not available.');
        $pdo->beginTransaction();
        if (!visibleFollowUp($pdo,$account,$id,true)) { $pdo->rollBack(); audioError(404,'Follow-up not available.'); }
        try {
            $saved=saveAftercareEntry($pdo,$account,$id,requestText($_POST,'instruction'),is_array($_FILES['recording'] ?? null) ? $_FILES['recording'] : null,$destination);
        } catch (InvalidArgumentException $invalid) {
            $pdo->rollBack(); audioError(400,$invalid->getMessage());
        }
        $pdo->commit();$destination=null;
        header('Content-Type: application/json; charset=utf-8');http_response_code(201);
        echo json_encode(['saved'=>true,'audio_id'=>$saved['audio_id'],'entry_id'=>$saved['entry_id']]);
        exit;
    }
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if (!$id) audioError(404, 'Recording not available.');
    $q = $pdo->prepare('SELECT followup_id,storage_key,mime_type,byte_size FROM aftercare_audio WHERE audio_id=?');
    $q->execute([$id]); $audio = $q->fetch();
    if (!$audio || !visibleFollowUp($pdo,$account,(int)$audio['followup_id'])
        || !preg_match('/\A[a-f0-9]{64}\z/', $audio['storage_key'])) audioError(404,'Recording not available.');
    $file = @fopen(aftercareAudioDirectory() . '/' . $audio['storage_key'],'rb');
    if (!$file) audioError(404,'Recording not available.');
    $size = fstat($file)['size'];
    $start = 0; $end = $size - 1;
    // Single byte ranges allow seeking in the native audio player.
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        if (!preg_match('/\Abytes=(\d*)-(\d*)\z/', $range, $match) || ($match[1] === '' && $match[2] === '')) {
            fclose($file); header('Content-Range: bytes */'.$size); audioError(416,'Invalid audio range.');
        }
        if ($match[1] === '') $start = max(0,$size-(int)$match[2]);
        else { $start=(int)$match[1]; if ($match[2] !== '') $end=min($end,(int)$match[2]); }
        if ($start > $end || $start >= $size) {
            fclose($file); header('Content-Range: bytes */'.$size); audioError(416,'Invalid audio range.');
        }
        http_response_code(206);
        header("Content-Range: bytes $start-$end/$size");
    }
    session_write_close();
    header('Content-Type: '.$audio['mime_type']);
    header('Content-Disposition: inline; filename="aftercare-recording"');
    header('Accept-Ranges: bytes');
    header('Content-Length: '.($end-$start+1));
    if ($method === 'HEAD') { fclose($file); exit; }
    fseek($file,$start); $remaining=$end-$start+1;
    while ($remaining > 0 && !feof($file)) {
        $chunk=fread($file,min(65536,$remaining));
        if ($chunk === false || $chunk === '') break;
        echo $chunk; $remaining-=strlen($chunk);
    }
    fclose($file);
} catch (PDOException | RuntimeException $error) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    if ($destination && is_file($destination)) unlink($destination);
    audioError(503,'Recording is temporarily unavailable. Please try again shortly.');
}
