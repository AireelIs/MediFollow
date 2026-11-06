<?php
// Counts active forwarded reviews, not unread messages. Opening a review never clears it.
function activeReviewCount(PDO $pdo,array $account): int
{
    if(!in_array($account['role'],['patient','doctor'],true))return 0;
    $sql="SELECT COUNT(*) FROM followup_doctor_review rv JOIN follow_up f ON f.followup_id=rv.followup_id
        WHERE rv.review_status='Sent to doctor' AND rv.referred_doctor_id=f.doctor_id";
    $sql.=$account['role']==='patient'?' AND f.patient_id=?':' AND f.doctor_id=?';
    $q=$pdo->prepare($sql);$q->execute([$account['id']]);return (int)$q->fetchColumn();
}
