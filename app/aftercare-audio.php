<?php
// Recordings stay outside the web root; audio.php checks ownership on every request.
function aftercareAudioDirectory(): string
{
    $configured = getenv('MEDIFOLLOW_AUDIO_DIR');
    if (getenv('MEDIFOLLOW_ENV') === 'production' && !$configured) {
        throw new RuntimeException('Private recording storage is not configured.');
    }
    if ($configured) {
        $directory = realpath($configured);
        $webRoot = realpath(dirname(__DIR__) . '/public');
        if (!$directory || !is_dir($directory) || !is_writable($directory)
            || $directory === $webRoot || str_starts_with($directory, $webRoot . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Recording storage must be private and writable.');
        }
        return $directory;
    }
    $config = require dirname(__DIR__) . '/config/database.php';
    return (getenv('HOME') ?: sys_get_temp_dir())
        . '/Documents/Codex/MediFollow-data/audio-' . substr(hash('sha256', $config['name']), 0, 16);
}
function aftercareAudioList(PDO $pdo, int $followupId): array
{
    $q = $pdo->prepare('SELECT a.audio_id,a.aftercare_id,a.doctor_id,a.created_at,d.name AS doctor_name
        FROM aftercare_audio a JOIN doctor d ON d.doctor_id=a.doctor_id
        WHERE a.followup_id=? ORDER BY a.created_at DESC,a.audio_id DESC');
    $q->execute([$followupId]);
    return $q->fetchAll();
}
function aftercareAudioMime(string $file): ?string
{
    // Detect content on the server, never trust the submitted filename/type.
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
    return match ($mime) {
        'audio/webm', 'video/webm' => 'audio/webm',
        'audio/mp4', 'video/mp4', 'audio/x-m4a' => 'audio/mp4',
        'audio/ogg', 'application/ogg' => 'audio/ogg',
        'audio/wav', 'audio/x-wav', 'audio/vnd.wave' => 'audio/wav',
        default => null,
    };
}

// Caller owns the transaction and removes $createdFile if it rolls back.
function saveAftercareEntry(PDO $pdo, array $account, int $followupId, string $text, ?array $upload, ?string &$createdFile): array
{
    $hasAudio=$upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if (mb_strlen($text)>10000 || ($text==='' && !$hasAudio)) {
        throw new InvalidArgumentException('Add written instructions, a recording, or both (text up to 10,000 characters).');
    }
    $mime=null;$size=0;
    if($hasAudio) {
        if(($upload['error'] ?? null)!==UPLOAD_ERR_OK || !is_string($upload['tmp_name'] ?? null)
            || !is_uploaded_file($upload['tmp_name'])) throw new InvalidArgumentException('Use a recording of up to 2 MB.');
        $size=filesize($upload['tmp_name']);
        if($size<44 || $size>2*1024*1024) throw new InvalidArgumentException('Use a recording of up to 2 MB.');
        $mime=aftercareAudioMime($upload['tmp_name']);
        if(!$mime) throw new InvalidArgumentException('Unsupported recording format. Please record again.');
    }
    // One timestamp is stored on both parts of this entry.
    $timestamp=date('Y-m-d H:i:s');
    $q=$pdo->prepare('INSERT INTO aftercare_instruction (followup_id,doctor_id,instruction,created_at) VALUES (?,?,?,?)');
    $q->execute([$followupId,$account['id'],$text,$timestamp]);$entryId=(int)$pdo->lastInsertId();
    $audioId=null;
    if($hasAudio) {
        $directory=aftercareAudioDirectory();
        if(!is_dir($directory) && !@mkdir($directory,0700,true) && !is_dir($directory)) throw new RuntimeException('Recording storage unavailable.');
        $key=bin2hex(random_bytes(32));$path=$directory.'/'.$key;
        if(!move_uploaded_file($upload['tmp_name'],$path)) throw new RuntimeException('Recording could not be saved.');
        $createdFile=$path;
        if(!chmod($path,0600)) throw new RuntimeException('Recording could not be protected.');
        $q=$pdo->prepare('INSERT INTO aftercare_audio (aftercare_id,followup_id,doctor_id,storage_key,mime_type,byte_size,created_at) VALUES (?,?,?,?,?,?,?)');
        $q->execute([$entryId,$followupId,$account['id'],$key,$mime,$size,$timestamp]);$audioId=(int)$pdo->lastInsertId();
    }
    return ['entry_id'=>$entryId,'audio_id'=>$audioId];
}
function groupedAftercareEntries(array $written, array $recordings): array
{
    $byEntry=[];
    foreach($recordings as $audio) $byEntry[(int)$audio['aftercare_id']][]=$audio;
    foreach($written as &$entry) $entry['recordings']=$byEntry[(int)$entry['aftercare_id']] ?? [];
    unset($entry);
    return $written;
}
