<?php
require dirname(__DIR__).'/app/auth.php';
require dirname(__DIR__).'/app/database.php';
require dirname(__DIR__).'/app/followups.php';
require dirname(__DIR__).'/app/chat.php';
$account=requireAccount(['patient','doctor','clinic_staff']);
$csrf=csrfToken();$threads=[];$record=null;$error='';$unread=[];
$review=null;$notice=$_SESSION['message_notice'] ?? '';unset($_SESSION['message_notice']);
$reviewTab=in_array($_GET['tab'] ?? '',['new','all','records'],true)?$_GET['tab']:'new';
$tabCounts=['new'=>0,'all'=>0,'records'=>0];
$requested=filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
try {
    $pdo=database();
    $pdo->exec("SET time_zone='+08:00'");
    if(($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST'){
        $action=requestText($_POST,'action');
        $target=$requested ? visibleFollowUp($pdo,$account,$requested) : null;
        if(!$target){http_response_code(404);$error='Review not available.';}
        elseif(!validCsrf($_POST['csrf'] ?? null)){http_response_code(403);$error='The form expired. Refresh and try again.';}
        elseif(!(($action==='request_review' && $account['role']==='patient') || ($action==='refer_review' && $account['role']==='clinic_staff') || ($action==='resolve_review' && $account['role']==='doctor'))){http_response_code(403);$error='Your account cannot perform this action.';}
        else{
            $note=requestText($_POST,'note');
            if(mb_strlen($note)>2000){http_response_code(400);$error='Use up to 2,000 characters.';}
            else{
                $pdo->beginTransaction();$target=visibleFollowUp($pdo,$account,$requested,true);
                if(!$target){$pdo->rollBack();http_response_code(404);exit('Review not available.');}
                $reviewEvent='';
                $current=doctorReview($pdo,$requested);
                if($action==='request_review' && $target['status']==='Completed'){
                    http_response_code(409);$error='This follow-up is completed. Open an active follow-up to request a review.';
                }elseif($action==='request_review'){
                    if($current && $current['review_status']!=='Resolved'){$notice='Your request is already being handled.';}
                    else{
                        $q=$pdo->prepare("INSERT INTO followup_doctor_review (followup_id,patient_note,requested_at) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE review_status='Pending',patient_note=VALUES(patient_note),requested_at=NOW(),referred_doctor_id=NULL,referred_by=NULL,referred_at=NULL,resolved_at=NULL");
                        $q->execute([$requested,$note]);$reviewEvent='Doctor review requested.'.($note!==''?"\n".$note:'');$notice='Doctor review requested. Staff will review your request.';
                    }
                }elseif($action==='refer_review' && $current && $current['review_status']==='Pending'){
                    // The doctor always comes from the follow-up, never a submitted doctor ID.
                    $q=$pdo->prepare("UPDATE followup_doctor_review SET review_status='Sent to doctor',referred_doctor_id=?,referred_by=?,referred_at=NOW() WHERE followup_id=?");
                    $q->execute([$target['doctor_id'],$account['id'],$requested]);$reviewEvent='Request sent to Dr '.$target['doctor_name'].'.';$notice='Request sent to the assigned doctor.';
                }elseif($action==='resolve_review' && $current && $current['review_status']==='Sent to doctor' && (int)$current['referred_doctor_id']===(int)$account['id']){
                    $q=$pdo->prepare("UPDATE followup_doctor_review SET review_status='Resolved',resolved_at=NOW() WHERE followup_id=?");$q->execute([$requested]);$reviewEvent='Doctor review resolved.';$notice='Doctor review resolved.';
                }else{http_response_code(409);$error='The request has changed. Refresh and try again.';}
                if($error){$pdo->rollBack();}else{
                    $pdo->commit();$_SESSION['message_notice']=$notice;header('Location: messages.php?id='.$requested,true,303);exit;}
            }
        }
    }
    $sql='SELECT f.status AS followup_status,rv.review_status,f.followup_id AS id,f.purpose,f.followup_date,p.name AS patient_name,p.ic_number,d.name AS doctor_name,
        NULL AS preview,rv.requested_at AS latest
        FROM follow_up f LEFT JOIN followup_doctor_review rv ON rv.followup_id=f.followup_id JOIN patient p ON p.patient_id=f.patient_id JOIN doctor d ON d.doctor_id=f.doctor_id';
    $params=[];
    if($account['role']==='clinic_staff')$sql.=' WHERE rv.followup_id IS NOT NULL';
    if($account['role']==='patient'){$sql.=' WHERE f.patient_id=?';$params[]=$account['id'];}
    elseif($account['role']==='doctor'){$sql.=" WHERE f.doctor_id=? AND rv.referred_doctor_id=? AND rv.review_status IN ('Sent to doctor','Resolved')";$params[]= $account['id'];$params[]=$account['id'];}
    $sql.=" ORDER BY CASE WHEN rv.review_status='Pending' THEN 0 WHEN rv.review_status='Sent to doctor' THEN 1 ELSE 2 END,latest DESC,f.followup_date DESC,f.followup_id DESC";
    $q=$pdo->prepare($sql);$q->execute($params);$threads=$q->fetchAll();
    if($account['role']!=='clinic_staff')$unread=chatUnreadCounts($pdo,$account,array_column(array_filter($threads,fn($thread)=>$thread['review_status']==='Sent to doctor'),'id'));
    $allThreads=$threads;
    $isNew=fn($thread)=>in_array($thread['review_status'],['Pending','Sent to doctor'],true);
    $isRecord=fn($thread)=>!$isNew($thread) && ($thread['review_status']==='Resolved' || $thread['followup_status']==='Completed');
    $tabCounts=['new'=>count(array_filter($threads,$isNew)),'all'=>count($threads),'records'=>count(array_filter($threads,$isRecord))];
    if($requested){
        $selected=null;foreach($allThreads as $thread){if((int)$thread['id']===$requested){$selected=$thread;break;}}
        if($selected && ($reviewTab==='new' && !$isNew($selected) || $reviewTab==='records' && !$isRecord($selected))){
            $reviewTab=$isNew($selected)?'new':($isRecord($selected)?'records':'all');
        }
    }
    $threads=array_values(array_filter($allThreads,fn($thread)=>$reviewTab==='all' || ($reviewTab==='new'?$isNew($thread):$isRecord($thread))));
    $id=$requested ?: (int)($threads[0]['id'] ?? 0);
    if($id){
        $scopedIds=array_map('intval',array_column($allThreads,'id'));
        $record=in_array($id,$scopedIds,true)?($account['role']==='doctor'?visibleChat($pdo,$account,$id,false,true):visibleFollowUp($pdo,$account,$id)):null;
        if(!$record){http_response_code(404);$error='Review not available.';}
    }
    $chatEnabled=false;
    if($record){$review=doctorReview($pdo,$id);$chatEnabled=(bool)visibleChat($pdo,$account,$id);}
} catch(PDOException $e){if(isset($pdo) && $pdo->inTransaction())$pdo->rollBack();http_response_code(503);$error='Doctor reviews are temporarily unavailable.';}
define('MEDIFOLLOW_MESSAGES',true);
require dirname(__DIR__).'/app/views/messages.php';
