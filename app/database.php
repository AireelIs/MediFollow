<?php
function database(): PDO
{
    $config = require dirname(__DIR__) . '/config/database.php';
    $location = $config['socket'] !== ''
        ? 'unix_socket=' . $config['socket']
        : 'host=' . $config['host'] . ';port=' . $config['port'];
    return new PDO('mysql:' . $location . ';dbname=' . $config['name'] . ';charset=utf8mb4',
        $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 3,
        ]);
}
function followUpStatus(array $record): string
{
    if ($record['status'] === 'Completed') return 'Completed';
    return $record['follow_up_date'] < date('Y-m-d') ? 'Overdue' : 'Upcoming';
}
