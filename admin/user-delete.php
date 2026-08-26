<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if (!validateCsrfToken($_GET['csrf_token'] ?? '')) {
    redirect('index.php', 'طلب غير صالح', 'error');
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0 && $id != $currentUser['id']) {
    $pdo->prepare("DELETE FROM favorites WHERE user_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM watch_history WHERE user_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    redirect('users.php', 'تم حذف المستخدم بنجاح!', 'success');
}

redirect('users.php', 'لا يمكن حذف حسابك', 'error');
