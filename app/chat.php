<?php
// All callers must first scope the follow-up through visibleFollowUp().
function chatMessage(array $row,array $account): array
{
    return [
        'id'=>(int)$row['message_id'],'body'=>$row['body'],'name'=>$row['sender_name'],
        'role'=>accountRoles()[$row['sender_role']]['label'],
        'mine'=>$row['sender_role']===$account['role'] && (int)$row['sender_id']===(int)$account['id'],
        'datetime'=>(new DateTimeImmutable($row['created_at']))->format(DateTimeInterface::ATOM),
        'time'=>(new DateTimeImmutable($row['created_at']))->format('j M Y · g:i A'),
    ];
}
function chatUnreadCounts(PDO $pdo,array $account,array $ids): array
{
    $counts=[];
    foreach(array_chunk(array_values(array_unique(array_map('intval',$ids))),500) as $batch){
        if(!$batch)continue;
        $slots=implode(',',array_fill(0,count($batch),'?'));
        $q=$pdo->prepare('SELECT m.followup_id,COUNT(*) AS unread_count FROM followup_message m
            LEFT JOIN followup_chat_read r ON r.followup_id=m.followup_id AND r.reader_role=? AND r.reader_id=?
            WHERE m.followup_id IN ('.$slots.') AND m.message_id>COALESCE(r.last_message_id,0)
            AND m.sender_role IN (\'patient\',\'doctor\') AND NOT(m.sender_role=? AND m.sender_id=?) GROUP BY m.followup_id');
        $q->execute(array_merge([$account['role'],$account['id']],$batch,[$account['role'],$account['id']]));
        foreach($q->fetchAll() as $r)$counts[(int)$r['followup_id']]=(int)$r['unread_count'];
    }
    return $counts;
}

function doctorReview(PDO $pdo,int $id): ?array
{
    $q=$pdo->prepare('SELECT * FROM followup_doctor_review WHERE followup_id=?');
    $q->execute([$id]);return $q->fetch() ?: null;
}
function visibleChat(PDO $pdo,array $account,int $id,bool $lock=false,bool $allowResolved=false): ?array
{
    $record=visibleFollowUp($pdo,$account,$id,$lock);
    if(!$record || $account['role']==='clinic_staff')return null;
    $review=doctorReview($pdo,$id);
    if(!$review || !in_array($review['review_status'],$allowResolved?['Sent to doctor','Resolved']:['Sent to doctor'],true) || (int)$review['referred_doctor_id']!==(int)$record['doctor_id'])return null;
    if($account['role']==='doctor' && (int)$review['referred_doctor_id']!==(int)$account['id'])return null;
    return $record;
}
