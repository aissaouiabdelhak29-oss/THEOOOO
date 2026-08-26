<?php 
$pageTitle = 'إنشاء حساب'; 
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'خطأ في التحقق من الأمان. حاول مرة أخرى.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($username) || strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'اسم المستخدم يجب أن يكون بين 3 و 50 حرف';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'اسم المستخدم يجب أن يحتوي فقط على أحرف إنجليزية وأرقام وشرطة سفلية';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'البريد الإلكتروني غير صالح';
        }
        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'كلمتا المرور غير متطابقتين';
        }

        // Check if username/email exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل';
            }
        }

        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashedPassword])) {
                $userId = $pdo->lastInsertId();
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_role'] = 'user';
                redirect('index.php', 'تم إنشاء الحساب بنجاح! مرحباً بك في Yuki', 'success');
            } else {
                $errors[] = 'حدث خطأ أثناء إنشاء الحساب. حاول مرة أخرى.';
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
    <title>إنشاء حساب | Yuki</title> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="assets/css/style.css"> 
</head> 
<body> 
<div class="auth-page"> 
    <div class="auth-card"> 
        <div class="auth-logo"> 
            <i class="fas fa-play-circle"></i> 
            <h2>إنشاء حساب</h2> 
            <p>انضم إلى Yuki واستمتع بالمشاهدة</p> 
        </div>

        <?php if (!empty($errors)): ?>
        <div style="margin-bottom: 20px;">
            <?php foreach ($errors as $err): ?>
            <div class="flash-message flash-error" style="position: static; transform: none; animation: none; margin-bottom: 8px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo sanitize($err); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="form-group">
                <label>اسم المستخدم <span class="required">*</span></label>
                <input type="text" name="username" class="form-input" placeholder="username" required 
                       value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ''; ?>"
                       pattern="[a-zA-Z0-9_]+" title="أحرف إنجليزية وأرقام وشرطة سفلية فقط">
            </div>

            <div class="form-group">
                <label>البريد الإلكتروني <span class="required">*</span></label>
                <input type="email" name="email" class="form-input" placeholder="example@email.com" required 
                       value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>كلمة المرور <span class="required">*</span></label>
                <input type="password" name="password" class="form-input" placeholder="••••••••" required minlength="6">
            </div>

            <div class="form-group">
                <label>تأكيد كلمة المرور <span class="required">*</span></label>
                <input type="password" name="confirm_password" class="form-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-user-plus"></i> إنشاء حساب
            </button>
        </form>

        <div class="auth-footer">
            <p>لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
        </div>
    </div>
</div>
</body> 
</html>