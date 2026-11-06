<?php
// Local development settings. Environment variables can override these values.
if (getenv('MEDIFOLLOW_ENV') === 'production'
    && (!getenv('MEDIFOLLOW_DB_HOST') || !getenv('MEDIFOLLOW_DB_NAME')
        || !getenv('MEDIFOLLOW_DB_USER') || getenv('MEDIFOLLOW_DB_USER') === 'root'
        || !getenv('MEDIFOLLOW_DB_PASSWORD'))) {
    throw new RuntimeException('Production database settings are incomplete.');
}
$localSocket = (getenv('HOME') ?: '') . '/Documents/Codex/MediFollow-data/mysql.sock';
return [
    'host' => getenv('MEDIFOLLOW_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('MEDIFOLLOW_DB_PORT') ?: '3306',
    'name' => getenv('MEDIFOLLOW_DB_NAME') ?: 'medifollow',
    'user' => getenv('MEDIFOLLOW_DB_USER') ?: 'root',
    'password' => getenv('MEDIFOLLOW_DB_PASSWORD') ?: '',
    'socket' => getenv('MEDIFOLLOW_DB_SOCKET') ?: ((getenv('MEDIFOLLOW_DB_HOST') || !file_exists($localSocket)) ? '' : $localSocket),
];
