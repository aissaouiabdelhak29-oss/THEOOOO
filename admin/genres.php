<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'الأنواع';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'خطأ في التحقق.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $slug = generateSlug($name);

        if (empty($name)) {
            $errors[] = 'اسم النوع مطلوب';
        } else {
            $stmt = $pdo->prepare("INSERT INTO genres (name, slug) VALUES (?, ?)");
            try {
                $stmt->execute([$name, $slug]);
                $success = 'تم إضافة النوع بنجاح!';
            } catch (PDOException $e) {
                $errors[] = 'النوع موجود بالفعل';
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM genres WHERE id = ?")->execute([$id]);
    redirect('genres.php', 'تم حذف النوع', 'success');
}

$genres = getGenres();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-tags"></i> الأنواع</h1>
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

<div class="admin-form" style="margin-bottom: 32px;">
    <h3 style="margin-bottom: 16px;"><i class="fas fa-plus"></i> إضافة نوع جديد</h3>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="action" value="add">
        <div class="admin-form-grid">
            <div class="form-group full">
                <label>اسم النوع</label>
                <input type="text" name="name" class="form-input" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top: 16px;">
            <i class="fas fa-plus"></i> إضافة
        </button>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-list"></i> قائمة الأنواع</h3>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>الاسم</th>
                <th>الرابط</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($genres as $g): ?>
            <tr>
                <td><?php echo $g['id']; ?></td>
                <td style="font-weight: 700;"><?php echo sanitize($g['name']); ?></td>
                <td><?php echo sanitize($g['slug']); ?></td>
                <td>
                    <div class="actions">
                        <a href="genres.php?delete=<?php echo $g['id']; ?>" class="action-btn delete" onclick="return confirmDelete()">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
