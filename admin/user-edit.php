<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'تعديل مستخدم';

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('users.php', 'المستخدم غير موجود', 'error');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'خطأ في التحقق.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $status = $_POST['status'] ?? 'active';
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($username) || strlen($username) < 3) {
            $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'البريد الإلكتروني غير صالح';
        }

        // Check if username/email taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1");
        $stmt->execute([$username, $email, $id]);
        if ($stmt->fetch()) {
            $errors[] = 'اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل';
        }

        if (empty($errors)) {
            if (!empty($newPassword)) {
                if (strlen($newPassword) < 6) {
                    $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
                } else {
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ?, status = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $hashedPassword, $role, $status, $id]);
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE id = ?");
                $stmt->execute([$username, $email, $role, $status, $id]);
            }

            if (empty($errors)) {
                $success = 'تم تحديث المستخدم بنجاح!';
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $user = $stmt->fetch();
            }
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-user-edit"></i> تعديل: <?php echo sanitize($user['username']); ?></h1>
    <a href="users.php" class="btn btn-outline">
        <i class="fas fa-arrow-right"></i> رجوع
    </a>
</div>

<?php if ($success): ?>
<div class="flash-message flash-success" style="position: static; transform: none; animation: none; margin-bottom: 24px;">
    <i class="fas fa-check-circle"></i> <?php echo sanitize($success); ?>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div style="margin-bottom: 24px;">
    <?php foreach ($errors as $err): ?>
    <div class="flash-message flash-error" style="position: static; transform: none; animation: none; margin-bottom: 8px;">
        <i class="fas fa-exclamation-circle"></i> <?php echo sanitize($err); ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

    <div class="admin-form-grid">
        <div class="form-group">
            <label>اسم المستخدم <span class="required">*</span></label>
            <input type="text" name="username" class="form-input" value="<?php echo sanitize($user['username']); ?>" required>
        </div>

        <div class="form-group">
            <label>البريد الإلكتروني <span class="required">*</span></label>
            <input type="email" name="email" class="form-input" value="<?php echo sanitize($user['email']); ?>" required>
        </div>

        <div class="form-group">
            <label>الصلاحية</label>
            <select name="role" class="form-input">
                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>مستخدم</option>
                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>مدير</option>
            </select>
        </div>

        <div class="form-group">
            <label>الحالة</label>
            <select name="status" class="form-input">
                <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                <option value="banned" <?php echo $user['status'] === 'banned' ? 'selected' : ''; ?>>محظور</option>
            </select>
        </div>

        <div class="form-group full">
            <label>كلمة مرور جديدة (اتركه فارغاً للاحتفاظ بالحالية)</label>
            <input type="password" name="new_password" class="form-input" placeholder="••••••••" minlength="6">
        </div>
    </div>

    <div style="margin-top: 32px;">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> حفظ التغييرات
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
