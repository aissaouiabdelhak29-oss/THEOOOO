<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);

require_once __DIR__ . '/../includes/db.php';

$sqlFile = __DIR__ . '/yuki_postgresql_import.sql';

if (!file_exists($sqlFile)) {
    exit("ERROR: ملف قاعدة البيانات غير موجود: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

if ($sql === false || trim($sql) === '') {
    exit("ERROR: لا يمكن قراءة ملف SQL\n");
}

try {
    $pdo->beginTransaction();

    $pdo->exec($sql);

    $pdo->commit();

    echo "SUCCESS!\n";
    echo "تم استيراد قاعدة البيانات الحقيقية إلى PostgreSQL بنجاح.\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "ERROR:\n";
    echo $e->getMessage() . "\n";
}
