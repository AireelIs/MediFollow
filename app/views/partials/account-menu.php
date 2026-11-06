<?php
// A native disclosure keeps account options accessible without JavaScript.
if (!defined('MEDIFOLLOW_DASHBOARD') && !defined('MEDIFOLLOW_ACCOUNTS') && !defined('MEDIFOLLOW_RECORD') && !defined('MEDIFOLLOW_MESSAGES')) {
    http_response_code(404);
    exit;
}
$menuRoleLabel = accountRoles()[$account['role']]['label'];
?>
<details class="account-menu">
    <summary class="account-menu-trigger" aria-label="Account options for <?= e($account['name']) ?>">
        <span class="account-menu-identity"><b class="account-name"><?= e($account['name']) ?></b><span class="muted"><?= e($menuRoleLabel) ?></span></span>
        <svg class="account-menu-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="account-menu-panel">
        <div class="account-appearance-row"><span>Appearance</span><?php require __DIR__ . '/theme-toggle.php'; ?></div>
        <form action="logout.php" method="post">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <button class="account-signout" type="submit">Sign out</button>
        </form>
    </div>
</details>
