<?php
require_once __DIR__.'/patient-identity.php';
require_once __DIR__.'/review-notifications.php';
// Shared session and access controls.
// Production hosts must serve HTTPS directly (or set HTTPS in trusted server config).
if (getenv('MEDIFOLLOW_ENV') === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        http_response_code(503);
        exit('A secure HTTPS connection is required.');
    }
    header('Strict-Transport-Security: max-age=31536000');
}
header('Content-Security-Policy: frame-ancestors \'none\'; base-uri \'self\'; form-action \'self\'');
date_default_timezone_set('Asia/Kuala_Lumpur');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
function e($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function accountRoles(): array
{
    return [
        'patient' => ['label' => 'Patient', 'table' => 'patient', 'id' => 'patient_id', 'identifier' => 'ic_number', 'secret' => 'pin'],
        'doctor' => ['label' => 'Doctor', 'table' => 'doctor', 'id' => 'doctor_id', 'identifier' => 'email', 'secret' => 'password'],
        'clinic_staff' => ['label' => 'Clinic Staff', 'table' => 'clinic_staff', 'id' => 'staff_id', 'identifier' => 'email', 'secret' => 'password'],
    ];
}
function csrfToken(): string
{
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function validCsrf($token): bool
{
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}
function currentAccount(): ?array
{
    $user = $_SESSION['account'] ?? null;
    if (!is_array($user) || !isset(accountRoles()[$user['role'] ?? '']) || !isset($user['id'], $user['name'])) return null;
    if (time() - ($_SESSION['last_active'] ?? 0) > 1800) {
        unset($_SESSION['account'], $_SESSION['last_active']);
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return null;
    }
    $_SESSION['last_active'] = time();
    return $user;
}
function workspaceUrl(string $role): string
{
    return 'index.php';
}
function requireAccount(array $allowed): array
{
    $user = currentAccount();
    if (!$user) { header('Location: login.php', true, 303); exit; }
    if (!in_array($user['role'], $allowed, true)) {
        http_response_code(403);
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MediFollow | Access restricted</title><link rel="stylesheet" href="assets/css/login.css"><main class="access-message"><h1>Access restricted</h1><p>This page is not available for your account.</p><a href="' . e(workspaceUrl($user['role'])) . '">Return to your workspace</a></main></html>';
        exit;
    }
    try {
        $fields = accountRoles()[$user['role']];
        $check = database()->prepare('SELECT name, `' . $fields['secret'] . '` AS secret_hash FROM `' . $fields['table'] . '` WHERE `' . $fields['id'] . '` = ?');
        $check->execute([$user['id']]);
        $record = $check->fetch();
        if (!$record || !is_string($_SESSION['credential_fingerprint'] ?? null)
            || !hash_equals($_SESSION['credential_fingerprint'], hash('sha256', $record['secret_hash']))) {
            $_SESSION = [];
            session_regenerate_id(true);
            header('Location: login.php', true, 303); exit;
        }
        $user['name'] = $record['name'];
        $_SESSION['account'] = $user;
    } catch (PDOException | RuntimeException $error) {
        http_response_code(503);
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MediFollow</title><link rel="stylesheet" href="assets/css/login.css"><main class="access-message"><h1>Temporarily unavailable</h1><p>Please try again shortly.</p></main></html>';
        exit;
    }
    return $user;
}
