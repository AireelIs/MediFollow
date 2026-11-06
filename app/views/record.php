<?php
if (!defined('MEDIFOLLOW_RECORD')) { http_response_code(404); exit; }
function recordTime(string $value): string { return (new DateTimeImmutable($value))->format('j M Y, H:i'); }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>MediFollow | Follow-up record</title>
    <link rel="stylesheet" href="assets/css/clinic-dashboard.css">
    <link rel="stylesheet" href="assets/css/records.css">
    <script src="assets/js/sidebar-highlight.js" defer></script>
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/account-menu.js" defer></script>
    <script src="assets/js/aftercare-audio.js?v=12" defer></script>
    <script src="assets/js/audio-player.js?v=2" defer></script>
<?php require __DIR__.'/partials/mobile-app.php'; ?>
</head>
<body>
<aside class="sidebar">
    <div class="brand"><span class="logo" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path d="M10 4h4v6h6v4h-6v6h-4v-6H4v-4h6z" fill="white"/></svg></span>MediFollow<span style="color:#2463db">.</span></div>
    <div class="sub">PATIENT FOLLOW-UP &amp; AFTERCARE</div>
    <nav aria-label="Main navigation">
        <?php if ($account['role'] !== 'patient'): ?>
            <a class="nav" href="accounts.php"><?= $account['role'] === 'doctor' ? 'Staff accounts' : 'Patients' ?></a>
        <?php endif; ?>
        <a class="nav active" href="index.php?view=followups"><?= $account['role'] === 'patient' ? 'My follow-ups' : 'Follow-ups' ?></a>
    <?php require __DIR__.'/partials/review-nav-link.php'; ?>
    </nav>
</aside>
<main class="main record-page">
    <header class="workspace-header">
                <?php require __DIR__ . '/partials/account-menu.php'; ?>
            </header>
    <section class="welcome">
        <div><h1>Follow-up record</h1></div>
        <a class="secondary record-back" href="index.php?view=followups<?= $record && $record['status'] === 'Completed' ? '&amp;tab=completed' : '' ?>">Back</a>
    </section>
    <?php if ($error !== ''): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($flash !== '' && $error === '' && $account['role'] !== 'patient'): ?><p class="notice" role="status"><?= e($flash) ?></p><?php endif; ?>
    <?php if ($record): ?>
        <section class="panel record-summary record-summary-clean" aria-label="Follow-up summary">
            <div class="record-patient-line">
                <div><h2><?= e($record['full_name']) ?></h2><p class="patient-ic muted"><?= e(patientIcLabel($record['ic_number'])) ?></p></div>
                <span class="pill <?= e(strtolower(followUpStatus($record))) ?>"><?= e(followUpStatus($record)) ?></span>
            </div>
            <dl class="record-facts">
                <div><dt>Date</dt><dd><?= e((new DateTimeImmutable($record['follow_up_date']))->format('j M Y')) ?></dd></div>
                <div><dt>Time</dt><dd><?= e((new DateTimeImmutable($record['followup_time']))->format('g:i A')) ?></dd></div>
                <div><dt>Doctor</dt><dd><?= e($record['doctor_name']) ?></dd></div>
                <div><dt>Purpose</dt><dd><?= e($record['reason']) ?></dd></div>
            </dl>
        </section>
        <div class="record-sections">
            <section class="panel record-section" id="aftercare">
                <div class="aftercare-heading-row">
                    <h2 id="aftercare-heading">Aftercare instructions</h2>
                    <?php if ($account['role'] === 'doctor'): ?><button class="primary aftercare-edit-button" type="button" data-aftercare-edit hidden aria-controls="aftercare-editor" aria-expanded="<?= !$aftercareEntries || $error !== '' ? 'true' : 'false' ?>"><?= $aftercareEntries ? 'Edit' : 'Add' ?></button><?php endif; ?>
                </div>
                <?php if ($aftercareEntries): ?>
                    <div class="current-care-version">
                        <span class="current-care-badge">Current</span>
                        <?php $careEntry=$aftercareEntries[0];require __DIR__.'/partials/aftercare-entry.php'; ?>
                    </div>
                <?php elseif ($account['role'] !== 'doctor'): ?>
                    <p class="muted">No instructions yet.</p>
                <?php endif; ?>
                <?php if (count($aftercareEntries) > 1): ?>
                    <details class="record-history instruction-versions">
                        <summary><span>Earlier versions</span><span class="instruction-version-count"><?= count($aftercareEntries)-1 ?></span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></summary>
                        <?php foreach (array_slice($aftercareEntries,1) as $careEntry): ?>
                            <article class="timeline-item"><?php require __DIR__.'/partials/aftercare-entry.php'; ?></article>
                        <?php endforeach; ?>
                    </details>
                <?php endif; ?>
                <?php if ($account['role'] === 'doctor'): ?>
                    <details id="aftercare-editor" class="aftercare-editor" <?= !$aftercareEntries || $error !== '' ? 'open' : '' ?>>
                        <summary><span><?= $aftercareEntries ? 'Update instructions' : 'Add instructions' ?></span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></summary>
                        <form action="record.php?id=<?= (int)$id ?>" method="post" enctype="multipart/form-data" data-aftercare-form>
                            <p class="aftercare-edit-hint"><?= $aftercareEntries ? 'Edit the text and optionally add an audio explanation. Saving keeps the earlier version.' : 'Add text, audio, or both.' ?></p>
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="aftercare">
                            <label for="instruction" class="aftercare-editor-label">Written instructions</label>
                            <textarea id="instruction" name="instruction" placeholder="Write care instructions for the patient…" rows="5" maxlength="10000"><?= e($instruction) ?></textarea>
                            <?php require __DIR__ . '/partials/aftercare-audio.php'; ?>
                        </form>
                    </details>
                <?php endif; ?>
            </section>
            <section class="panel record-section" id="condition">
                <h2><?= $account['role'] === 'patient' ? 'How are you feeling?' : 'Patient updates' ?></h2>
                <?php if ($account['role'] === 'patient' && $record['status'] !== 'Completed'): ?>
                    <form action="record.php?id=<?= (int)$id ?>" method="post">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="condition">
                        <label for="condition-choice">Condition</label>
                        <select id="condition-choice" name="condition" required><option value="">Select condition</option>
                            <?php foreach (patientConditionChoices() as $choice): ?><option <?= $condition === $choice ? 'selected' : '' ?> value="<?= e($choice) ?>"><?= e($choice) ?></option><?php endforeach; ?>
                        </select>
                        <label for="condition-details">Notes (optional)</label><textarea id="condition-details" name="details" rows="3" maxlength="2000"><?= e($details) ?></textarea>
                        <label class="checkbox-line"><input type="checkbox" name="doctor_review" value="1" <?= $wantDoctorReview ? 'checked' : '' ?>> Request doctor review</label>
                        <button class="primary" type="submit">Submit</button>
                    </form>
                    <?php if ($flash !== '' && $error === ''): ?>
                        <p class="submission-confirmation" role="status"><?= e($flash === 'Condition update saved. Your doctor review request is with the clinic.' ? 'Update submitted. Your doctor review request is with the clinic.' : 'Your update has been submitted.') ?></p>
                    <?php endif; ?>
                <?php elseif ($account['role'] === 'patient'): ?><p class="muted">This follow-up is completed. Your previous updates remain available below.</p>
                <?php endif; ?>
                <div class="condition-history<?= $account['role'] !== 'patient' ? ' condition-history-review' : '' ?>">
                <?php if ($account['role'] === 'patient'): ?><h3 class="subheading">Previous updates</h3><?php endif; ?>
                <?php foreach ($history['updates'] as $update): ?><article class="timeline-item"><b><?= e($update['condition']) ?></b><div class="muted"><?= e(recordTime($update['submitted_at'])) ?></div><?php if ($update['details'] !== ''): ?><p class="preserve-text"><?= e($update['details']) ?></p><?php endif; ?></article><?php endforeach; ?>
                <?php if (!$history['updates']): ?><p class="muted condition-empty">No updates yet.</p><?php endif; ?>
                </div>
            </section>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
