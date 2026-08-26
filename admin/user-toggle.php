<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if (!validateCsrfToken($_GET['csrf_token'] ?? '')) {
    redirect('index.php', 'طلب غير صالح', 'error');
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0 && $id != $currentUser['id']) {
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if ($user) {
        $newStatus = $user['status'] === 'active' ? 'banned' : 'active';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
        redirect('users.php', 'تم تحديث حالة المستخدم', 'success');
    }
}

redirect('users.php', 'خطأ', 'error');
