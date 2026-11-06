<?php
// This view is loaded by public/login.php after it prepares the login data.
// Opening the view directly is not allowed.
if (!defined('MEDIFOLLOW_LOGIN')) {
    http_response_code(404);
    exit;
}

// Patients use an IC number and PIN; other roles use email and password.
$isPatient = $role === 'patient';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,viewport-fit=cover">
    <title>MediFollow | Sign in</title>

    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/login-split.css?v=2">

    <!-- Role switching, show/hide password, Enter submission and focus highlight. -->
    <script src="assets/js/login.js?v=4" defer></script>
<?php require __DIR__.'/partials/mobile-app.php'; ?>
</head>
<body class="login-page login-light-only split-login">

    <!-- 3. Login panel. -->
    <main class="login-shell">
        <aside class="welcome-panel" aria-labelledby="welcome-heading">
            <div class="login-brand" aria-label="MediFollow">
            <svg
                class="brand-mark"
                width="34"
                height="34"
                viewBox="0 0 40 40"
                aria-hidden="true"
            >
                <rect width="40" height="40" rx="11" fill="#2463db" />
                <path d="M17 10h6v7h7v6h-7v7h-6v-7h-7v-6h7z" fill="white" />
            </svg>
            <span>MediFollow<span class="brand-period">.</span></span>
        </div>
            <div class="welcome-copy">
                <p class="welcome-eyebrow">PATIENT FOLLOW-UP &amp; AFTERCARE</p>
                <h2 id="welcome-heading">Care that<br> continues.</h2>
                <p>Stay connected to your clinic, every step of your recovery.</p>
            </div>
        </aside>
        <section class="signin-panel" aria-labelledby="signin-heading">
            <div class="signin-content">
                <h1 id="signin-heading">Sign in</h1>
                <p class="signin-description">Sign in to manage your follow-ups and aftercare.</p>

                <!-- Choose which type of account to sign in with. -->
                <nav class="role-options" aria-label="Account type">
                    <?php foreach (accountRoles() as $key => $details): ?>
                        <a
                            href="login.php?role=<?= e($key) ?>"
                            data-role="<?= e($key) ?>"
                            <?= $role === $key ? 'aria-current="page"' : '' ?>
                        ><?= e($details['label']) ?></a>
                    <?php endforeach; ?>
                </nav>

                <!-- public/login.php supplies this error after an unsuccessful sign-in. -->
                <p
                    class="login-error"
                    id="login-message"
                    role="alert"
                    <?= $error === '' ? 'hidden' : '' ?>
                ><?= e($error) ?></p>

                <!-- 4. Submit credentials to the login handler, not to this view. -->
                <form action="login.php" method="post" id="login-form" autocomplete="on">
                    <!-- Hidden values protect the request and identify the selected role. -->
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" id="account-role" name="role" value="<?= e($role) ?>">

                    <label for="identifier" id="identifier-label">
                        <?= $isPatient ? 'IC number' : 'Email address' ?>
                    </label>
                    <input
                        id="identifier"
                        name="identifier"
                        type="<?= $isPatient ? 'text' : 'email' ?>"
                        autocomplete="username"
                        inputmode="<?= $isPatient ? 'numeric' : 'email' ?>"
                        maxlength="<?= $isPatient ? 15 : 100 ?>"
                        value="<?= e($identifier) ?>"
                        required
                    >

                    <div class="secret-heading">
                        <label for="secret" id="secret-label">
                            <?= $isPatient ? 'PIN' : 'Password' ?>
                        </label>
                    </div>
                    <div class="secret-input">
                        <input
                            id="secret"
                            name="secret"
                            type="password"
                            enterkeyhint="go"
                            autocomplete="current-password"
                            <?= $isPatient ? 'inputmode="numeric"' : '' ?>
                            maxlength="72"
                            required
                        >
                        <button
                            type="button"
                            id="toggle-secret"
                            aria-controls="secret"
                            aria-pressed="false"
                        >Show</button>
                    </div>

                    <details class="pin-help" id="pin-help" <?= !$isPatient ? 'hidden' : '' ?>>
                        <summary>Forgot PIN?</summary>
                        <div class="pin-help-content">
                            <p>Please call or visit the clinic to reset your PIN. Staff will confirm your identity before setting a new PIN.</p>
                            <?php $clinicPhone=trim(getenv('MEDIFOLLOW_CLINIC_PHONE') ?: '03-6039 1493'); ?>
                            <?php if(preg_match('/\A\+?[0-9 ()-]{6,22}\z/',$clinicPhone)): ?><a href="tel:<?= e(preg_replace('/[^+0-9]/','',$clinicPhone)) ?>"><?= e($clinicPhone) ?></a><?php endif; ?>
                        </div>
                    </details>
                    <button type="submit" class="signin-button">Sign in</button>
                </form>

                <p class="account-help">Use the account details provided by your clinic.</p>
            </div>


        </section>
    </main>
</body>
</html>
