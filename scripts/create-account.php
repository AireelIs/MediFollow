<?php
require_once dirname(__DIR__).'/app/patient-identity.php';
// Local, interactive account provisioning. This does not run through the browser.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit('Not found'); }
require dirname(__DIR__) . '/app/database.php';
$roles = [
    'patient' => ['table' => 'patient', 'identifier' => 'ic_number', 'secret' => 'pin'],
    'doctor' => ['table' => 'doctor', 'identifier' => 'email', 'secret' => 'password'],
    'clinic_staff' => ['table' => 'clinic_staff', 'identifier' => 'email', 'secret' => 'password'],
];
function prompt(string $label, bool $secret = false): string
{
    fwrite(STDOUT, $label . ': ');
    $hide = $secret && stream_isatty(STDIN) && function_exists('system');
    if ($hide) system('stty -echo');
    try { $value = fgets(STDIN); }
    finally { if ($hide) { system('stty echo'); fwrite(STDOUT, "\n"); } }
    if ($value === false) throw new RuntimeException('Account creation cancelled.');
    return $secret ? rtrim($value, "\r\n") : trim($value);
}
try {
    $role = prompt('Account type (patient, doctor, clinic_staff)');
    if (!isset($roles[$role])) throw new RuntimeException('Choose one of the listed account types.');
    $name = prompt('Full name');
    if ($name === '' || mb_strlen($name) > 100) throw new RuntimeException('Enter a name of up to 100 characters.');
    $identifier = prompt($role === 'patient' ? 'IC number' : 'Email address');
    if ($role === 'patient') {
        if (normalizedPatientIc($identifier) === null) throw new RuntimeException('Enter a 12-digit IC number.');
        $identifier = normalizedPatientIc($identifier);
    } elseif (strlen($identifier) > 100 || !filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Enter a valid email address of up to 100 characters.');
    }
    $secret = prompt($role === 'patient' ? 'PIN (4 to 12 digits)' : 'Password (8 to 72 bytes)', true);
    if ($role === 'patient' ? !preg_match('/\A[0-9]{4,12}\z/', $secret) : strlen($secret) < 8 || strlen($secret) > 72) {
        throw new RuntimeException('The PIN or password does not meet the stated requirements.');
    }
    if ($secret !== prompt('Confirm PIN or password', true)) throw new RuntimeException('The confirmation does not match.');
    $fields = $roles[$role];
    $pdo = database();
    $check = $pdo->prepare('SELECT COUNT(*) FROM `' . $fields['table'] . '` WHERE `' . $fields['identifier'] . '` = ?');
    $check->execute([$identifier]);
    if ($check->fetchColumn() > 0) throw new RuntimeException('That sign-in identifier is already registered for this role.');
    $insert = $pdo->prepare('INSERT INTO `' . $fields['table'] . '` (name, `' . $fields['identifier'] . '`, `' . $fields['secret'] . '`) VALUES (?, ?, ?)');
    $insert->execute([$name, $identifier, password_hash($secret, PASSWORD_DEFAULT)]);
    unset($secret);
    fwrite(STDOUT, "Account created. You can now sign in to MediFollow.\n");
} catch (PDOException $exception) {
    fwrite(STDERR, "Account could not be created. Check the database connection and run scripts/setup.php first.\n"); exit(1);
} catch (RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n"); exit(1);
}
