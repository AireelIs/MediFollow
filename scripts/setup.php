<?php
// Run once from a terminal: php scripts/setup.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}
$config = require dirname(__DIR__) . '/config/database.php';
if (!preg_match('/\A[a-zA-Z0-9_]+\z/', $config['name'])) {
    fwrite(STDERR, "Use letters, numbers and underscores in the database name.\n");
    exit(1);
}
try {
    $location = $config['socket'] !== '' ? 'unix_socket=' . $config['socket']
        : 'host=' . $config['host'] . ';port=' . $config['port'];
    $pdo = new PDO('mysql:' . $location . ';charset=utf8mb4', $config['user'], $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $config['name'] . '` CHARACTER SET utf8mb4');
    $pdo->exec('USE `' . $config['name'] . '`');
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
    echo "MediFollow tables are ready. No records were added.\n";
} catch (PDOException $error) {
    fwrite(STDERR, "Database setup failed. Start MySQL and check config/database.php.\n");
    exit(1);
}
