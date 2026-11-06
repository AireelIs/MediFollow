<?php
// 1. Load session/security helpers and the database connection.
require dirname(__DIR__) . '/app/auth.php';
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/login-throttle.php';

// Signed-in users go straight to their dashboard.
$user = currentAccount();

if ($user) {
    header('Location: ' . workspaceUrl($user['role']), true, 303);
    exit;
}

// 2. Prepare the values used by the login form.
$roles = accountRoles();
$requestedRole = $_POST['role'] ?? $_GET['role'] ?? 'patient';

// Only accept one of the account roles defined by the application.
$role = is_string($requestedRole) && isset($roles[$requestedRole])
    ? $requestedRole
    : 'patient';

$identifier = isset($_POST['identifier']) && is_string($_POST['identifier'])
    ? trim($_POST['identifier'])
    : '';

if ($role === 'patient') $identifier = normalizedPatientIc($identifier) ?? $identifier;
$error = '';
$csrf = csrfToken();

// 3. Process the form only when the user submits it.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $secret = $_POST['secret'] ?? '';

    // Reject requests with a missing or invalid security token.
    if (!validCsrf($_POST['csrf'] ?? null)) {
        http_response_code(403);
        $error = 'Your session expired. Please refresh and try again.';

    // Enforce the temporary delay after repeated incorrect passwords/PINs.
    } elseif (($_SESSION['login_lock_until'] ?? 0) > time()) {
        http_response_code(429);
        header('Retry-After: ' . max(1, $_SESSION['login_lock_until'] - time()));
        $error = 'Too many attempts. Please wait a minute before trying again.';

    // Check the submitted role, identifier, and password/PIN format.
    } elseif (
        !is_string($requestedRole)
        || !isset($roles[$requestedRole])
        || !is_string($secret)
        || $identifier === ''
        || strlen($identifier) > ($role === 'patient' ? 15 : 100)
        || $secret === ''
        || strlen($secret) > 72
        || ($role !== 'patient' && !filter_var($identifier, FILTER_VALIDATE_EMAIL))
    ) {
        $error = 'Please enter valid sign-in details for your account.';
    } else {
        try {
            // Count attempts across sessions before checking credentials.
            $retryAfter = loginRetryAfter($role, $identifier);
            if ($retryAfter > 0) {
                http_response_code(429);
                header('Retry-After: ' . $retryAfter);
                $error = 'Too many attempts. Please wait a minute before trying again.';
                unset($secret);
                define('MEDIFOLLOW_LOGIN', true);
                require dirname(__DIR__) . '/app/views/login.php';
                exit;
            }

            // 4. Find the account in the table assigned to the selected role.
            // Table/column names come from our fixed role map, not user input.
            $fields = $roles[$role];
            $lookupField = $fields['identifier'];
            $lookupValue = $identifier;
            $statement = database()->prepare(
                'SELECT `' . $fields['id'] . '` AS id, name, `'
                . $fields['secret'] . '` AS secret_hash FROM `' . $fields['table']
                . '` WHERE `' . $lookupField . '` = ? LIMIT 2'
            );
            $statement->execute([$lookupValue]);
            $matches = $statement->fetchAll();
            // Transitional access: only patients without an IC keep their old sign-in.
            if ($role === 'patient' && !$matches) {
                $legacyField='phone_number';$legacyValue=$identifier;
                if(preg_match('/\AP0*([1-9][0-9]{0,9})\z/i',$identifier,$legacyId)){
                    $legacyField='patient_id';$legacyValue=(int)$legacyId[1];
                }
                $legacy=database()->prepare('SELECT patient_id AS id,name,pin AS secret_hash FROM patient WHERE ic_number IS NULL AND `'.$legacyField.'`=? LIMIT 2');
                $legacy->execute([$legacyValue]);$matches=$legacy->fetchAll();
            }


            // Exactly one account must match. A fallback hash still performs
            // a password check when the account is missing or duplicated.
            $hash = count($matches) === 1
                ? $matches[0]['secret_hash']
                : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

            $verified = password_verify($secret, $hash);

            // 5. Successful sign-in: start a fresh authenticated session.
            if (count($matches) === 1 && $verified) {
                session_regenerate_id(true);

                $_SESSION = [
                    'account' => [
                        'id' => (int) $matches[0]['id'],
                        'name' => $matches[0]['name'],
                        'role' => $role
                    ],
                    'credential_fingerprint' => hash('sha256', $matches[0]['secret_hash']),
                    'last_active' => time(),
                    'csrf' => bin2hex(random_bytes(32))
                ];

                header('Location: ' . workspaceUrl($role), true, 303);
                exit;
            }

            // 6. Failed sign-in: count the attempt and apply a one-minute
            // delay after five incorrect attempts in the current session.
            $attempts = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['login_attempts'] = $attempts;

            if ($attempts >= 5) {
                $_SESSION['login_lock_until'] = time() + 60;
                $_SESSION['login_attempts'] = 0;
            }

            $error = 'The sign-in details are incorrect.';
        } catch (PDOException | RuntimeException $exception) {
            // Show a readable message without exposing database error details.
            http_response_code(503);
            $error = 'Sign-in is temporarily unavailable. Please try again shortly.';
        }
    }
}

// 7. Display the login view, including any error message prepared above.
// Never pass the submitted password or PIN back to the browser.
unset($secret);
define('MEDIFOLLOW_LOGIN', true);
require dirname(__DIR__) . '/app/views/login.php';
