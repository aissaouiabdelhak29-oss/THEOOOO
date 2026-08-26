<?php
// إجبار الاستضافة على إرسال الـ JSON مباشرة وتجاوز حماية الـ HTML
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// تعطيل كوكيز الحماية المؤقتة للـ API إن وجدت
if (!isset($_COOKIE['__test'])) {
    setcookie('__test', 'bypass', time() + 86400, '/');
}

require_once __DIR__ . '/bootstrap.php';

try {
    $featuredStmt = $pdo->query("
        SELECT c.*, cat.name AS category_name, g.name AS genre_name
        FROM content c
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN genres g ON c.genre_id = g.id
        WHERE c.status = 'published'
        ORDER BY c.featured DESC, c.created_at DESC
        LIMIT 6
    ");

    $moviesStmt = $pdo->query("
        SELECT c.*, cat.name AS category_name, g.name AS genre_name
        FROM content c
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN genres g ON c.genre_id = g.id
        WHERE c.status = 'published' AND c.type = 'movie'
        ORDER BY c.created_at DESC
        LIMIT 12
    ");

    $seriesStmt = $pdo->query("
        SELECT c.*, cat.name AS category_name, g.name AS genre_name
        FROM content c
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN genres g ON c.genre_id = g.id
        WHERE c.status = 'published' AND c.type = 'series'
        ORDER BY c.created_at DESC
        LIMIT 12
    ");

    $popularStmt = $pdo->query("
        SELECT c.*, cat.name AS category_name, g.name AS genre_name
        FROM content c
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN genres g ON c.genre_id = g.id
        WHERE c.status = 'published'
        ORDER BY c.views DESC, c.created_at DESC
        LIMIT 12
    ");

    apiResponse(true, [
        'featured' => array_map('contentItem', $featuredStmt->fetchAll()),
        'movies' => array_map('contentItem', $moviesStmt->fetchAll()),
        'series' => array_map('contentItem', $seriesStmt->fetchAll()),
        'popular' => array_map('contentItem', $popularStmt->fetchAll()),
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());
    apiResponse(false, null, 'حدث خطأ في تحميل المحتوى', 500);
}