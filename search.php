<?php
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$pageTitle = $query ? 'نتائج البحث: ' . $query : 'البحث';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$results = [];
if (!empty($query)) {
    $results = searchContent($query, '', 30);
}
?>

<section class="section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-search"></i> 
            <?php echo $query ? 'نتائج البحث عن: "' . sanitize($query) . '"' : 'البحث'; ?>
        </h2>
        <?php if (!empty($results)): ?>
        <span style="color: var(--text-muted);"><?php echo count($results); ?> نتيجة</span>
        <?php endif; ?>
    </div>

    <?php if (empty($query)): ?>
    <div class="empty-state">
        <i class="fas fa-search"></i>
        <h3>ابحث عن فيلم أو مسلسل</h3>
        <p>اكتب اسم الفيلم أو المسلسل في مربع البحث أعلاه</p>
    </div>
    <?php elseif (empty($results)): ?>
    <div class="empty-state">
        <i class="fas fa-search"></i>
        <h3>لم يتم العثور على نتائج</h3>
        <p>جرب البحث بكلمات مختلفة</p>
    </div>
    <?php else: ?>
    <div class="content-grid">
        <?php foreach ($results as $item): ?>
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
