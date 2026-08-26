<?php
// social-callback.php
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$provider = $_SESSION['oauth_provider'] ?? '';
$code     = $_GET['code'] ?? '';

if (empty($code) || empty($provider)) {
    header('Location: login.php?error=oauth_failed');
    exit;
}

// دالة cURL موحدة وموثوقة لاستدعاء البيانات والتغلب على قيود الاستضافة
function makeCurlRequest($url, $postData = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    
    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($postData) ? http_build_query($postData) : $postData);
    }
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('cURL Error: ' . $error);
    }
    curl_close($ch);
    return $response;
}

try {
    $email    = '';
    $name     = '';
    $socialId = '';
    $redirectUri = rtrim(SITE_URL, '/') . '/social-callback.php';

    if ($provider === 'google') {
        // 1. تبديل الكود للحصول على Access Token من Google عبر cURL
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $postData = [
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code'
        ];

        $response = makeCurlRequest($tokenUrl, $postData);
        $tokenData = json_decode($response, true);

        if (empty($tokenData['access_token'])) {
            throw new Exception('Google Token Error: ' . ($tokenData['error_description'] ?? 'Failed to get access token'));
        }

        // 2. جلب معلومات المستخدم من Google عبر cURL
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . $tokenData['access_token'];
        $userInfoResponse = makeCurlRequest($userInfoUrl);
        $userInfo = json_decode($userInfoResponse, true);

        $email    = $userInfo['email'] ?? '';
        $name     = $userInfo['name'] ?? 'Google User';
        $socialId = $userInfo['sub'] ?? '';

    } elseif ($provider === 'facebook') {
        // 1. تبديل الكود للحصول على Access Token من Facebook عبر cURL
        $tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token?' . http_build_query([
            'client_id'     => FACEBOOK_APP_ID,
            'client_secret' => FACEBOOK_APP_SECRET,
            'redirect_uri'  => $redirectUri,
            'code'          => $code
        ]);

        $response = makeCurlRequest($tokenUrl);
        $tokenData = json_decode($response, true);

        if (empty($tokenData['access_token'])) {
            throw new Exception('Facebook Token Error: ' . ($tokenData['error']['message'] ?? 'Failed to get access token'));
        }

        // 2. جلب معلومات المستخدم من Facebook عبر cURL
        $userInfoUrl = 'https://graph.facebook.com/v18.0/me?' . http_build_query([
            'fields'       => 'id,name,email',
            'access_token' => $tokenData['access_token']
        ]);
        
        $userInfoResponse = makeCurlRequest($userInfoUrl);
        $userInfo = json_decode($userInfoResponse, true);

        $email    = $userInfo['email'] ?? ($userInfo['id'] . '@facebook.com');
        $name     = $userInfo['name'] ?? 'Facebook User';
        $socialId = $userInfo['id'] ?? '';
    }

    if (empty($email)) {
        throw new Exception('Email is required to complete authentication.');
    }

    // 3. البحث عن المستخدم بالبريد الإلكتروني في قاعدة البيانات
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $randomPassword = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
        
        $baseName = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        if (empty($baseName)) {
            $baseName = 'user';
        }
        
        $finalUsername = $baseName;
        while (true) {
            $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $checkUser->execute([$finalUsername]);
            if (!$checkUser->fetch()) {
                break;
            }
                $finalUsername = $baseName . '_' . rand(100, 9999);
        }

        $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
        $insertStmt->execute([$finalUsername, $email, $randomPassword]);
        
        $userId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
    }

    // 4. تسجيل الدخول وحفظ بيانات الجلسة
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['user_role'] = $user['role'] ?? 'user';

    unset($_SESSION['oauth_provider']);
    header('Location: index.php');
    exit;

} catch (Exception $e) {
    die('Social Login Error: ' . $e->getMessage());
}