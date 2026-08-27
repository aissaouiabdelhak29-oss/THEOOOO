<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);

require_once __DIR__ . '/../includes/auth.php';

// حماية: تشغيل SQL كامل من ملف على قاعدة بيانات الإنتاج يجب أن يقتصر على المشرف
// مع تأكيد صريح، لمنع أي زائر من إعادة تشغيل الترحيل عشوائياً.
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['confirm'] ?? '') !== 'YES_RUN_MIGRATION') {
    http_response_code(400);
    exit(
        "هذا السكربت ينفذ migrate_postgres.sql بالكامل على قاعدة البيانات الحالية.\n" .
        "لتشغيله فعلاً، أرسل طلب POST يحتوي confirm=YES_RUN_MIGRATION\n"
    );
}

$sqlFile = __DIR__ . '/migrate_postgres.sql';

if (!file_exists($sqlFile)) {
    exit("ERROR: migrate_postgres.sql not found\n");
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    exit("ERROR: Cannot read migrate_postgres.sql\n");
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
