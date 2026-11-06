<?php
require dirname(__DIR__).'/app/auth.php';
require dirname(__DIR__).'/app/database.php';
require dirname(__DIR__).'/app/followups.php';
require dirname(__DIR__).'/app/chat.php';
$previousActivity=$_SESSION['last_active'] ?? 0;
$account=requireAccount(['patient','doctor','clinic_staff']);
$method=$_SERVER['REQUEST_METHOD'] ?? 'GET';
$action=is_string($_POST['action'] ?? null)?$_POST['action']:'';
// Background polling/read receipts must not keep an idle sign-in alive forever.
if($method==='GET' || $action==='read')$_SESSION['last_active']=$previousActivity;
function chatReply(int $status,array $data): never{http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if(!in_array($method,['GET','POST'],true)){header('Allow: GET, POST');chatReply(405,['error'=>'Method not allowed.']);}
$id=filter_var($_GET['followup'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$id)chatReply(404,['error'=>'Conversation not available.']);
try{
    $pdo=database();$pdo->exec("SET time_zone='+08:00'");
    if(!visibleChat($pdo,$account,$id))chatReply(404,['error'=>'Conversation not available.']);
    if($method==='GET'){
        foreach(['after','before'] as $field){
            if(isset($_GET[$field]) && filter_var($_GET[$field],FILTER_VALIDATE_INT,['options'=>['min_range'=>0]])===false)chatReply(400,['error'=>'Invalid message cursor.']);
        }
        $after=(int)($_GET['after'] ?? 0);$before=(int)($_GET['before'] ?? 0);
        if($after && $before)chatReply(400,['error'=>'Use one message cursor.']);
        $sql="SELECT * FROM followup_message WHERE followup_id=? AND sender_role IN ('patient','doctor')";$params=[$id];
        if($after){$sql.=' AND message_id>? ORDER BY message_id ASC LIMIT 101';$params[]=$after;}
        else{if($before){$sql.=' AND message_id<?';$params[]=$before;}$sql.=' ORDER BY message_id DESC LIMIT 51';}
        $q=$pdo->prepare($sql);$q->execute($params);$rows=$q->fetchAll();$limit=$after?100:50;$more=count($rows)>$limit;
        if($more)array_pop($rows);if(!$after)$rows=array_reverse($rows);
        chatReply(200,['messages'=>array_map(fn($r)=>chatMessage($r,$account),$rows),'has_more'=>$more]);
    }
    if(!validCsrf($_POST['csrf'] ?? null))chatReply(403,['error'=>'The form expired. Keep your draft and sign in again.']);
    if(!in_array($action,['send','read'],true))chatReply(400,['error'=>'Invalid chat action.']);
    if($action==='read'){
        $seen=filter_var($_POST['last_seen'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!$seen)chatReply(400,['error'=>'Invalid read position.']);
        $q=$pdo->prepare('SELECT message_id FROM followup_message WHERE followup_id=? AND message_id=?');$q->execute([$id,$seen]);
        if(!$q->fetch())chatReply(400,['error'=>'Invalid read position.']);
        $q=$pdo->prepare('INSERT INTO followup_chat_read (followup_id,reader_role,reader_id,last_message_id) VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE last_message_id=GREATEST(last_message_id,VALUES(last_message_id))');
        $q->execute([$id,$account['role'],$account['id'],$seen]);chatReply(200,['read'=>true]);
    }
    $body=requestText($_POST,'body');$nonce=requestText($_POST,'nonce');
    if($body==='' || mb_strlen($body)>2000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$body))chatReply(400,['error'=>'Enter a message of up to 2,000 characters.']);
    if(!preg_match('/\A[a-f0-9]{32}\z/',$nonce))chatReply(400,['error'=>'The message could not be identified. Please try again.']);
    $pdo->beginTransaction();
    if(!visibleChat($pdo,$account,$id,true)){$pdo->rollBack();chatReply(404,['error'=>'Conversation not available.']);}
    $q=$pdo->prepare('SELECT * FROM followup_message WHERE sender_role=? AND sender_id=? AND client_nonce=?');$q->execute([$account['role'],$account['id'],$nonce]);$existing=$q->fetch();
    if($existing){
        $pdo->rollBack();
        if((int)$existing['followup_id']!==$id || $existing['body']!==$body)chatReply(409,['error'=>'This message identifier was already used.']);
        chatReply(200,['message'=>chatMessage($existing,$account),'sent'=>true]);
    }
    $q=$pdo->prepare('SELECT COUNT(*) FROM followup_message WHERE followup_id=? AND sender_role=? AND sender_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 1 MINUTE)');
    $q->execute([$id,$account['role'],$account['id']]);
    if((int)$q->fetchColumn()>=30){$pdo->rollBack();header('Retry-After: 60');chatReply(429,['error'=>'Please wait a minute before sending more messages.']);}
    $q=$pdo->prepare('INSERT INTO followup_message (followup_id,sender_role,sender_id,sender_name,body,client_nonce,created_at) VALUES (?,?,?,?,?,?,NOW())');
    $q->execute([$id,$account['role'],$account['id'],$account['name'],$body,$nonce]);$messageId=(int)$pdo->lastInsertId();
    $q=$pdo->prepare('SELECT * FROM followup_message WHERE message_id=?');$q->execute([$messageId]);$message=$q->fetch();
    $pdo->commit();chatReply(201,['message'=>chatMessage($message,$account),'sent'=>true]);
}catch(PDOException | RuntimeException $e){if(isset($pdo) && $pdo->inTransaction())$pdo->rollBack();chatReply(503,['error'=>'Messages are temporarily unavailable. Your draft has not been cleared.']);}
