<?php
// social-login.php
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$provider = $_GET['provider'] ?? '';

// بناء رابط التوجيه الدقيق بناءً على المسار الحالي
$redirectUri = rtrim(SITE_URL, '/') . '/social-callback.php';

if ($provider === 'google') {
    $_SESSION['oauth_provider'] = 'google';
    
    $params = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => $redirectUri,
        'response_type' => 'code',
        'scope'         => 'openid profile email',
        'state'         => generateCsrfToken(),
        'prompt'        => 'select_account'
    ];
    
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    header('Location: ' . $authUrl);
    exit;

} elseif ($provider === 'facebook') {
    $_SESSION['oauth_provider'] = 'facebook';
    
    $params = [
        'client_id'     => FACEBOOK_APP_ID,
        'redirect_uri'  => $redirectUri,
        'state'         => generateCsrfToken(),
        'scope'         => 'public_profile email'
    ];
    
    $authUrl = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    header('Location: ' . $authUrl);
    exit;

} else {
    header('Location: login.php');
    exit;
}