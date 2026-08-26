<?php
/**
 * Yuki Helper Functions
 */

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';

// Helper to convert any insecure URL to HTTPS
if (!function_exists('secure_url')) {
    function secure_url(string $url): string {
        return preg_replace('/^http:/i', 'https:', trim($url));
    }
}

// بدء الجلسة إذا لم تكن مبدأة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * تنظيف المدخلات وحمايتها من الثغرات
 */
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * التحقق مما إذا كان المستخدم مسجلاً للدخول
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

/**
 * حماية الصفحات وتوجيه المستخدم لتسجيل الدخول إن لم يكن مسجلاً
 */
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
}

/**
 * جلب بيانات المستخدم الحالي مع رتبته ونقاط الـ XP
 */
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        global $pdo;
        if (!isLoggedIn()) {
            return null;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? $user : null;
        } catch (PDOException $e) {
            return null;
        }
    }
}

// تعريف المتغير العام للمستخدم الحالي لتسهيل استخدامه في الصفحات
$currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;

/**
 * توليد رمز حماية CSRF لمنع الهجمات
 */
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * التحقق من صحة رمز CSRF المرسل مع النماذج
 */
if (!function_exists('validateCsrfToken')) {
    function validateCsrfToken($token) {
        if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
            return true;
        }
        return false;
    }
}

/**
 * Get all categories
 */
function getCategories(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get all genres
 */
function getGenres(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM genres ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get content by slug
 */
function getContentBySlug(string $slug): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT c.*, cat.name as category_name, g.name as genre_name 
                           FROM content c 
                           LEFT JOIN categories cat ON c.category_id = cat.id 
                           LEFT JOIN genres g ON c.genre_id = g.id 
                           WHERE c.slug = ? AND c.status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/**
 * Get content by ID
 */
function getContentById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT c.*, cat.name as category_name, g.name as genre_name 
                           FROM content c 
                           LEFT JOIN categories cat ON c.category_id = cat.id 
                           LEFT JOIN genres g ON c.genre_id = g.id 
                           WHERE c.id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get video servers for content with HTTPS enforcing
 */
function getVideoServers(int $contentId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM video_servers WHERE content_id = ? ORDER BY is_default DESC, id ASC");
    $stmt->execute([$contentId]);
    $servers = $stmt->fetchAll();
    foreach ($servers as &$server) {
        if (isset($server['url'])) {
            $server['url'] = secure_url($server['url']);
        }
    }
    return $servers;
}

/**
 * Get episode servers with HTTPS enforcing
 */
function getEpisodeServers(int $episodeId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM episode_servers WHERE episode_id = ? ORDER BY is_default DESC, id ASC");
    $stmt->execute([$episodeId]);
    $servers = $stmt->fetchAll();
    foreach ($servers as &$server) {
        if (isset($server['url'])) {
            $server['url'] = secure_url($server['url']);
        }
    }
    return $servers;
}

/**
 * Get episodes for series
 */
function getEpisodes(int $seriesId, int $season = 0): array {
    global $pdo;
    if ($season > 0) {
        $stmt = $pdo->prepare("SELECT e.*, 
                               (SELECT COUNT(*) FROM episode_servers WHERE episode_id = e.id) as server_count 
                               FROM episodes e WHERE e.series_id = ? AND e.season = ? ORDER BY e.episode_number");
        $stmt->execute([$seriesId, $season]);
    } else {
        $stmt = $pdo->prepare("SELECT e.*, 
                               (SELECT COUNT(*) FROM episode_servers WHERE episode_id = e.id) as server_count 
                               FROM episodes e WHERE e.series_id = ? ORDER BY e.season, e.episode_number");
        $stmt->execute([$seriesId]);
    }
    return $stmt->fetchAll();
}

/**
 * Get seasons for series
 */
function getSeasons(int $seriesId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT DISTINCT season FROM episodes WHERE series_id = ? ORDER BY season");
    $stmt->execute([$seriesId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get featured content
 */
function getFeaturedContent(int $limit = 6): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT c.*, cat.name as category_name, g.name as genre_name 
                           FROM content c 
                           LEFT JOIN categories cat ON c.category_id = cat.id 
                           LEFT JOIN genres g ON c.genre_id = g.id 
                           WHERE c.featured = 1 AND c.status = 'published' 
                           ORDER BY c.created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get latest content
 */
function getLatestContent(string $type = '', int $limit = 12): array {
    global $pdo;
    $sql = "SELECT c.*, cat.name as category_name, g.name as genre_name 
            FROM content c 
            LEFT JOIN categories cat ON c.category_id = cat.id 
            LEFT JOIN genres g ON c.genre_id = g.id 
            WHERE c.status = 'published'";
    $params = [];
    if (!empty($type)) {
        $sql .= " AND c.type = ?";
        $params[] = $type;
    }
    $sql .= " ORDER BY c.created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get popular content
 */
function getPopularContent(string $type = '', int $limit = 12): array {
    global $pdo;
    $sql = "SELECT c.*, cat.name as category_name, g.name as genre_name 
            FROM content c 
            LEFT JOIN categories cat ON c.category_id = cat.id 
            LEFT JOIN genres g ON c.genre_id = g.id 
            WHERE c.status = 'published'";
    $params = [];
    if (!empty($type)) {
        $sql .= " AND c.type = ?";
        $params[] = $type;
    }
    $sql .= " ORDER BY c.views DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Search content
 */
function searchContent(string $query, string $type = '', int $limit = 20): array {
    global $pdo;
    $search = "%$query%";
    $sql = "SELECT c.*, cat.name as category_name, g.name as genre_name 
            FROM content c 
            LEFT JOIN categories cat ON c.category_id = cat.id 
            LEFT JOIN genres g ON c.genre_id = g.id 
            WHERE c.status = 'published' AND (c.title LIKE ? OR c.description LIKE ?)";
    $params = [$search, $search];
    if (!empty($type)) {
        $sql .= " AND c.type = ?";
        $params[] = $type;
    }
    $sql .= " ORDER BY c.created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get user favorites
 */
function getUserFavorites(int $userId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT c.* FROM content c 
                           JOIN favorites f ON c.id = f.content_id 
                           WHERE f.user_id = ? AND c.status = 'published' 
                           ORDER BY f.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Check if content is in favorites
 */
function isFavorite(int $userId, int $contentId): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND content_id = ? LIMIT 1");
    $stmt->execute([$userId, $contentId]);
    return (bool)$stmt->fetch();
}

/**
 * Add to favorites
 */
function addFavorite(int $userId, int $contentId): bool {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, content_id) VALUES (?, ?)");
        return $stmt->execute([$userId, $contentId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Remove from favorites
 */
function removeFavorite(int $userId, int $contentId): bool {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND content_id = ?");
    return $stmt->execute([$userId, $contentId]);
}

/**
 * Increment views
 */
function incrementViews(int $contentId): void {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE content SET views = views + 1 WHERE id = ?");
    $stmt->execute([$contentId]);
}

/**
 * Get related content
 */
function getRelatedContent(int $contentId, string $type, int $limit = 6): array {
    global $pdo;
    $content = getContentById($contentId);
    if (!$content) return [];

    $stmt = $pdo->prepare("SELECT c.*, cat.name as category_name, g.name as genre_name 
                           FROM content c 
                           LEFT JOIN categories cat ON c.category_id = cat.id 
                           LEFT JOIN genres g ON c.genre_id = g.id 
                           WHERE c.id != ? AND c.type = ? AND c.status = 'published' 
                           AND (c.genre_id = ? OR c.category_id = ?)
                           ORDER BY RAND() LIMIT ?");
    $stmt->execute([$contentId, $type, $content['genre_id'] ?? 0, $content['category_id'] ?? 0, $limit]);
    return $stmt->fetchAll();
}

/**
 * Get setting value
 */
function getSetting(string $key, string $default = ''): string {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

/**
 * Generate slug
 */
function generateSlug(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'item-' . time() : $text;
}

/**
 * Format duration
 */
function formatDuration(int $minutes): string {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($hours > 0) {
        return $hours . 'h ' . $mins . 'm';
    }
    return $mins . 'm';
}

/**
 * Get server display name
 */
function getServerName(string $server): string {
    $names = [
        'streamhg' => 'StreamHG',
        'earnvids' => 'EarnVids',
        'mixdrop' => 'Mixdrop',
        'doodstream' => 'DoodStream'
    ];
    return $names[$server] ?? $server;
}

/**
 * Get server icon
 */
function getServerIcon(string $server): string {
    $icons = [
        'streamhg' => 'fa-server',
        'earnvids' => 'fa-play-circle',
        'mixdrop' => 'fa-cloud',
        'doodstream' => 'fa-video'
    ];
    return $icons[$server] ?? 'fa-play';
}

/**
 * Pagination
 */
function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Get content by category
 */
function getContentByCategory(int $categoryId, string $type = '', int $limit = 12): array {
    global $pdo;
    $sql = "SELECT c.*, cat.name as category_name, g.name as genre_name 
            FROM content c 
            LEFT JOIN categories cat ON c.category_id = cat.id 
            LEFT JOIN genres g ON c.genre_id = g.id 
            WHERE c.category_id = ? AND c.status = 'published'";
    $params = [$categoryId];
    if (!empty($type)) {
        $sql .= " AND c.type = ?";
        $params[] = $type;
    }
    $sql .= " ORDER BY c.created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get content by genre
 */
function getContentByGenre(int $genreId, string $type = '', int $limit = 12): array {
    global $pdo;
    $sql = "SELECT c.*, cat.name as category_name, g.name as genre_name 
            FROM content c 
            LEFT JOIN categories cat ON c.category_id = cat.id 
            LEFT JOIN genres g ON c.genre_id = g.id 
            WHERE c.genre_id = ? AND c.status = 'published'";
    $params = [$genreId];
    if (!empty($type)) {
        $sql .= " AND c.type = ?";
        $params[] = $type;
    }
    $sql .= " ORDER BY c.created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/* ==========================================================================
   Yuki System Functions (XP & User Leveling System)
   ========================================================================== */

/**
 * Add XP to user safely
 */
function addXpToUser(int $userId, int $amount, string $actionType = ''): bool {
    global $pdo;
    if (!$userId || $amount <= 0) return false;

    try {
        $stmt = $pdo->prepare("UPDATE users SET xp = COALESCE(xp, 0) + ? WHERE id = ?");
        $success = $stmt->execute([$amount, $userId]);

        updateUserLevel($userId);

        return $success;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Calculate Level based on XP (Every 100 XP = 1 Level)
 */
function calculateLevel(int $xp): int {
    return max(1, floor($xp / 100) + 1);
}

/**
 * Sync user level in DB
 */
function updateUserLevel(int $userId): bool {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT xp FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $xp = (int)$stmt->fetchColumn();

        $newLevel = calculateLevel($xp);

        $updateStmt = $pdo->prepare("UPDATE users SET level = ? WHERE id = ?");
        return $updateStmt->execute([$newLevel, $userId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get user rank / badge title based on XP or Level
 */
function getUserRanks(int $xp = 0): array {
    $level = calculateLevel($xp);
    
    if ($level >= 50) {
        return ['title' => 'أسطورة Yuki', 'icon' => 'fa-dragon', 'color' => '#ff0055'];
    } elseif ($level >= 20) {
        return ['title' => 'مشاهد محترف', 'icon' => 'fa-fire', 'color' => '#ff9100'];
    } elseif ($level >= 10) {
        return ['title' => 'متابع نشيط', 'icon' => 'fa-star', 'color' => '#00e5ff'];
    } elseif ($level >= 5) {
        return ['title' => 'عضو متقدم', 'icon' => 'fa-user-check', 'color' => '#00ff66'];
    } else {
        return ['title' => 'عضو جديد', 'icon' => 'fa-user', 'color' => '#a0a0b0'];
    }
}

/**
 * منح وسام أو إنجاز للمستخدم برمجياً من قاعدة البيانات
 */
if (!function_exists('awardBadgeToUser')) {
    function awardBadgeToUser(int $userId, int $badgeId): bool {
        global $pdo;
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, rank_id) VALUES (?, ?)");
            return $stmt->execute([$userId, $badgeId]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

/**
 * جلب الأوسمة المفتوحة للمستخدم من قاعدة البيانات
 */
if (!function_exists('getUserBadges')) {
    function getUserBadges(int $userId): array {
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                SELECT b.*, ua.unlocked_at 
                FROM user_achievements ua
                JOIN badges b ON ua.rank_id = b.id
                WHERE ua.user_id = ?
                ORDER BY ua.unlocked_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}