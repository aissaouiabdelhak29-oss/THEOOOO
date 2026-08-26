<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// 1. التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

// 2. جلب بيانات المستخدم الحالي
$currentUser = getCurrentUser();

if (!$currentUser) {
    echo json_encode(['success' => false, 'message' => 'المستخدم غير موجود أو انتهت جلسته']);
    exit;
}

// 3. التحقق من طريقة إرسال الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة إرسال الطلب غير صحيحة']);
    exit;
}

// 4. التحقق من توكن الحماية CSRF
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'خطأ في التحقق من رمز الأمان (CSRF Token)']);
    exit;
}

// 5. جلب وتنقية معرف المحتوى
$contentId = intval($_POST['content_id'] ?? 0);

if ($contentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرف المحتوى غير صالح']);
    exit;
}

// 6. معالجة التبديل في المفضلة (إضافة / إزالة)
if (isFavorite($currentUser['id'], $contentId)) {
    if (removeFavorite($currentUser['id'], $contentId)) {
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء محاولة الإزالة من المفضلة']);
    }
} else {
    if (addFavorite($currentUser['id'], $contentId)) {
        echo json_encode(['success' => true, 'action' => 'added']);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء محاولة الإضافة إلى المفضلة']);
    }
}
exit;