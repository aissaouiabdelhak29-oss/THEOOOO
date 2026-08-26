<?php
$pageTitle = 'الأفلام';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 24;

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM content WHERE type = 'movie' AND status = 'published'");
$countStmt->execute();
$total = $countStmt->fetchColumn();

$pagination = paginate($total, $perPage, $page);

$stmt = $pdo->prepare("SELECT c.*, cat.name as category_name, g.name as genre_name 
                       FROM content c 
                       LEFT JOIN categories cat ON c.category_id = cat.id 
                       LEFT JOIN genres g ON c.genre_id = g.id 
                       WHERE c.type = 'movie' AND c.status = 'published' 
                       ORDER BY c.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $pagination['offset']]);
$movies = $stmt->fetchAll();
?>

<section class="section">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-film"></i> الأفلام</h2>
        <span style="color: var(--text-muted);"><?php echo number_format($total); ?> فيلم</span>
    </div>

    <?php if (empty($movies)): ?>
    <div class="empty-state">
        <i class="fas fa-film"></i>
        <h3>لا توجد أفلام حالياً</h3>
        <p>سيتم إضافة الأفلام قريباً</p>
    </div>
    <?php else: ?>
    <div class="content-grid">
        <?php foreach ($movies as $item): ?>
        <a href="watch.php?slug=<?php echo urlencode($item['slug']); ?>" class="content-card">
            <div class="card-img-wrap">
                <img src="<?php echo $item['poster'] ? 'uploads/posters/' . sanitize($item['poster']) : 'assets/images/default-poster.jpg'; ?>" 
                     alt="<?php echo sanitize($item['title']); ?>" class="card-img" loading="lazy">
                <div class="card-overlay">
                    <div class="card-play"><i class="fas fa-play"></i></div>
                </div>
                <span class="card-badge quality"><?php echo $item['quality']; ?></span>
            </div>
            <div class="card-info">
                <h3 class="card-title"><?php echo sanitize($item['title']); ?></h3>
                <div class="card-meta">
                    <span><i class="fas fa-star"></i> <?php echo $item['rating']; ?></span>
                    <span><i class="fas fa-calendar"></i> <?php echo $item['year']; ?></span>
                    <span><i class="fas fa-eye"></i> <?php echo number_format($item['views']); ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="pagination">
        <?php if ($pagination['has_prev']): ?>
        <a href="?page=<?php echo $pagination['current_page'] - 1; ?>"><i class="fas fa-chevron-right"></i></a>
        <?php else: ?>
        <span class="disabled"><i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>

        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
            <?php if ($i == $pagination['current_page']): ?>
            <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($pagination['has_next']): ?>
        <a href="?page=<?php echo $pagination['current_page'] + 1; ?>"><i class="fas fa-chevron-left"></i></a>
        <?php else: ?>
        <span class="disabled"><i class="fas fa-chevron-left"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
