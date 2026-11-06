<?php
// 1. Load security/database helpers and require a Doctor or Clinic Staff account.
require dirname(__DIR__) . '/app/auth.php';
require dirname(__DIR__) . '/app/database.php';

$account = requireAccount(['doctor', 'clinic_staff']);

// Doctors manage staff; Clinic Staff manage patients.
// The signed-in role determines the target table, never a submitted form value.
$targetRole = $account['role'] === 'doctor' ? 'clinic_staff' : 'patient';
$fields = accountRoles()[$targetRole];
$isPatient = $targetRole === 'patient';

// 2. Prepare page labels, form values, search text, and security token.
$singular = $isPatient ? 'patient' : 'staff';
$title = $isPatient ? 'Patient accounts' : 'Staff accounts';
$identityLabel = $isPatient ? 'IC number' : 'Email address';
$secretLabel = $isPatient ? 'PIN' : 'Password';
$csrf = csrfToken();

$editingId = filter_var(
    $_GET['edit'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
) ?: null;

$formName = '';
$formIdentifier = '';
$error = '';
$connected = true;
$query = isset($_GET['q']) && is_string($_GET['q'])
    ? mb_substr(trim($_GET['q']), 0, 100)
    : '';

$flash = $_SESSION['account_flash'] ?? '';
unset($_SESSION['account_flash']);
$pdo = null;

try {
    $pdo = database();

    // 3. Process account create, update, and delete requests.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = $_POST['action'] ?? '';
        $id = filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        ) ?: null;

        if (!validCsrf($_POST['csrf'] ?? null)) {
            http_response_code(403);
            $error = 'The form expired. Please refresh and try again.';
        } elseif (
            !is_string($action)
            || !in_array($action, ['create', 'update', 'delete'], true)
            || ($action !== 'create' && !$id)
        ) {
            http_response_code(400);
            $error = 'Please choose a valid account action.';
        } else {
            // Before editing or deleting, confirm the target account still exists.
            if ($action !== 'create') {
                $exists = $pdo->prepare(
                    'SELECT `' . $fields['id'] . '` FROM `' . $fields['table'] . '` WHERE `' . $fields['id'] . '` = ?'
                );
                $exists->execute([$id]);

                if (!$exists->fetch()) {
                    http_response_code(404);
                    $error = 'That account no longer exists.';
                }
            }

            // 4. Delete the account and return to a fresh GET page.
            if ($error === '' && $action === 'delete') {
                $delete = $pdo->prepare(
                    'DELETE FROM `' . $fields['table'] . '` WHERE `' . $fields['id'] . '` = ?'
                );
                $delete->execute([$id]);
                $_SESSION['account_flash'] = ucfirst($singular) . ' account removed.';
                header('Location: accounts.php', true, 303);
                exit;
            }

            // 5. Validate the details supplied for account creation or editing.
            if ($error === '') {
                $editingId = $action === 'update' ? $id : null;
                $formName = isset($_POST['name']) && is_string($_POST['name'])
                    ? trim($_POST['name'])
                    : '';
                $formIdentifier = isset($_POST['identifier']) && is_string($_POST['identifier'])
                    ? trim($_POST['identifier'])
                    : '';
                if ($isPatient) $formIdentifier = normalizedPatientIc($formIdentifier) ?? $formIdentifier;
                $secret = $_POST['secret'] ?? '';

                if ($formName === '' || mb_strlen($formName) > 100)
                    $error = 'Enter a name of up to 100 characters.';
                elseif (
                    $isPatient
                        ? normalizedPatientIc($formIdentifier) === null
                        : (strlen($formIdentifier) > 100 || !filter_var($formIdentifier, FILTER_VALIDATE_EMAIL))
                ) {
                    $error = $isPatient
                        ? 'Enter a 12-digit IC number, with or without hyphens.'
                        : 'Enter a valid email address of up to 100 characters.';
                } elseif (!is_string($secret))
                    $error = 'Enter a valid PIN or password.';
                elseif ($action === 'create' && $secret === '')
                    $error = 'Enter a ' . strtolower($secretLabel) . ' for the new account.';
                elseif (
                    $secret !== ''
                    && ($isPatient ? !preg_match('/\A[0-9]{4,12}\z/', $secret) : strlen($secret) < 8 || strlen($secret) > 72)
                ) {
                    $error = $isPatient
                        ? 'Use a PIN of 4 to 12 digits.'
                        : 'Use a password between 8 and 72 bytes.';
                }

                // Reject an identifier already assigned to another account.
                if ($error === '') {
                    $duplicate = $pdo->prepare(
                        'SELECT COUNT(*) FROM `' . $fields['table'] . '` WHERE `' . $fields['identifier'] . '` = ? AND `' . $fields['id'] . '` <> ?'
                    );
                    $duplicate->execute([$formIdentifier, $editingId ?? 0]);

                    if ($duplicate->fetchColumn() > 0)
                        $error = 'That ' . strtolower($identityLabel) . ' is already registered.';
                }

                // 6. Save the account. Passwords and PINs are stored as hashes.
                if ($error === '') {
                    if ($action === 'create') {
                        $insert = $pdo->prepare(
                            'INSERT INTO `' . $fields['table'] . '` (name, `' . $fields['identifier'] . '`, `' . $fields['secret'] . '`) VALUES (?, ?, ?)'
                        );
                        $insert->execute([$formName, $formIdentifier, password_hash($secret, PASSWORD_DEFAULT)]);
                        $createdId = (int)$pdo->lastInsertId();
                    } elseif ($secret !== '') {
                        // Editing with a new password/PIN also replaces its stored hash.
                        $update = $pdo->prepare(
                            'UPDATE `' . $fields['table'] . '` SET name = ?, `' . $fields['identifier'] . '` = ?, `' . $fields['secret'] . '` = ? WHERE `' . $fields['id'] . '` = ?'
                        );
                        $update->execute([$formName, $formIdentifier, password_hash($secret, PASSWORD_DEFAULT), $editingId]);
                    } else {
                        // Blank secret fields preserve the existing password/PIN.
                        $update = $pdo->prepare(
                            'UPDATE `' . $fields['table'] . '` SET name = ?, `' . $fields['identifier'] . '` = ? WHERE `' . $fields['id'] . '` = ?'
                        );
                        $update->execute([$formName, $formIdentifier, $editingId]);
                    }

                    unset($secret);
                    $_SESSION['account_flash'] = $isPatient && $action === 'create'
                        ? 'Patient account created for ' . $formName . '.'
                        : ucfirst($singular) . ' account ' . ($action === 'create' ? 'created.' : 'updated.');
                    header('Location: accounts.php', true, 303);
                    exit;
                }

                if ($error !== '') http_response_code(400);
                unset($secret);
            }
        }
    }

    // 7. When opening an Edit link, fill the form with the saved account details.
    // Never retrieve its password/PIN for display.
    if ($editingId && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        $edit = $pdo->prepare(
            'SELECT name, `' . $fields['identifier'] . '` AS identifier FROM `' . $fields['table'] . '` WHERE `' . $fields['id'] . '` = ?'
        );
        $edit->execute([$editingId]);
        $selected = $edit->fetch();

        if ($selected) {
            $formName = $selected['name'];
            $formIdentifier = $selected['identifier'];
        } else {
            http_response_code(404);
            $error = 'That account no longer exists.';
            $editingId = null;
        }
    }

    // 8. Retrieve the account directory for the current user's permitted role.
    $rows = $pdo->query(
        'SELECT `' . $fields['id'] . '` AS id, name, `' . $fields['identifier'] . '` AS identifier FROM `' . $fields['table'] . '` ORDER BY name, `' . $fields['id'] . '`'
    )->fetchAll();
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        // Linked records prevent deletion of an account they depend on.
        http_response_code(409);
        $error = ($action ?? '') === 'delete' ? 'This account has linked records and cannot be removed.' : 'That IC number or email address is already registered.';
        $rows = $pdo->query(
            'SELECT `' . $fields['id'] . '` AS id, name, `' . $fields['identifier'] . '` AS identifier FROM `' . $fields['table'] . '` ORDER BY name'
        )->fetchAll();
    } else {
        http_response_code(503);
        $connected = false;
        $rows = [];
        $error = 'Accounts are temporarily unavailable. Please try again shortly.';
    }
}

// 9. Apply the directory search, then display the account-management view.
$visible = array_filter(
    $rows,
    fn($row) => $query === '' || mb_stripos($row['name'] . ' ' . ($row['identifier'] ?? ''), $query) !== false
);

define('MEDIFOLLOW_ACCOUNTS', true);
require dirname(__DIR__) . '/app/views/accounts.php';
