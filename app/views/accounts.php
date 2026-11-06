<?php
// Display-only account-management view; public/accounts.php prepares its data.
if (!defined('MEDIFOLLOW_ACCOUNTS')) {
    http_response_code(404);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
        <title>MediFollow | <?= e($title) ?></title>
        <link rel="stylesheet" href="assets/css/clinic-dashboard.css">
        <link rel="stylesheet" href="assets/css/accounts.css?v=3">
        <script src="assets/js/sidebar-highlight.js" defer></script>
        <script src="assets/js/account-form.js" defer></script>
        <link rel="stylesheet" href="assets/css/theme.css">
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/account-menu.js" defer></script>
<?php require __DIR__.'/partials/mobile-app.php'; ?>
</head>
    <body class="account-workspace<?= $isPatient ? ' patient-account-workspace' : '' ?>">
        <!-- Sidebar navigation. -->
<aside class="sidebar">
            <div class="brand"><span class="logo" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path d="M10 4h4v6h6v4h-6v6h-4v-6H4v-4h6z" fill="white"/></svg></span>MediFollow<span style="color:#2463db">.</span></div>
            <div class="sub">
                PATIENT FOLLOW-UP &amp; AFTERCARE
            </div>
            <nav aria-label="Main navigation">
                <a class="nav active" href="accounts.php"><?= $isPatient ? 'Patients' : 'Staff accounts' ?></a>
                <a class="nav" href="index.php?view=followups"><?= $account['role'] === 'patient' ? 'My follow-ups' : 'Follow-ups' ?></a>
            <?php require __DIR__.'/partials/review-nav-link.php'; ?>
    </nav>
</aside>
        <!-- Workspace header and signed-in user. -->
<main class="main">
            <header class="workspace-header">
                <?php require __DIR__ . '/partials/account-menu.php'; ?>
            </header>
            <section class="welcome">
                <div>
                    <h1><?= e($title) ?></h1>
                </div>
                <a class="primary" href="accounts.php?new=1#account-form">+ <?= $isPatient ? 'New patient' : 'New staff account' ?></a>
            </section>
            <?php if ($flash !== ''): ?>
                <p class="notice" role="status"><?= e($flash) ?></p>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <p class="account-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <!-- Account form and searchable directory. -->
<div class="account-layout">
                <section class="records" id="account-form">
                    <?php if (!$isPatient): ?><p class="section-kicker">ACCOUNT DETAILS</p><?php endif; ?>
                    <h2><?= $editingId ? 'Edit ' : ($isPatient ? 'Create ' : 'Add ') ?><?= e($singular) ?> account</h2>
                    <?php if (!$isPatient): ?><p class="muted">Enter the account details below.</p><?php endif; ?>
                    <form method="post" action="accounts.php<?= $editingId ? '?edit=' . (int) $editingId : '' ?>" class="account-form">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="action" value="<?= $editingId ? 'update' : 'create' ?>">
                        <?php if ($editingId): ?>
                            <input type="hidden" name="id" value="<?= (int) $editingId ?>">
                        <?php endif; ?>
                        <label for="account-name">Full name</label>
                        <input id="account-name" name="name" <?= isset($_GET['new']) ? 'autofocus' : '' ?> maxlength="100" autocomplete="off" value="<?= e($formName) ?>" required <?= !$connected ? 'disabled' : '' ?>>
                        <label for="account-identifier"><?= e($identityLabel) ?></label>
                        <input id="account-identifier" name="identifier" type="<?= $isPatient ? 'text' : 'email' ?>" maxlength="<?= $isPatient ? 14 : 100 ?>" autocomplete="off" value="<?= e($formIdentifier) ?>" required <?= !$connected ? 'disabled' : '' ?>>
                        <label for="account-secret"><?= $editingId ? 'New ' . strtolower($secretLabel) : e($secretLabel) ?></label>
                        <input id="account-secret" name="secret" type="password" autocomplete="new-password" maxlength="<?= $isPatient ? 12 : 72 ?>" <?= $isPatient ? 'inputmode="numeric" pattern="[0-9]{4,12}"' : 'minlength="8"' ?> <?= !$editingId ? 'required' : '' ?> <?= !$connected ? 'disabled' : '' ?>>
                        <?php if ($editingId): ?>
                            <p class="field-hint">Leave blank to keep the current <?= $isPatient ? 'PIN' : 'password' ?>.</p>
                        <?php endif; ?>
                        <button class="primary-button" type="submit" <?= !$connected ? 'disabled' : '' ?>><?= $editingId ? 'Save changes' : ($isPatient ? 'Create patient account' : 'Create account') ?></button>
                        <?php if ($editingId): ?>
                            <a href="accounts.php" class="cancel-link">Cancel</a>
                        <?php endif; ?>
                    </form>
                </section>
                <section class="records">
                    <div class="section-heading">
                        <div>
                            <?php if (!$isPatient): ?><p class="section-kicker">CLINIC DIRECTORY</p><?php endif; ?>
                            <h2><?= e($title) ?></h2>
                        </div>
                        <span class="account-count">
                            <?php if (!$connected): ?>
                                Count unavailable
                            <?php elseif ($query !== ''): ?>
                                <?= count($visible) ?> of <?= count($rows) ?> <?= $isPatient ? (count($rows) === 1 ? 'patient' : 'patients') : 'staff accounts' ?>
                            <?php else: ?>
                                <?= count($rows) ?> <?= $isPatient ? (count($rows) === 1 ? 'patient' : 'patients') : (count($rows) === 1 ? 'staff account' : 'staff accounts') ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <form action="accounts.php" method="get" class="search-form account-search">
                        <div>
                            <label for="account-search">Search accounts</label>
                            <input id="account-search" type="search" name="q" value="<?= e($query) ?>" placeholder="Name or <?= e(strtolower($identityLabel)) ?>">
                        </div>
                        <button type="submit">Search</button>
                    </form>
                    <div class="table-wrap">
                        <table class="account-table <?= !$visible ? 'is-empty' : '' ?>">
                            <thead>
                                <tr>
                                    <th>
                                        Name
                                    </th>
                                    <?php if(!$isPatient): ?><th><?= e($identityLabel) ?></th><?php endif; ?>
                                    <th>
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($visible as $row): ?>
                                    <tr>
                                        <td data-label="Name">
                                            <div>
                                                <strong><?= e($row['name']) ?></strong><?php if($isPatient): ?><span class="patient-ic muted"><?= e(patientIcLabel($row['identifier'])) ?></span><?php endif; ?>
                                            </div>
                                        </td>
                                        <?php if(!$isPatient): ?><td data-label="<?= e($identityLabel) ?>"><?= e($row['identifier']) ?></td><?php endif; ?>
                                        <td data-label="Actions">
                                            <div class="account-actions">
                                                <a class="edit-link" href="accounts.php?edit=<?= (int) $row['id'] ?>#account-form">Edit</a>
                                                <form action="accounts.php" method="post" onsubmit="return confirm('Remove this account? This cannot be undone.');">
                                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                    <button class="remove-button" type="submit" aria-label="Remove account for <?= e($row['name']) ?>">Remove</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$visible): ?>
                                    <tr>
                                        <td colspan="3" class="empty-state">
                                            <span class="empty-symbol" aria-hidden="true">
                                                <svg viewBox="0 0 40 40" fill="none">
                                                    <circle cx="20" cy="14" r="6"/>
                                                    <path d="M9 33v-3a11 11 0 0 1 22 0v3M13 33h14"/>
                                                </svg>
                                            </span>
                                            <strong><?= !$connected ? 'Accounts unavailable' : ($query !== '' ? 'No matching accounts' : 'No ' . e($singular) . ' accounts yet') ?></strong>
                                            <span><?= $connected ? 'Accounts will appear here once they are added.' : 'Please try again shortly.' ?></span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
