<?php

require_once __DIR__ . '/bootstrap.php';

$query = trim($_GET['q'] ?? '');

if (mb_strlen($query) < 1) {
    apiResponse(true, [
        'query' => '',
        'results' => [],
    ]);
}

if (mb_strlen($query) > 100) {
    apiResponse(false, null, 'البحث طويل جداً', 422);
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, cat.name AS category_name, g.name AS genre_name
        FROM content c
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN genres g ON c.genre_id = g.id
        WHERE c.status = 'published'
        AND (
            c.title LIKE ? ESCAPE '\\'
            OR c.description LIKE ? ESCAPE '\\'
        )
        ORDER BY c.views DESC, c.created_at DESC
        LIMIT 30
    ");

    // تهريب رموز LIKE الخاصة (% و _) حتى لا يتحكم المستخدم بنمط البحث
    $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
    $like = '%' . $escaped . '%';

    $stmt->execute([$like, $like]);

    apiResponse(true, [
        'query' => $query,
        'results' => array_map('contentItem', $stmt->fetchAll()),
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());
    apiResponse(false, null, 'حدث خطأ أثناء البحث', 500);
}
