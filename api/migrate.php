<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);

require_once __DIR__ . '/../includes/db.php';

$sqlFile = __DIR__ . '/migrate_postgres.sql';

if (!file_exists($sqlFile)) {
    echo "ERROR: migrate_postgres.sql not found\n";
    exit;
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "ERROR: Cannot read migrate_postgres.sql\n";
    exit;
}

// Split SQL into statements by semicolons, but be careful with strings
$statements = [];
$current = '';
$inQuote = false;
$quoteChar = '';
$len = strlen($sql);

for ($i = 0; $i < $len; $i++) {
    $char = $sql[$i];
    $current .= $char;

    if (!$inQuote && ($char === "'" || $char === '"')) {
        $inQuote = true;
        $quoteChar = $char;
    } elseif ($inQuote && $char === $quoteChar) {
        // Check if escaped
        $escaped = false;
        $j = $i - 1;
        while ($j >= 0 && $sql[$j] === '\\') {
            $escaped = !$escaped;
            $j--;
        }
        if (!$escaped) {
            $inQuote = false;
        }
    } elseif (!$inQuote && $char === ';') {
        $stmt = trim($current);
        if ($stmt !== '') {
            $statements[] = $stmt;
        }
        $current = '';
    }
}

// Add remaining
$stmt = trim($current);
if ($stmt !== '') {
    $statements[] = $stmt;
}

$success = 0;
$failed = 0;

foreach ($statements as $stmt) {
    if ($stmt === '' || str_starts_with($stmt, '--')) {
        continue;
    }

    try {
        $pdo->exec($stmt);
        echo "OK: " . substr($stmt, 0, 60) . "...\n";
        $success++;
    } catch (PDOException $e) {
        echo "FAIL: " . substr($stmt, 0, 60) . "...\n";
        echo "  -> " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n===========================\n";
echo "Done: $success statements executed\n";
echo "Failed: $failed statements\n";
