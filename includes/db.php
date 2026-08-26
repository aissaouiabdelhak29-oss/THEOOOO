<?php
declare(strict_types=1);
require_once __DIR__ . '/security.php';

$configFile = __DIR__ . '/config.local.php';
if (!is_file($configFile)) {
    http_response_code(500);
    error_log('Missing private configuration file.');
    exit('Server configuration is incomplete.');
}
$config = require $configFile;
foreach (['db_host','db_name','db_user','db_pass'] as $key) {
    if (!array_key_exists($key, $config) || $config[$key] === '' || str_starts_with((string)$config[$key], 'CHANGE_')) {
        http_response_code(500); exit('Server configuration is incomplete.');
    }
}
$db_charset = $config['db_charset'] ?? 'utf8mb4';
try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset={$db_charset}",
        $config['db_user'], $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: '.$e->getMessage());
    http_response_code(500); exit('Service temporarily unavailable.');
}
