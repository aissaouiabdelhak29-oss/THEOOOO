<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'إدارة المحتوى';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;

$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$where = ["1=1"];
$params = [];
if (!empty($type)) {
    $where[] = "c.type = ?";
    $params[] = $type;
}
if (!empty($status)) {
    $where[] = "c.status = ?";
    $params[] = $status;
}

$whereStr = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM content c WHERE $whereStr");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$pagination = paginate($total, $perPage, $page);

$sql = "SELECT c.*, cat.name as category_name, g.name as genre_name 
        FROM content c 
        LEFT JOIN categories cat ON c.category_id = cat.id 
        LEFT JOIN genres g ON c.genre_id = g.id 
        WHERE $whereStr 
        ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $pagination['offset'];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$content = $stmt->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-film"></i> إدارة المحتوى</h1>
    <div class="admin-header-actions">
        <a href="content-add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> إضافة محتوى
        </a>
    </div>
</div>

<!-- Filters -->
<div style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
    <a href="content.php" class="btn <?php echo empty($type) && empty($status) ? 'btn-primary' : 'btn-outline'; ?>">الكل</a>
    <a href="content.php?type=movie" class="btn <?php echo $type === 'movie' ? 'btn-primary' : 'btn-outline'; ?>"><i class="fas fa-film"></i> أفلام</a>
    <a href="content.php?type=series" class="btn <?php echo $type === 'series' ? 'btn-primary' : 'btn-outline'; ?>"><i class="fas fa-tv"></i> مسلسلات</a>
    <a href="content.php?status=published" class="btn <?php echo $status === 'published' ? 'btn-primary' : 'btn-outline'; ?>"><i class="fas fa-check"></i> منشور</a>
    <a href="content.php?status=draft" class="btn <?php echo $status === 'draft' ? 'btn-primary' : 'btn-outline'; ?>"><i class="fas fa-pen"></i> مسودة</a>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-list"></i> قائمة المحتوى (<?php echo number_format($total); ?>)</h3>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>البوستر</th>
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
            <?php foreach ($content as $item): ?>
            <tr>
                <td><?php echo $item['id']; ?></td>
                <td>
                    <img src="<?php echo $item['poster'] ? '../uploads/posters/' . sanitize($item['poster']) : '../assets/images/default-poster.png'; ?>" 
                         style="width: 50px; height: 75px; object-fit: cover; border-radius: 6px;">
                </td>
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
                        <a href="content-delete.php?id=<?php echo $item['id']; ?>&csrf_token=<?php echo urlencode(generateCsrfToken()); ?>" class="action-btn delete" title="حذف" onclick="return confirmDelete('هل أنت متأكد من حذف هذا المحتوى؟')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($pagination['total_pages'] > 1): ?>
<div class="pagination">
    <?php if ($pagination['has_prev']): ?>
    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])); ?>"><i class="fas fa-chevron-right"></i></a>
    <?php else: ?>
    <span class="disabled"><i class="fas fa-chevron-right"></i></span>
    <?php endif; ?>

    <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
        <?php if ($i == $pagination['current_page']): ?>
        <span class="current"><?php echo $i; ?></span>
        <?php else: ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($pagination['has_next']): ?>
    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])); ?>"><i class="fas fa-chevron-left"></i></a>
    <?php else: ?>
    <span class="disabled"><i class="fas fa-chevron-left"></i></span>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
