<?php
/**
 * Yuki Authentication & Gamification System
 * Secure session management, user authentication & badges/points engine
 */

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';

// =========================================================================
// 🌐 إعدادات الموقع ومفاتيح التواصل الاجتماعي (OAuth Configuration)
// =========================================================================

// تصحيح رابط الموقع الرئيسي (بدون مجلد فرعي إذا كان الموقع يعمل على الرئيسية مباشرة)
$siteConfig = require __DIR__ . '/config.local.php';
define('SITE_URL', rtrim($siteConfig['site_url'] ?? '', '/'));
define('GOOGLE_CLIENT_ID', (string)($siteConfig['google_client_id'] ?? ''));
define('GOOGLE_CLIENT_SECRET', (string)($siteConfig['google_client_secret'] ?? ''));
define('FACEBOOK_APP_ID', (string)($siteConfig['facebook_app_id'] ?? ''));
define('FACEBOOK_APP_SECRET', (string)($siteConfig['facebook_app_secret'] ?? ''));


/**
 * منطق التحقق التلقائي (Auto-Login) من الكوكيز
 * يعمل فقط إذا لم يكن المستخدم مسجلاً دخوله حالياً
 */
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    // البحث عن التوكن في قاعدة البيانات والتأكد من عدم انتهاء صلاحيته
    $stmt = $pdo->prepare("SELECT id, user_id, token FROM user_tokens WHERE expires_at > NOW() ORDER BY id DESC LIMIT 20");
    $stmt->execute();
    $tokenData = null;
    foreach ($stmt->fetchAll() as $candidate) {
        if (password_verify($token, $candidate['token'])) { $tokenData = $candidate; break; }
    }

    if ($tokenData) {
        // إذا وجدنا التوكن صالحاً، نعيد جلب بيانات المستخدم وتحديث الجلسة
        $stmtUser = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
        $stmtUser->execute([$tokenData['user_id']]);
        $user = $stmtUser->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
        }
    } else {
        // إذا كان التوكن منتهي أو غير موجود، نقوم بحذفه بأمان من المتصفح
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current user data
 */
function getCurrentUser(): ?array {
    global $pdo;

    if (!isLoggedIn()) return null;

    $stmt = $pdo->prepare("SELECT id, username, email, avatar, role, points, status, membership_type, created_at FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Require login
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

/**
 * Require admin
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Generate CSRF token
 */
function generateCsrfToken(): string { return yuki_csrf_token(); }

/**
 * Validate CSRF token
 */
function validateCsrfToken(string $token): bool { return yuki_validate_csrf($token); }

/**
 * Sanitize input
 */
function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with message
 */
function redirect(string $url, string $message = '', string $type = 'info'): void {
    if (preg_match('#^(?:https?:)?//#i', $url) || str_contains($url, "
") || str_contains($url, "
")) $url = 'index.php';
    if ($message !== '') { $_SESSION['flash_message'] = $message; $_SESSION['flash_type'] = $type; }
    header('Location: '.$url, true, 302); exit;
}

/**
 * Get flash message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $msg = ['message' => $_SESSION['flash_message'], 'type' => $_SESSION['flash_type'] ?? 'info'];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $msg;
    }
    return null;
}

/**
 * Logout user
 */
function logout(): void {
    global $pdo;
    
    if (isset($_COOKIE['remember_token'])) {
        $stmt = $pdo->prepare("DELETE FROM user_tokens WHERE expires_at <= NOW()");
        $stmt->execute();
        
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    session_destroy();
}

// =========================================================================
// 🎁 نظام النقاط والشارات والإنجازات (Gamification System Engine)
// =========================================================================

function addPointsToUser(int $userId, int $points, string $action): bool {
    global $pdo;
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE users SET points = GREATEST(0, points + ?) WHERE id = ?");
        $stmt->execute([$points, $userId]);

        $logStmt = $pdo->prepare("INSERT INTO points_log (user_id, points, action) VALUES (?, ?, ?)");
        $logStmt->execute([$userId, $points, $action]);

        $pdo->commit();

        if ($points > 0) {
            checkAndAwardBadges($userId);
        }

        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Gamification Error (addPointsToUser): " . $e->getMessage());
        return false;
    }
}

function checkAndAwardBadges(int $userId): void {
    global $pdo;

    $userStmt = $pdo->prepare("SELECT points FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $userPoints = (int)$userStmt->fetchColumn();

    if ($userPoints >= 100) {
        awardBadge($userId, 'centurion');
    }

    if ($userPoints >= 500) {
        awardBadge($userId, 'cinephile');
    }

    $commentsStmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
    $commentsStmt->execute([$userId]);
    if ((int)$commentsStmt->fetchColumn() >= 5) {
        awardBadge($userId, 'movie_critic');
    }
}

function awardBadge(int $userId, string $badgeCode): bool {
    global $pdo;

    $badgeStmt = $pdo->prepare("SELECT id, points_reward FROM badges WHERE code_name = ? LIMIT 1");
    $badgeStmt->execute([$badgeCode]);
    $badge = $badgeStmt->fetch();

    if ($badge) {
        $insert = $pdo->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)");
        $insert->execute([$userId, $badge['id']]);

        if ($insert.rowCount() > 0 && $badge['points_reward'] > 0) {
            addPointsToUser($userId, (int)$badge['points_reward'], "unlocked_badge_" . $badgeCode);
        }
        return true;
    }
    return false;
}

function getUserBadges(int $userId): array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT b.title, b.description, b.icon, ub.unlocked_at 
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
        ORDER BY ub.unlocked_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll() ?: [];
}