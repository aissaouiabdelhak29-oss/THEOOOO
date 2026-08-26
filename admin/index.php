<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'لوحة التحكم';

// Stats
$stats = [];
$stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['movies'] = $pdo->query("SELECT COUNT(*) FROM content WHERE type = 'movie'")->fetchColumn();
$stats['series'] = $pdo->query("SELECT COUNT(*) FROM content WHERE type = 'series'")->fetchColumn();
$stats['episodes'] = $pdo->query("SELECT COUNT(*) FROM episodes")->fetchColumn();
$stats['views'] = $pdo->query("SELECT SUM(views) FROM content")->fetchColumn() ?: 0;

// Latest content
$latestContent = $pdo->query("SELECT c.*, cat.name as category_name 
                              FROM content c LEFT JOIN categories cat ON c.category_id = cat.id 
                              ORDER BY c.created_at DESC LIMIT 10")->fetchAll();

// Latest users
$latestUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 8")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-tachometer-alt"></i> لوحة التحكم</h1>
    <div class="admin-header-actions">
        <a href="content-add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> إضافة محتوى
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="admin-stats">
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['users']); ?></h3>
            <p>المستخدمين</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-film"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['movies']); ?></h3>
            <p>الأفلام</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-tv"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['series']); ?></h3>
            <p>المسلسلات</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-list"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['episodes']); ?></h3>
            <p>الحلقات</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-eye"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['views']); ?></h3>
            <p>إجمالي المشاهدات</p>
        </div>
    </div>
</div>

<!-- Latest Content -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-clock"></i> أحدث المحتوى</h3>
        <a href="content.php" class="section-more">عرض الكل <i class="fas fa-arrow-left"></i></a>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>العنوان</th>
                <th>النوع</th>
                <th>التصنيف</th>
                <th>الجودة</th>
                <th>المشاهدات</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($latestContent as $item): ?>
            <tr>
                <td style="font-weight: 700;"><?php echo sanitize($item['title']); ?></td>
                <td>
                    <span class="badge <?php echo $item['type'] === 'movie' ? 'badge-info' : 'badge-success'; ?>">
                        <?php echo $item['type'] === 'movie' ? 'فيلم' : 'مسلسل'; ?>
                    </span>
                </td>
                <td><?php echo sanitize($item['category_name'] ?? '-'); ?></td>
                <td><?php echo $item['quality']; ?></td>
                <td><?php echo number_format($item['views']); ?></td>
                <td>
                    <span class="badge badge-<?php echo $item['status'] === 'published' ? 'success' : ($item['status'] === 'draft' ? 'warning' : 'info'); ?>">
                        <?php echo $item['status'] === 'published' ? 'منشور' : ($item['status'] === 'draft' ? 'مسودة' : 'معلق'); ?>
                    </span>
                </td>
                <td>
                    <div class="actions">
                        <a href="content-edit.php?id=<?php echo $item['id']; ?>" class="action-btn edit" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="../watch.php?slug=<?php echo urlencode($item['slug']); ?>" target="_blank" class="action-btn view" title="عرض">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="content-delete.php?id=<?php echo $item['id']; ?>&csrf_token=<?php echo urlencode(generateCsrfToken()); ?>" class="action-btn delete" title="حذف" onclick="return confirmDelete()">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Latest Users -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-users"></i> أحدث المستخدمين</h3>
        <a href="users.php" class="section-more">عرض الكل <i class="fas fa-arrow-left"></i></a>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>المستخدم</th>
                <th>البريد</th>
                <th>الصلاحية</th>
                <th>الحالة</th>
                <th>تاريخ التسجيل</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($latestUsers as $user): ?>
            <tr>
                <td style="display: flex; align-items: center; gap: 10px;">
                    <img src="<?php echo $user['avatar'] && $user['avatar'] !== 'default-avatar.png' ? '../uploads/avatars/' . sanitize($user['avatar']) : '../assets/images/default-avatar.png'; ?>" 
                         style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                    <span style="font-weight: 700;"><?php echo sanitize($user['username']); ?></span>
                </td>
                <td><?php echo sanitize($user['email']); ?></td>
                <td>
                    <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>">
                        <?php echo $user['role'] === 'admin' ? 'مدير' : 'مستخدم'; ?>
                    </span>
                </td>
                <td>
                    <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                        <?php echo $user['status'] === 'active' ? 'نشط' : 'محظور'; ?>
                    </span>
                </td>
                <td><?php echo date('Y/m/d', strtotime($user['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
