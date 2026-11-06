<?php
require dirname(__DIR__) . '/app/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); header('Allow: POST'); exit('Method not allowed');
}
if (!validCsrf($_POST['csrf'] ?? null)) { http_response_code(403); exit('Please refresh the page and try again.'); }
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $params['path'],
    'domain' => $params['domain'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: login.php', true, 303);
