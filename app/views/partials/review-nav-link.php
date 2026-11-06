<?php
if(!defined('MEDIFOLLOW_DASHBOARD') && !defined('MEDIFOLLOW_ACCOUNTS') && !defined('MEDIFOLLOW_RECORD') && !defined('MEDIFOLLOW_MESSAGES')){http_response_code(404);exit;}
$activeReviews=0;
try{$activeReviews=activeReviewCount(database(),$account);}catch(PDOException $e){/* A badge failure must not hide the workspace. */}
$reviewNavActive=defined('MEDIFOLLOW_MESSAGES');
?>
<a class="nav review-nav-link<?= $reviewNavActive?' active':'' ?>" href="messages.php" <?= $reviewNavActive?'aria-current="page"':'' ?> data-review-nav data-review-role="<?= e($account['role']) ?>" aria-label="Doctor review<?= $activeReviews?', '.$activeReviews.' active':'' ?>"><span>Doctor review</span><span class="review-nav-badge" data-review-badge aria-hidden="true" <?= !$activeReviews?'hidden':'' ?>><?= $activeReviews ?></span></a>
