<?php
// ajax/comment_like.php
require_once '../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// 1. التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً للإعجاب بالتعليق.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'رمز الحماية غير صالح.']);
        exit;
    }
    $comment_id = intval($_POST['comment_id'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? null;

    if ($comment_id <= 0 || !$user_id) {
        echo json_encode(['success' => false, 'message' => 'طلب غير صالح.']);
        exit;
    }

    try {
        // 2. الفحص هل قام المستخدم بالإعجاب بهذا التعليق سابقاً؟
        $check_stmt = $pdo->prepare("SELECT id FROM comment_likes WHERE user_id = ? AND comment_id = ?");
        $check_stmt->execute([$user_id, $comment_id]);
        $already_liked = $check_stmt->fetchColumn();

        if ($already_liked) {
            // إذا كان قد وضع لايك سابقاً -> نقوم بإلغائه (Unlike)
            $delete_like = $pdo->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
            $delete_like->execute([$user_id, $comment_id]);

            // إنقاص عداد اللايكات في جدول التعليقات الرئيسي
            $update_comment = $pdo->prepare("UPDATE comments SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?");
            $update_comment->execute([$comment_id]);
            
            $action = 'unliked';
        } else {
            // إذا لم يضع لايك سابقاً -> نقوم بإضافته (Like)
            $insert_like = $pdo->prepare("INSERT INTO comment_likes (user_id, comment_id) VALUES (?, ?)");
            $insert_like->execute([$user_id, $comment_id]);

            // زيادة عداد اللايكات في جدول التعليقات الرئيسي
            $update_comment = $pdo->prepare("UPDATE comments SET likes_count = likes_count + 1 WHERE id = ?");
            $update_comment->execute([$comment_id]);
            
            $action = 'liked';
        }

        // 3. جلب العداد الجديد لإرساله للواجهة
        $count_stmt = $pdo->prepare("SELECT likes_count FROM comments WHERE id = ?");
        $count_stmt->execute([$comment_id]);
        $new_count = $count_stmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'action' => $action,
            'likes_count' => intval($new_count)
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في السيرفر أثناء معالجة الإعجاب.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'طريقة إرسال خاطئة.']);
    exit;
}