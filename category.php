<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$type = $_GET['type'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    redirect('index.php', 'التصنيف غير موجود', 'error');
}

$pageTitle = sanitize($category['name']);

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 24;

$sql = "SELECT COUNT(*) FROM content WHERE category_id = ? AND status = 'published'";
$params = [$category['id']];
if (!empty($type)) {
    $sql .= " AND type = ?";
    $params[] = $type;
}
$countStmt = $pdo->prepare($sql);
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$pagination = paginate($total, $perPage, $page);

$sql = "SELECT c.*, cat.name as category_name, g.name as genre_name 
        FROM content c 
        LEFT JOIN categories cat ON c.category_id = cat.id 
        LEFT JOIN genres g ON c.genre_id = g.id 
        WHERE c.category_id = ? AND c.status = 'published'";
$params = [$category['id']];
if (!empty($type)) {
    $sql .= " AND c.type = ?";
    $params[] = $type;
}
$sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $pagination['offset'];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$content = $stmt->fetchAll();
?>

<section class="section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-<?php echo $category['icon']; ?>"></i> 
            <?php echo sanitize($category['name']); ?>
            <?php echo !empty($type) ? ($type === 'movie' ? ' - أفلام' : ' - مسلسلات') : ''; ?>
        </h2>
        <span style="color: var(--text-muted);"><?php echo number_format($total); ?> نتيجة</span>
    </div>

    <?php if (empty($content)): ?>
    <div class="empty-state">
        <i class="fas fa-<?php echo $category['icon']; ?>"></i>
        <h3>لا يوجد محتوى في هذا التصنيف</h3>
    </div>
    <?php else: ?>
    <div class="content-grid">
        <?php foreach ($content as $item): ?>
        <a href="watch.php?slug=<?php echo urlencode($item['slug']); ?>" class="content-card">
            <div class="card-img-wrap">
                <img src="<?php echo $item['poster'] ? 'uploads/posters/' . sanitize($item['poster']) : 'assets/images/default-poster.jpg'; ?>" 
                     alt="<?php echo sanitize($item['title']); ?>" class="card-img" loading="lazy">
                <div class="card-overlay">
                    <div class="card-play"><i class="fas fa-play"></i></div>
                </div>
                <span class="card-badge"><?php echo $item['type'] === 'movie' ? 'فيلم' : 'مسلسل'; ?></span>
                <span class="card-badge quality" style="top: auto; bottom: 10px;"><?php echo $item['quality']; ?></span>
            </div>
            <div class="card-info">
                <h3 class="card-title"><?php echo sanitize($item['title']); ?></h3>
                <div class="card-meta">
                    <span><i class="fas fa-star"></i> <?php echo $item['rating']; ?></span>
                    <span><i class="fas fa-calendar"></i> <?php echo $item['year']; ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="pagination">
        <?php if ($pagination['has_prev']): ?>
        <a href="?slug=<?php echo urlencode($slug); ?>&type=<?php echo $type; ?>&page=<?php echo $pagination['current_page'] - 1; ?>"><i class="fas fa-chevron-right"></i></a>
        <?php else: ?>
        <span class="disabled"><i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>

        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
            <?php if ($i == $pagination['current_page']): ?>
            <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="?slug=<?php echo urlencode($slug); ?>&type=<?php echo $type; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($pagination['has_next']): ?>
        <a href="?slug=<?php echo urlencode($slug); ?>&type=<?php echo $type; ?>&page=<?php echo $pagination['current_page'] + 1; ?>"><i class="fas fa-chevron-left"></i></a>
        <?php else: ?>
        <span class="disabled"><i class="fas fa-chevron-left"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
