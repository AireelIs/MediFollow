<?php if(!defined('MEDIFOLLOW_MESSAGES')){http_response_code(404);exit;} ?>
<!doctype html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>MediFollow | Doctor review</title>
<link rel="stylesheet" href="assets/css/clinic-dashboard.css"><link rel="stylesheet" href="assets/css/records.css">
<link rel="stylesheet" href="assets/css/theme.css"><link rel="stylesheet" href="assets/css/messages.css?v=6">
<script src="assets/js/review-request.js" defer></script><script src="assets/js/theme.js"></script><script src="assets/js/account-menu.js" defer></script>
<script src="assets/js/sidebar-highlight.js" defer></script><script src="assets/js/followup-chat.js?v=3" defer></script>
<?php require __DIR__.'/partials/mobile-app.php'; ?>
</head><body>
<aside class="sidebar">
<div class="brand"><span class="logo" aria-hidden="true">+</span>MediFollow<span style="color:#2463db">.</span></div>
<div class="sub">PATIENT FOLLOW-UP &amp; AFTERCARE</div>
<nav aria-label="Main navigation">
<?php if($account['role']!=='patient'): ?><a class="nav" href="accounts.php"><?= $account['role']==='doctor'?'Staff accounts':'Patients' ?></a><?php endif; ?>
<a class="nav" href="index.php?view=followups"><?= $account['role']==='patient'?'My follow-ups':'Follow-ups' ?></a>
<?php require __DIR__.'/partials/review-nav-link.php'; ?>
</nav></aside>
<main class="main messages-page">
<header class="workspace-header"><?php require __DIR__.'/partials/account-menu.php'; ?></header>
<section class="welcome"><h1>Doctor review</h1></section>
<?php if($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if($notice && !$error): ?><p class="notice" role="status"><?= e($notice) ?></p><?php endif; ?>
<nav class="review-tabs" aria-label="Review categories">
<?php foreach(['new'=>'New reviews','all'=>'All','records'=>'Records'] as $tabValue=>$tabLabel): ?>
<a class="<?= $reviewTab===$tabValue?'active':'' ?>" href="messages.php?tab=<?= $tabValue ?>" <?= $reviewTab===$tabValue?'aria-current="page"':'' ?>><?= $tabLabel ?><span><?= $tabCounts[$tabValue] ?></span></a>
<?php endforeach; ?>
</nav>
<div class="message-workspace">
<aside class="conversation-list" aria-label="Follow-ups for review">
<div class="conversation-list-heading"><?= ['new'=>'New reviews','all'=>'All follow-ups','records'=>'Records'][$reviewTab] ?></div>
<?php foreach($threads as $thread): ?>
<a class="conversation-item <?= $record && (int)$thread['id']===$id?'selected':'' ?>" href="messages.php?tab=<?= e($reviewTab) ?>&amp;id=<?= (int)$thread['id'] ?>" <?= $record && (int)$thread['id']===$id?'aria-current="true"':'' ?>>
<div class="conversation-title"><strong><?= e($account['role']==='patient'?$thread['purpose']:$thread['patient_name']) ?></strong><?php if($unread[$thread['id']] ?? 0): ?><span class="chat-unread"><?= (int)$unread[$thread['id']] ?></span><?php endif; ?></div>
<?php if($account['role']!=='patient'): ?><span class="patient-ic muted"><?= e(patientIcLabel($thread['ic_number'])) ?></span><?php endif; ?><span class="conversation-purpose"><?= $account['role']==='patient'?'Follow-up':e($thread['purpose']) ?> · <?= e((new DateTimeImmutable($thread['followup_date']))->format('j M Y')) ?></span>
<?php if($thread['review_status']): ?><span class="review-list-status"><?= e($thread['review_status']) ?></span><?php endif; ?>
<span class="conversation-preview"><?= $thread['review_status']?'View request':'No review requested' ?></span>
</a>
<?php endforeach; ?>
<?php if(!$threads): ?><p class="message-placeholder"><?= $reviewTab==='new'?'No new reviews.':($reviewTab==='records'?'No records yet.':'No follow-ups yet.') ?></p><?php endif; ?>
</aside>
<section class="conversation-pane followup-chat<?= $account['role']==='clinic_staff'?' staff-review-pane':'' ?>" aria-label="Selected conversation">
<?php if($record): ?>
<header class="conversation-header"><div><h2><?= e($account['role']==='patient'?$record['reason']:$record['full_name']) ?></h2><?php if($account['role']!=='patient'): ?><span class="patient-ic muted"><?= e(patientIcLabel($record['ic_number'])) ?></span><?php endif; ?><p><?= $account['role']==='patient'?'Follow-up':e($record['reason']) ?> · <?= e((new DateTimeImmutable($record['follow_up_date']))->format('j M Y')) ?></p></div><a class="conversation-record" href="record.php?id=<?= $id ?>">View record</a></header>
<section class="doctor-review" aria-label="Doctor review">
<?php if($review): ?>
<div class="review-status-row"><strong>Doctor review</strong><span class="pill"><?= e($review['review_status']) ?></span></div>
<?php if($account['role']==='clinic_staff'): ?>
<div class="staff-review-reason"><span>Reason for review</span><p><?= $review['patient_note']!==''?nl2br(e($review['patient_note'])):'No additional reason provided.' ?></p></div>
<dl class="staff-review-facts"><div><dt>Assigned doctor</dt><dd>Dr <?= e($record['doctor_name']) ?></dd></div><div><dt>Requested</dt><dd><?= e((new DateTimeImmutable($review['requested_at']))->format('j M Y · g:i A')) ?></dd></div></dl>
<?php elseif($review['patient_note']!==''): ?><p class="review-note"><?= nl2br(e($review['patient_note'])) ?></p><?php endif; ?>
<?php if($review['review_status']==='Sent to doctor'): ?><p class="review-caption">Sent to Dr <?= e($record['doctor_name']) ?>.</p><?php endif; ?>
<?php elseif($record['status']==='Completed'): ?><p class="review-caption">No doctor review was requested for this follow-up.</p>
<?php endif; ?>
<?php if($account['role']==='patient' && $record['status']!=='Completed' && (!$review || $review['review_status']==='Resolved')): ?>
<details class="review-request"><summary>Request doctor review</summary>
<form action="messages.php?id=<?= $id ?>" method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="request_review">
<div class="review-field-heading"><label for="review-note">Reason for review</label><span>Optional</span></div><textarea id="review-note" name="note" maxlength="2000" rows="3" placeholder="Briefly describe how you’re feeling…"></textarea><div class="review-form-actions"><button class="review-cancel" type="button" data-review-cancel hidden>Cancel</button><button class="primary" type="submit">Send request</button></div></form></details>
<?php elseif($account['role']==='patient' && $review && $review['review_status']==='Pending'): ?><p class="review-caption">Staff will review your request and send it to your assigned doctor.</p>
<?php elseif($account['role']==='clinic_staff' && $review && $review['review_status']==='Pending'): ?>
<form class="review-action" action="messages.php?id=<?= $id ?>" method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="refer_review">
<button class="primary" type="submit">Send to doctor</button></form>
<?php elseif($account['role']==='doctor' && $review && $review['review_status']==='Sent to doctor' && (int)$review['referred_doctor_id']===(int)$account['id']): ?>
<form class="review-action" action="messages.php?id=<?= $id ?>" method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="resolve_review"><button class="primary" type="submit">Complete review</button></form>
<?php elseif($review && $review['review_status']==='Resolved'): ?><p class="review-caption">Review completed · <?= e((new DateTimeImmutable($review['resolved_at']))->format('j M Y · g:i A')) ?></p>
<?php endif; ?>
</section>
<?php if($chatEnabled): ?><h3 class="doctor-chat-heading">Chat with <?= $account['role']==='patient'?'Dr '.e($record['doctor_name']):'patient' ?></h3>
<details data-chat open data-csrf="<?= e($csrf) ?>"><summary class="chat-sr-only">Conversation</summary>
<div class="chat-content">
<span data-chat-unread hidden></span><button class="chat-earlier" data-chat-earlier type="button" hidden>Earlier messages</button>
<div class="chat-messages" data-chat-log role="log" aria-label="Conversation messages" aria-live="polite" aria-relevant="additions" tabindex="0"><p class="chat-empty" data-chat-empty>No messages yet.</p></div>
<form action="chat.php?followup=<?= $id ?>" method="post" data-chat-form hidden><label class="chat-sr-only" for="chat-message">Message</label><textarea id="chat-message" name="body" rows="2" maxlength="2000" placeholder="Write a message…" required></textarea><button class="primary" type="submit">Send</button></form>
<p class="chat-status" data-chat-status role="status"></p><p class="chat-hint">Replies may not be immediate.</p><noscript>Enable JavaScript to use messages.</noscript></div></details>
<?php elseif($account['role']==='patient' && $record['status']!=='Completed'): ?><p class="message-placeholder"><?= $review && $review['review_status']==='Resolved'?'This review is complete. Submit a new request if you need another doctor review.':'Chat with your doctor will appear after staff forwards your request.' ?></p><?php endif; ?>
<?php else: ?><p class="message-placeholder"><?= $reviewTab==='new'?'New review requests will appear here. Select All to view your other follow-ups.':($reviewTab==='records'?'Completed reviews and past follow-ups are kept here.':'Select a follow-up to view its details.') ?></p><?php endif; ?>
</section></div></main></body></html>
