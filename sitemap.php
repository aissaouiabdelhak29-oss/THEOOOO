<?php
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/xml; charset=UTF-8');

$siteConfig = require __DIR__ . '/includes/config.local.php';
$base_url = rtrim((string)($siteConfig['site_url'] ?? ''), '/');
if ($base_url === '') { http_response_code(500); exit; }

function xml($value) {
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <!-- الصفحات الرئيسية -->
    <url>
        <loc><?= xml($base_url) ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <url>
        <loc><?= xml($base_url) ?>/movies.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <url>
        <loc><?= xml($base_url) ?>/series.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <url>
        <loc><?= xml($base_url) ?>/about.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc><?= xml($base_url) ?>/contact.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- التصنيفات -->
    <?php
    try {
        $stmt = $pdo->query("
            SELECT slug
            FROM categories
            WHERE slug IS NOT NULL AND slug != ''
            ORDER BY id ASC
        ");

        foreach ($stmt->fetchAll() as $category) {
            $url = $base_url . '/category.php?slug=' . rawurlencode($category['slug']);
    ?>
    <url>
        <loc><?= xml($url) ?></loc>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <?php } } catch (PDOException $e) {
        error_log("Sitemap categories error: " . $e->getMessage());
    } ?>

    <!-- الأفلام والمسلسلات -->
    <?php
    try {
        $stmt = $pdo->query("
            SELECT slug, updated_at
            FROM content
            WHERE slug IS NOT NULL
              AND slug != ''
              AND status = 'published'
            ORDER BY id DESC
        ");

        foreach ($stmt->fetchAll() as $item) {
            $url = $base_url . '/watch.php?slug=' . rawurlencode($item['slug']);
    ?>
    <url>
        <loc><?= xml($url) ?></loc>

        <?php if (!empty($item['updated_at'])): ?>
        <lastmod><?= date('c', strtotime($item['updated_at'])) ?></lastmod>
        <?php endif; ?>

        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php } } catch (PDOException $e) {
        error_log("Sitemap content error: " . $e->getMessage());
    } ?>

</urlset>