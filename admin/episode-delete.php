<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if (!validateCsrfToken($_GET['csrf_token'] ?? '')) {
    redirect('index.php', 'طلب غير صالح', 'error');
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT series_id FROM episodes WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $episode = $stmt->fetch();

    if ($episode) {
        $pdo->prepare("DELETE FROM episode_servers WHERE episode_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM episodes WHERE id = ?")->execute([$id]);
        redirect('episodes.php?series=' . $episode['series_id'], 'تم حذف الحلقة بنجاح!', 'success');
    }
}

redirect('episodes.php', 'معرف غير صالح', 'error');
