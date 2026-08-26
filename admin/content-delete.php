<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if (!validateCsrfToken($_GET['csrf_token'] ?? '')) {
    redirect('index.php', 'طلب غير صالح', 'error');
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Delete related data first (cascade will handle most, but let's be safe)
    $pdo->prepare("DELETE FROM video_servers WHERE content_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM favorites WHERE content_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM watch_history WHERE content_id = ?")->execute([$id]);

    // Get episodes to delete their servers
    $epStmt = $pdo->prepare("SELECT id FROM episodes WHERE series_id = ?");
    $epStmt->execute([$id]);
    $episodes = $epStmt->fetchAll();
    foreach ($episodes as $ep) {
        $pdo->prepare("DELETE FROM episode_servers WHERE episode_id = ?")->execute([$ep['id']]);
    }
    $pdo->prepare("DELETE FROM episodes WHERE series_id = ?")->execute([$id]);

    // Delete content
    $pdo->prepare("DELETE FROM content WHERE id = ?")->execute([$id]);

    redirect('content.php', 'تم حذف المحتوى بنجاح!', 'success');
} else {
    redirect('content.php', 'معرف غير صالح', 'error');
}
