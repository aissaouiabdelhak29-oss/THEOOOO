<?php
declare(strict_types=1);
require_once __DIR__ . '/security.php';

// Render يعطي DATABASE_URL تلقائياً عند إضافة PostgreSQL
$dbUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null);

if ($dbUrl) {
    $parsed = parse_url($dbUrl);
    $host = $parsed['host'] ?? '';
    $port = isset($parsed['port']) ? (int)$parsed['port'] : 5432;
    $dbName = isset($parsed['path']) ? ltrim($parsed['path'], '/') : '';
    $user = $parsed['user'] ?? '';
    $pass = $parsed['pass'] ?? '';
} else {
    // fallback للتطوير المحلي
    $configFile = __DIR__ . '/config.local.php';
    if (!is_file($configFile)) {
        http_response_code(500);
        error_log('Missing private configuration file.');
        exit('Server configuration is incomplete.');
    }
    $config = require $configFile;
    foreach (['db_host','db_name','db_user','db_pass'] as $key) {
        if (!array_key_exists($key, $config) || $config[$key] === '' || str_starts_with((string)$config[$key], 'CHANGE_')) {
            http_response_code(500); 
            exit('Server configuration is incomplete.');
        }
    }
    $hostParts = explode(':', $config['db_host']);
    $host = $hostParts[0];
    $port = isset($hostParts[1]) ? (int)$hostParts[1] : 5432;
    $dbName = $config['db_name'];
    $user = $config['db_user'];
    $pass = $config['db_pass'];
}

try {
    $pdo = new PDO(
        "pgsql:host={$host};port={$port};dbname={$dbName}",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500); 
    exit('Service temporarily unavailable.');
}
