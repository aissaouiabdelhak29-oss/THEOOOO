<?php
declare(strict_types=1);

if (!defined('YUKI_SECURITY_BOOTSTRAPPED')) {
    define('YUKI_SECURITY_BOOTSTRAPPED', true);

    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');

    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    header("Content-Security-Policy: upgrade-insecure-requests; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'");

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443)
        || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    if (session_status() === PHP_SESSION_NONE) {
        session_name('yuki_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        session_start();
    }

    if (empty($_SESSION['_yuki_session_started'])) {
        session_regenerate_id(true);
        $_SESSION['_yuki_session_started'] = time();
    }
    if (!empty($_SESSION['_yuki_last_regen']) && time() - (int)$_SESSION['_yuki_last_regen'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_yuki_last_regen'] = time();
    } elseif (empty($_SESSION['_yuki_last_regen'])) {
        $_SESSION['_yuki_last_regen'] = time();
    }

    function yuki_csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function yuki_validate_csrf(?string $token): bool {
        return is_string($token) && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    function yuki_require_csrf(): void {
        if (!yuki_validate_csrf($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            http_response_code(403);
            exit('Invalid security token.');
        }
    }

    function yuki_safe_int($value, int $min = 0, int $max = PHP_INT_MAX): ?int {
        $v = filter_var($value, FILTER_VALIDATE_INT);
        if ($v === false || $v < $min || $v > $max) return null;
        return (int)$v;
    }

    function yuki_rate_limit(string $key, int $maxAttempts, int $windowSeconds): bool {
        $now = time();
        $bucket = $_SESSION['_rate'][$key] ?? ['start' => $now, 'count' => 0];
        if ($now - $bucket['start'] >= $windowSeconds) $bucket = ['start' => $now, 'count' => 0];
        $bucket['count']++;
        $_SESSION['_rate'][$key] = $bucket;
        return $bucket['count'] <= $maxAttempts;
    }

    function yuki_upload_image(array $file, string $directory, int $maxBytes = 5_000_000): string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed.');
        if (($file['size'] ?? 0) < 1 || $file['size'] > $maxBytes) throw new RuntimeException('Invalid file size.');
        if (!is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Invalid upload.');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        if (!isset($map[$mime])) throw new RuntimeException('Only image files are allowed.');
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) throw new RuntimeException('Invalid image.');
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Upload directory unavailable.');
        $name = bin2hex(random_bytes(16)).'.'.$map[$mime];
        $target = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name;
        if (!move_uploaded_file($file['tmp_name'], $target)) throw new RuntimeException('Could not save upload.');
        @chmod($target, 0644);
        return $name;
    }
}
