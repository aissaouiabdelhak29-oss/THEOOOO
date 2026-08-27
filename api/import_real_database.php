```php
<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);

require_once __DIR__ . '/../includes/db.php';

// اسم ملف قاعدة البيانات
$sqlFile = __DIR__ . '/yuki_postgresql_import_fixed.sql';

if (!file_exists($sqlFile)) {
    exit("ERROR: ملف قاعدة البيانات غير موجود: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

if ($sql === false || trim($sql) === '') {
    exit("ERROR: لا يمكن قراءة ملف SQL\n");
}

// إزالة التعليقات من الملف
$sql = preg_replace('/--.*$/m', '', $sql);

// تقسيم الملف إلى أوامر SQL منفصلة
$statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);

if (!$statements) {
    exit("ERROR: لا توجد أوامر SQL في الملف\n");
}

try {
    $pdo->beginTransaction();

    $count = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);

        if ($statement === '') {
            continue;
        }

        $count++;

        try {
            $pdo->exec($statement);
        } catch (Throwable $e) {
            throw new RuntimeException(
                "فشل الأمر رقم: $count\n\n" .
                "SQL:\n" .
                substr($statement, 0, 3000) .
                "\n\nالخطأ:\n" .
                $e->getMessage()
            );
        }
    }

    $pdo->commit();

    echo "SUCCESS!\n";
    echo "تم استيراد قاعدة البيانات بنجاح.\n";
    echo "عدد أوامر SQL المنفذة: $count\n";

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "ERROR:\n";
    echo $e->getMessage() . "\n";
}
```
