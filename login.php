<?php
$pageTitle = 'تسجيل الدخول';
require_once 'includes/auth.php'; // تأكد أن هذا الملف يبدأ الجلسة session_start() ويعرف $pdo

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!yuki_rate_limit('login', 10, 900)) { http_response_code(429); $error = 'محاولات كثيرة، حاول لاحقاً.'; } elseif (!validateCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); $error = 'طلب غير صالح.'; } else {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'خطأ في التحقق من الأمان. حاول مرة أخرى.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']); // التحقق من خيار "تذكرني"

        if (empty($email) || empty($password)) {
            $error = 'الرجاء إدخال البريد الإلكتروني وكلمة المرور';
        } else {
            $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // 1. تسجيل الدخول للجلسة
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];

                // 2. منطق "تذكرني"
                if ($remember) {
                    $token = bin2hex(random_bytes(32)); // إنشاء توكن عشوائي
                    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));

                    // حفظ التوكن في المتصفح (Cookie)
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);

                    // حفظ التوكن في قاعدة البيانات للتحقق مستقبلاً
                    $stmtToken = $pdo->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmtToken->execute([$user['id'], $token, $expiry]);
                }

                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                redirect($redirect, 'تم تسجيل الدخول بنجاح!', 'success');
            } else {
                $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
            }
        }
    }
}
    }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | Yuki</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-logo">
                <i class="fas fa-play-circle"></i>
                <h2>تسجيل الدخول</h2>
                <p>أهلاً بك مجدداً في Yuki</p>
            </div>

            <?php if ($error): ?>
            <div class="flash-message flash-error" style="position: static; transform: none; animation: none; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <div class="form-group">
                    <label>البريد الإلكتروني <span class="required">*</span></label>
                    <input type="email" name="email" class="form-input" placeholder="example@email.com" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                </div>

                <div class="form-group">
                    <label>كلمة المرور <span class="required">*</span></label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="remember" id="remember" style="width: auto;">
                    <label for="remember" style="margin: 0; font-weight: 400;">تذكرني (30 يوماً)</label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                </button>
            </form>

            <!-- فاصل بسيط بين التسجيل العادي والاجتماعي -->
            <div class="auth-divider" style="display: flex; align-items: center; text-align: center; margin: 20px 0; color: #888;">
                <span style="padding: 0 10px; font-size: 0.9rem;">أو</span>
            </div>

            <!-- أزرار تسجيل الدخول بواسطة جوجل وفيسبوك -->
            <div class="social-login" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
                <a href="social-login.php?provider=google" class="btn btn-social btn-google" style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; background-color: #fff; color: #333; border: 1px solid #ddd;">
                    <i class="fab fa-google" style="color: #ea4335;"></i> المتابعة باستخدام Google
                </a>
                <a href="social-login.php?provider=facebook" class="btn btn-social btn-facebook" style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; background-color: #1877f2; color: #fff;">
                    <i class="fab fa-facebook-f"></i> المتابعة باستخدام Facebook
                </a>
            </div>

            <div class="auth-footer">
                <p>ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a></p>
            </div>
        </div>
    </div>
</body>
</html>