<?php
$pageTitle = 'المفضلة';
require_once 'includes/header.php';
require_once 'includes/functions.php';

requireLogin();

$favorites = getUserFavorites($currentUser['id']);
?>

<section class="section">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-heart"></i> المفضلة</h2>
        <span style="color: var(--text-muted);"><?php echo count($favorites); ?> عنصر</span>
    </div>

    <?php if (empty($favorites)): ?>
    <div class="empty-state">
        <i class="fas fa-heart"></i>
        <h3>قائمة المفضلة فارغة</h3>
        <p>أضف الأفلام والمسلسلات المفضلة لديك للوصول إليها بسرعة</p>
        <a href="movies.php" class="btn btn-primary" style="margin-top: 20px;">
            <i class="fas fa-film"></i> تصفح الأفلام
        </a>
    </div>
    <?php else: ?>
    <div class="content-grid">
        <?php foreach ($favorites as $item): ?>
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
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
