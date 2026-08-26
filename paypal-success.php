<?php
// paypal-success.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// 1. التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    die("خطأ غير مصرح به: يرجى تسجيل الدخول أولاً.");
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
$order_id = $_GET['order_id'] ?? null;

// 2. التحقق من استقبال رقم المعاملة من بايبال
if (!$user_id || !$order_id) {
    header('Location: upgrade-vip.php');
    exit;
}

// 3. الفحص الذكي لمتغير قاعدة البيانات المستخدم في سكربتك
$db_instance = null;
if (isset($pdo)) { $db_instance = $pdo; } 
elseif (isset($conn)) { $db_instance = $conn; } 
elseif (isset($db)) { $db_instance = $db; }

if (!$db_instance) {
    die("خطأ تقني: لم يتم التعرف على كائن الاتصال بقاعدة البيانات الخاص بالموقع (\$pdo أو \$conn).");
}

try {
    // 4. تحديث حقل العضوية داخل جدول المستخدمين ليكون vip
    $stmt = $db_instance->prepare("UPDATE users SET membership_type = 'vip' WHERE id = ?");
    $result = $stmt->execute([$user_id]);

    if ($result) {
        // تحديث بيانات الجلسة النشطة حالياً حتى يتغير مظهر الهيدر والاسم فوراً
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['membership_type'] = 'vip';
        }
        $_SESSION['user_membership'] = 'vip';

        // التوجيه للرئيسية بنجاح
        header('Location: index.php?status=success_vip');
        exit;
    } else {
        die("خطأ: فشل تحديث بيانات الجدول. تأكد من وجود حقل membership_type.");
    }

} catch (PDOException $e) {
    die("حدث خطأ أثناء تحديث الحساب في قاعدة البيانات: " . $e->getMessage());
}