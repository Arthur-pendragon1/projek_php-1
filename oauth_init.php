<?php
session_start();
require_once 'oauth_config.php';

$provider = $_GET['provider'] ?? '';

if ($provider === 'google') {
    $url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'scope' => 'openid https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
        'response_type' => 'code',
        'state' => bin2hex(random_bytes(16))
    ]);
    header('Location: ' . $url);
    exit;
} elseif ($provider === 'github') {
    $url = 'https://github.com/login/oauth/authorize?' . http_build_query([
        'client_id' => GITHUB_CLIENT_ID,
        'redirect_uri' => GITHUB_REDIRECT_URI,
        'scope' => 'user:email',
        'state' => bin2hex(random_bytes(16))
    ]);
    header('Location: ' . $url);
    exit;
} else {
    die('Invalid provider');
}
?>