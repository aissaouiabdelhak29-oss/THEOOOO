<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);

require_once __DIR__ . '/../includes/auth.php';

// حماية: استيراد قاعدة بيانات كاملة يمسح/يستبدل البيانات الحالية،
// لذا يجب أن يقتصر على المشرف مع تأكيد صريح.
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['confirm'] ?? '') !== 'YES_IMPORT_DATABASE') {
    http_response_code(400);
    exit(
        "هذا السكربت يستورد yuki_postgresql_import.sql بالكامل فوق قاعدة البيانات الحالية.\n" .
        "لتشغيله فعلاً، أرسل طلب POST يحتوي confirm=YES_IMPORT_DATABASE\n"
    );
}

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
    echo "تم استيراد قاعدة البيانات بنجاح.\n";

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('import_real_database.php failed: ' . $e->getMessage());
    echo "ERROR:\n";
    echo $e->getMessage() . "\n";
}
