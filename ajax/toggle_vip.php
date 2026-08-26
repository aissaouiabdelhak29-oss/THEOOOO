<?php
// ajax/toggle_vip.php
require_once '../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// 1. التحقق من أن المستخدم مسجل دخول وأنه مدير (Admin)
$is_admin = false;
if (isLoggedIn() && isset($_SESSION['user_role'])) {
    $is_admin = ($_SESSION['user_role'] === 'admin');
} elseif (isLoggedIn() && isset($currentUser['role'])) {
    $is_admin = ($currentUser['role'] === 'admin');
}

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالقيام بهذا الإجراء.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'رمز الحماية غير صالح.']);
        exit;
    }
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'بيانات المستخدم غير صالحة.']);
        exit;
    }

    try {
        // 2. جلب حالة العضوية الحالية للمستخدم المستهدف
        $stmt = $pdo->prepare("SELECT membership_type FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_type = $stmt->fetchColumn();

        if (!$current_type) {
            echo json_encode(['success' => false, 'message' => 'المستخدم غير موجود.']);
            exit;
        }

        // 3. عكس الحالة (إذا كان VIP يرجع عادي، وإذا كان عادي يصبح VIP)
        $new_type = ($current_type === 'vip') ? 'regular' : 'vip';
        
        $update_stmt = $pdo->prepare("UPDATE users SET membership_type = ? WHERE id = ?");
        $update_stmt->execute([$new_type, $user_id]);

        echo json_encode([
            'success' => true,
            'new_type' => $new_type,
            'message' => ($new_type === 'vip') ? 'تمت الترقية إلى VIP بنجاح!' : 'تم إلغاء الترقية بنجاح.'
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في السيرفر أثناء تعديل العضوية.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'طريقة إرسال خاطئة.']);
    exit;
}