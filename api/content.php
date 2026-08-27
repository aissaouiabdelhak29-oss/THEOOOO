<?php

require_once __DIR__ . '/bootstrap.php';

$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    apiResponse(false, null, 'معرف المحتوى مطلوب', 422);
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, cat.name AS category_name, g.name AS genre_name
        FROM content c
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN genres g ON c.genre_id = g.id
        WHERE c.slug = ? AND c.status = 'published'
        LIMIT 1
    ");

    $stmt->execute([$slug]);
    $content = $stmt->fetch();

    if (!$content) {
        apiResponse(false, null, 'المحتوى غير موجود', 404);
    }

    apiResponse(true, contentItem($content));

} catch (Throwable $e) {
    error_log($e->getMessage());
    apiResponse(false, null, 'حدث خطأ في تحميل المحتوى', 500);
}
