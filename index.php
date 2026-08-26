<?php
// إيقاف عرض الأخطاء للبيئة الفعلية (قم بتفعيلها فقط أثناء التطوير)
error_reporting(0);

$pageTitle = 'الرئيسية';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// استدعاء البيانات من قاعدة البيانات
$featured = getFeaturedContent(6);
$latestMovies = getLatestContent('movie', 12);
$latestSeries = getLatestContent('series', 12);
$popular = getPopularContent('', 12);
?>

<style>
    /* Hero Slider Styles */
    .hero { position: relative; min-height: 80vh; overflow: hidden; padding: 0; }
    .hero-slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; visibility: hidden; transition: opacity 0.8s ease-in-out; z-index: 1; }
    .hero-slide.active { opacity: 1; visibility: visible; z-index: 2; }
    
    .hero-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; }
    .hero-bg::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.4) 100%); }
    
    .hero-content { position: relative; z-index: 3; height: 100%; display: flex; flex-direction: column; justify-content: center; padding: 0 5%; }
    
    .hero-badge {
        display: inline-block;
        width: fit-content;
        background-color: #ff2a5f;
        color: #ffffff;
        padding: 5px 15px;
        border-radius: 4px;
        margin-bottom: 10px;
        font-weight: bold;
    }
    
    .hero-meta { display: flex; gap: 15px; margin: 15px 0; color: #fff; font-size: 0.9rem; }
    .hero-meta span { display: flex; align-items: center; gap: 5px; }
    .quality-tag { background: #ff2a5f; padding: 2px 8px; border-radius: 4px; font-weight: bold; }
    
    .hero-dots { position: absolute; bottom: 20px; width: 100%; display: flex; justify-content: center; gap: 10px; z-index: 4; }
    .dot { width: 12px; height: 12px; background: rgba(255,255,255,0.3); border-radius: 50%; cursor: pointer; transition: 0.3s; }
    .dot.active { background: #ff2a5f; transform: scale(1.2); }

    /* Card Meta Styles */
    .card-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 6px;
        font-size: 0.85rem;
        color: #a0a0b0;
    }
    .card-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .card-meta .fa-star {
        color: #f1c40f;
    }
</style>

<!-- Hero Section -->
<?php if (!empty($featured)): ?>
<section class="hero" id="hero-slider">
    <?php foreach ($featured as $index => $hero): ?>
    <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>">
        <div class="hero-bg" style="background-image: url('<?php echo !empty($hero['poster']) ? 'uploads/posters/' . sanitize($hero['poster']) : 'assets/images/default-poster.png'; ?>')"></div>
        <div class="hero-content">
            <div class="hero-badge"><i class="fas fa-fire"></i> الأكثر مشاهدة</div>
            <h1 class="hero-title"><?php echo sanitize($hero['title']); ?></h1>
            <p class="hero-desc">
                <?php 
                    $desc = sanitize($hero['description'] ?? '');
                    echo mb_strlen($desc) > 180 ? mb_substr($desc, 0, 180) . '...' : $desc;
                ?>
            </p>
            
            <div class="hero-meta">
                <span class="rating"><i class="fas fa-star"></i> <?php echo sanitize($hero['rating'] ?? '0.0'); ?></span>
                <span><i class="fas fa-calendar"></i> <?php echo sanitize($hero['year'] ?? ''); ?></span>
                <span><i class="fas fa-clock"></i> <?php formatDuration($hero['duration'] ?? 0); ?></span>
                <span class="quality-tag"><?php echo sanitize($hero['quality'] ?? 'HD'); ?></span>
            </div>
            
            <div class="hero-buttons">
                <a href="watch.php?slug=<?php echo urlencode($hero['slug']); ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-play"></i> مشاهدة الآن
                </a>
                <a href="watch.php?slug=<?php echo urlencode($hero['slug']); ?>" class="btn btn-outline btn-lg">
                    <i class="fas fa-info-circle"></i> التفاصيل
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="hero-dots">
        <?php foreach ($featured as $index => $hero): ?>
            <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="jumpToSlide(<?php echo $index; ?>)"></span>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Featured Content -->
<?php if (!empty($featured)): ?>
<section class="section">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-crown"></i> محتوى مميز</h2>
    </div>
    <div class="content-grid">
        <?php foreach ($featured as $item): ?>
        <a href="watch.php?slug=<?php echo urlencode($item['slug']); ?>" class="content-card">
            <div class="card-img-wrap">
                <img src="<?php echo !empty($item['poster']) ? 'uploads/posters/' . sanitize($item['poster']) : 'assets/images/default-poster.png'; ?>" alt="<?php echo sanitize($item['title']); ?>" class="card-img" loading="lazy">
                <div class="card-overlay"><div class="card-play"><i class="fas fa-play"></i></div></div>
                <span class="card-badge"><?php echo ($item['type'] ?? '') === 'movie' ? 'فيلم' : 'مسلسل'; ?></span>
            </div>
            <div class="card-info">
                <h3 class="card-title"><?php echo sanitize($item['title']); ?></h3>
                <div class="card-meta">
                    <span><?php echo sanitize($item['rating'] ?? '0.0'); ?> <i class="fas fa-star"></i></span>
                    <span><?php echo sanitize($item['year'] ?? ''); ?> <i class="far fa-calendar"></i></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Latest Movies -->
<?php if (!empty($latestMovies)): ?>
<section class="section">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-film"></i> أحدث الأفلام</h2>
        <a href="movies.php" class="section-more">عرض الكل <i class="fas fa-arrow-left"></i></a>
    </div>
    <div class="content-grid">
        <?php foreach ($latestMovies as $item): ?>
        <a href="watch.php?slug=<?php echo urlencode($item['slug']); ?>" class="content-card">
            <div class="card-img-wrap">
                <img src="<?php echo !empty($item['poster']) ? 'uploads/posters/' . sanitize($item['poster']) : 'assets/images/default-poster.png'; ?>" alt="<?php echo sanitize($item['title']); ?>" class="card-img" loading="lazy">
            </div>
            <div class="card-info">
                <h3 class="card-title"><?php echo sanitize($item['title']); ?></h3>
                <div class="card-meta">
                    <span><?php echo sanitize($item['rating'] ?? '0.0'); ?> <i class="fas fa-star"></i></span>
                    <span><?php echo sanitize($item['year'] ?? ''); ?> <i class="far fa-calendar"></i></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Latest Series -->
<?php if (!empty($latestSeries)): ?>
<section class="section">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-tv"></i> أحدث المسلسلات</h2>
        <a href="series.php" class="section-more">عرض الكل <i class="fas fa-arrow-left"></i></a>
    </div>
    <div class="content-grid">
        <?php foreach ($latestSeries as $item): ?>
        <a href="watch.php?slug=<?php echo urlencode($item['slug']); ?>" class="content-card">
            <div class="card-img-wrap">
                <img src="<?php echo !empty($item['poster']) ? 'uploads/posters/' . sanitize($item['poster']) : 'assets/images/default-poster.png'; ?>" alt="<?php echo sanitize($item['title']); ?>" class="card-img" loading="lazy">
            </div>
            <div class="card-info">
                <h3 class="card-title"><?php echo sanitize($item['title']); ?></h3>
                <div class="card-meta">
                    <span><?php echo sanitize($item['rating'] ?? '0.0'); ?> <i class="fas fa-star"></i></span>
                    <span><?php echo sanitize($item['year'] ?? ''); ?> <i class="far fa-calendar"></i></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Popular -->
<?php if (!empty($popular)): ?>
<section class="section">
    <div class="section-header"><h2 class="section-title"><i class="fas fa-fire"></i> الأكثر مشاهدة</h2></div>
    <div class="content-grid">
        <?php foreach ($popular as $item): ?>
        <a href="watch.php?slug=<?php echo urlencode($item['slug']); ?>" class="content-card">
            <div class="card-img-wrap">
                <img src="<?php echo !empty($item['poster']) ? 'uploads/posters/' . sanitize($item['poster']) : 'assets/images/default-poster.png'; ?>" alt="<?php echo sanitize($item['title']); ?>" class="card-img" loading="lazy">
            </div>
            <div class="card-info">
                <h3 class="card-title"><?php echo sanitize($item['title']); ?></h3>
                <div class="card-meta">
                    <span><?php echo sanitize($item['rating'] ?? '0.0'); ?> <i class="fas fa-star"></i></span>
                    <span><?php echo sanitize($item['year'] ?? ''); ?> <i class="far fa-calendar"></i></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let currentIdx = 0;
    const slides = document.querySelectorAll(".hero-slide");
    const dots = document.querySelectorAll(".dot");
    let slideTimer;

    if (slides.length > 0) {
        function showSlide(n) {
            slides.forEach(s => s.classList.remove("active"));
            dots.forEach(d => d.classList.remove("active"));
            currentIdx = (n + slides.length) % slides.length;
            slides[currentIdx].classList.add("active");
            if (dots[currentIdx]) dots[currentIdx].classList.add("active");
        }

        function nextSlide() { 
            showSlide(currentIdx + 1); 
        }

        window.jumpToSlide = function (n) {
            clearInterval(slideTimer);
            showSlide(n);
            slideTimer = setInterval(nextSlide, 5000);
        };

        slideTimer = setInterval(nextSlide, 5000);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>