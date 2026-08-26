<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'الإعدادات';

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'خطأ في التحقق من الأمان.';
    } else {
        $settings = [
            'site_name',
            'site_description',
            'facebook_url',
            'instagram_url',
            'telegram_url',
            'twitter_url',
            'maintenance_mode',
            'allow_registration',
            'default_server'
        ];

        foreach ($settings as $key) {
            $value = trim($_POST[$key] ?? '');
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        $success = 'تم حفظ الإعدادات بنجاح!';
    }
}

// Get current settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-cog"></i> إعدادات الموقع</h1>
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
            <label>اسم الموقع</label>
            <input type="text" name="site_name" class="form-input" value="<?php echo sanitize($settings['site_name'] ?? 'Yuki'); ?>">
        </div>

        <div class="form-group">
            <label>وصف الموقع</label>
            <input type="text" name="site_description" class="form-input" value="<?php echo sanitize($settings['site_description'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Facebook</label>
            <input type="url" name="facebook_url" class="form-input" value="<?php echo sanitize($settings['facebook_url'] ?? 'https://www.facebook.com/share/14dXFFBeYYp/'); ?>">
        </div>

        <div class="form-group">
            <label>Instagram</label>
            <input type="url" name="instagram_url" class="form-input" value="<?php echo sanitize($settings['instagram_url'] ?? 'https://www.instagram.com/kyou__999'); ?>">
        </div>

        <div class="form-group">
            <label>Telegram</label>
            <input type="url" name="telegram_url" class="form-input" value="<?php echo sanitize($settings['telegram_url'] ?? 'https://t.me/213557740724'); ?>">
        </div>

        <div class="form-group">
            <label>X (Twitter)</label>
            <input type="url" name="twitter_url" class="form-input" value="<?php echo sanitize($settings['twitter_url'] ?? 'https://x.com/Yuki____999'); ?>">
        </div>

        <div class="form-group">
            <label>وضع الصيانة</label>
            <select name="maintenance_mode" class="form-input">
                <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>معطل</option>
                <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>مفعل</option>
            </select>
        </div>

        <div class="form-group">
            <label>التسجيل</label>
            <select name="allow_registration" class="form-input">
                <option value="1" <?php echo ($settings['allow_registration'] ?? '1') === '1' ? 'selected' : ''; ?>>مسموح</option>
                <option value="0" <?php echo ($settings['allow_registration'] ?? '1') === '0' ? 'selected' : ''; ?>>غير مسموح</option>
            </select>
        </div>

        <div class="form-group">
            <label>السيرفر الافتراضي</label>
            <select name="default_server" class="form-input">
                <option value="streamhg" <?php echo ($settings['default_server'] ?? 'streamhg') === 'streamhg' ? 'selected' : ''; ?>>StreamHG</option>
                <option value="earnvids" <?php echo ($settings['default_server'] ?? '') === 'earnvids' ? 'selected' : ''; ?>>EarnVids</option>
                <option value="mixdrop" <?php echo ($settings['default_server'] ?? '') === 'mixdrop' ? 'selected' : ''; ?>>Mixdrop</option>
                <option value="doodstream" <?php echo ($settings['default_server'] ?? '') === 'doodstream' ? 'selected' : ''; ?>>DoodStream</option>
            </select>
        </div>
    </div>

    <div style="margin-top: 32px;">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> حفظ الإعدادات
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
