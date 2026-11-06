<?php
// Display-only template loaded by public/index.php after it prepares the data.
if (!defined('MEDIFOLLOW_DASHBOARD')) {
    http_response_code(404);
    exit;
}

// Choose labels and filter options for the signed-in role.
$isPatient = $account['role'] === 'patient';
$recordCount = count($baseRecords);
$canCreateFollowup = $connected && $patients && $doctors;

if ($tab === 'completed') {
    $filterOptions = ['all' => 'All completed'];
} elseif ($isPatient) {
    $filterOptions = [
        'all' => 'All active',
        'upcoming' => 'Upcoming',
        'unanswered' => 'Needs my update',
        'overdue' => 'Overdue',
    ];
} else {
    $filterOptions = [
        'all' => 'All follow-ups',
        'upcoming' => 'Upcoming',
        'overdue' => 'Overdue',
        'attention' => 'Needs doctor review',
        'unanswered' => 'Awaiting update',
    ];
}
?>
<!doctype html>
<html lang="en">
    <head>
        <!-- Styles and browser interactions for the dashboard. -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
        <title>MediFollow | <?= e($roleLabel) ?> Dashboard</title>
        <link rel="stylesheet" href="assets/css/clinic-dashboard.css">
        <script src="assets/js/clinic-dashboard.js" defer></script>
        <script src="assets/js/sidebar-highlight.js" defer></script>
        <link rel="stylesheet" href="assets/css/theme.css">
        <script src="assets/js/theme.js"></script>
        <script src="assets/js/account-menu.js" defer></script>
        <?php require __DIR__ . '/partials/mobile-app.php'; ?>
    </head>
    <body>
        <!-- 1. Sidebar navigation. -->
        <aside class="sidebar">
            <div class="brand"><span class="logo" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path d="M10 4h4v6h6v4h-6v6h-4v-6H4v-4h6z" fill="white"/></svg></span>MediFollow<span style="color:#2463db">.</span></div>
            <div class="sub">
                PATIENT FOLLOW-UP &amp; AFTERCARE
            </div>
            <nav aria-label="Main navigation">
                <?php if (!$isPatient): ?>
                    <a class="nav" href="accounts.php"><?= $canUpdate ? 'Patients' : 'Staff accounts' ?></a>
                <?php endif; ?>
                <a class="nav <?= $view === 'followups' ? 'active' : '' ?>" href="index.php?view=followups"><?= $account['role'] === 'patient' ? 'My follow-ups' : 'Follow-ups' ?></a>
                <?php require __DIR__ . '/partials/review-nav-link.php'; ?>
            </nav>
        </aside>
        <!-- 2. Workspace header and signed-in account. -->
        <main class="main">
            <header class="workspace-header followups-header">
                <h1><?= e($title) ?></h1>
                <div class="workspace-header-actions">
                    <?php if ($canUpdate): ?>
                        <button class="primary" data-new-followup <?= !$canCreateFollowup ? 'disabled' : '' ?>>+ New follow-up</button>
                        <noscript><a class="link" href="?view=followups&amp;create=1">New follow-up form</a></noscript>
                    <?php endif; ?>
                    <?php require __DIR__ . '/partials/account-menu.php'; ?>
                </div>
            </header>
            <?php if (!$connected): ?>
                <p class="notice error" role="alert">Records cannot be loaded. Please try again shortly.</p>
            <?php endif; ?>
            <?php if ($flash !== ''): ?>
                <p class="notice <?= $failed ? 'error' : '' ?>" role="status"><?= e($flash) ?></p>
            <?php endif; ?>
            <nav class="followup-tabs" aria-label="Follow-up list">
                <a href="index.php?view=followups" <?= $tab === 'active' ? 'aria-current="page"' : '' ?>>Active</a>
                <a href="index.php?view=followups&amp;tab=completed" <?= $tab === 'completed' ? 'aria-current="page"' : '' ?>>Completed</a>
            </nav>
            <!-- 3. Follow-up list: heading, search, records, and result count. -->
            <div class="workspace followup-list-workspace">
                <section class="panel<?= $account['role'] === 'patient' ? ' patient-followup-list' : '' ?>">
                    <div class="panelhead">
                        <div>
                            <h2><?= $tab === 'completed' ? 'Completed follow-ups' : 'Active follow-ups' ?></h2>
                            <?php if ($account['role'] !== 'patient'): ?>
                                <div class="muted" style="margin-top:7px">Manage the next step in every patient’s care.</div>
                            <?php endif; ?>
                        </div>
                        <span class="pill"><?= $recordCount ?> <?= $recordCount === 1 ? 'follow-up' : 'follow-ups' ?></span>
                    </div>
                    <div class="toolbar<?= $account['role'] === 'patient' ? ' patient-search' : '' ?>">
                        <form method="get" action="index.php">
                            <input type="hidden" name="view" value="followups">
                            <input type="hidden" name="tab" value="<?= e($tab) ?>">
                            <input
                                type="search"
                                id="search"
                                name="q"
                                maxlength="100"
                                value="<?= e($query) ?>"
                                aria-label="<?= $account['role'] === 'patient' ? 'Search my follow-ups' : 'Search patient follow-ups' ?>"
                                placeholder="<?= $account['role'] === 'patient' ? 'Search by date, doctor or purpose…' : 'Search patient name, IC or purpose…' ?>"
                            >
                            <select id="filter" name="filter" aria-label="Filter follow-up status">
                                <?php foreach ($filterOptions as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= $filter === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="secondary" type="submit" hidden>Search</button>
                            <noscript><button class="secondary" type="submit">Search</button></noscript>
                        </form>
                    </div>
                    <div class="scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>
                                        <?= $account['role'] === 'patient' ? 'Doctor' : 'Patient' ?>
                                    </th>
                                    <th>
                                        Follow-up
                                    </th>
                                    <th>
                                        <?= $account['role'] === 'patient' ? 'Latest update' : 'Patient condition' ?>
                                    </th>
                                    <th>
                                        Status
                                    </th>
                                    <th>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($baseRecords as $r): ?>
                                <?php $status = followUpStatus($r); ?>
                                <tr class="followup-clickable-row" tabindex="0" aria-label="<?= e('View follow-up: ' . $r['reason'] . ' on ' . prettyDate($r['follow_up_date'])) ?>" data-row-href="record.php?id=<?= (int)$r['id'] ?>" data-search="<?= e(strtolower(implode(' ',[$r['full_name'],$r['ic_number'] ?? '',$r['reason'],$r['doctor_name'],$r['follow_up_date'],prettyDate($r['follow_up_date']),(new DateTimeImmutable($r['follow_up_date']))->format('j/n'),(new DateTimeImmutable($r['follow_up_date']))->format('d/m'),$r['followup_time'],$r['patient_condition']??'',$status]))) ?>" data-status="<?= e(strtolower($status)) ?>" data-attention="<?= needsAttention($r)?'1':'0' ?>" data-unanswered="<?= !$r['patient_condition']&&$status!=='Completed'?'1':'0' ?>" <?= !in_array($r,$visible,true)?'hidden':'' ?>>
                                    <td data-label="<?= $account['role'] === 'patient' ? 'Doctor' : 'Patient' ?>">
                                        <?php if ($account['role'] === 'patient'): ?>
                                            <strong><?= e($r['doctor_name']) ?></strong>
                                        <?php else: ?>
                                        <strong><?= e($r['full_name']) ?></strong><span class="patient-ic muted"><?= e(patientIcLabel($r['ic_number'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Follow-up">
                                        <strong><?= e(prettyDate($r['follow_up_date'])) ?> · <?= e((new DateTimeImmutable($r['followup_time']))->format('g:i A')) ?></strong>
                                        <span class="muted"><?= e($r['reason']) ?></span>
                                    </td>
                                    <td data-label="Latest update">
                                        <span class="<?= needsAttention($r) ? 'pill overdue' : '' ?>"><?= e($r['patient_condition'] ?? 'No update yet') ?></span>
                                    </td>
                                    <td data-label="Status">
                                        <span class="pill <?= e(strtolower($status)) ?>"><?= e($status) ?></span>
                                    </td>
                                    <td data-label="Actions">
                                        <?php if ($account['role'] === 'patient'): ?>
                                            <div class="patient-record-actions">
                                                <?php if ($status !== 'Completed'): ?>
                                                    <a class="primary" href="record.php?id=<?= (int)$r['id'] ?>#condition">Update condition</a>
                                                    <a class="link" href="record.php?id=<?= (int)$r['id'] ?>#aftercare">View aftercare</a>
                                                <?php else: ?>
                                                    <a class="link" href="record.php?id=<?= (int)$r['id'] ?>">View details</a>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                        <a class="link table-link" data-record="<?= (int)$r['id'] ?>" href="?view=followups&amp;tab=<?= e($tab) ?>&amp;record=<?= (int)$r['id'] ?>">View ↗</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="no-client-matches" <?= $visible ? 'hidden' : '' ?>>
                                <td colspan="5" class="empty">
                                    <?php if (!$connected): ?>
                                        Records are temporarily unavailable.
                                    <?php elseif (!$baseRecords): ?>
                                        No follow-up records yet.
                                    <?php else: ?>
                                        No matching records. Try another search or filter.
                                    <?php endif; ?>
                                    <?php if ($canUpdate && $connected && !$patients): ?>
                                        <p>
                                            <a href="accounts.php">Create a patient account to get started.</a>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="tablefoot">
                    Showing
                    <span id="visible-count"><?= count($visible) ?></span>
                    of <?= $recordCount ?> follow-up records
                </div>
            </section>
        </div>
        <!-- Follow-up details, create forms, and no-JavaScript fallbacks. -->
        <?php require __DIR__ . '/partials/followup-dialogs.php'; ?>
    </main>
</body>
</html>
