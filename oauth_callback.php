<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'oauth_config.php';

$provider = $_GET['provider'] ?? '';
$code = $_GET['code'] ?? '';

if (!$code) {
    die('Authorization code not received');
}

if ($provider === 'google') {
    // Exchange code for access token
    $token_url = 'https://oauth2.googleapis.com/token';
    $post_data = [
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => GOOGLE_REDIRECT_URI
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);
    if (!isset($token_data['access_token'])) {
        die('Failed to get access token');
    }

    // Get user info
    $user_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $user_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_data['access_token']]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $user_response = curl_exec($ch);
    curl_close($ch);

    $user_data = json_decode($user_response, true);
    $email = $user_data['email'];
    $name = $user_data['name'];
    $username = strtolower(str_replace(' ', '_', $name)) . '_google';

} elseif ($provider === 'github') {
    // Exchange code for access token
    $token_url = 'https://github.com/login/oauth/access_token';
    $post_data = [
        'client_id' => GITHUB_CLIENT_ID,
        'client_secret' => GITHUB_CLIENT_SECRET,
        'code' => $code,
        'redirect_uri' => GITHUB_REDIRECT_URI
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);
    if (!isset($token_data['access_token'])) {
        die('Failed to get access token');
    }

    // Get user info
    $user_url = 'https://api.github.com/user';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $user_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token_data['access_token'],
        'User-Agent: Web Sunsal'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $user_response = curl_exec($ch);
    curl_close($ch);

    $user_data = json_decode($user_response, true);
    $username = $user_data['login'];
    $name = $user_data['name'] ?? $username;
    $email = $user_data['email'];

    // If email not public, get from emails endpoint
    if (!$email) {
        $email_url = 'https://api.github.com/user/emails';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $email_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $token_data['access_token'],
            'User-Agent: Web Sunsal'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $email_response = curl_exec($ch);
        curl_close($ch);

        $emails = json_decode($email_response, true);
        foreach ($emails as $e) {
            if ($e['primary']) {
                $email = $e['email'];
                break;
            }
        }
    }

} else {
    die('Invalid provider');
}

// Now, check if user exists by email
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // Login existing user
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = isset($user['role']) ? $user['role'] : 'user';
    
    // Load avatar from database if exists
    if (!empty($user['avatar'])) {
        $_SESSION['avatar'] = $user['avatar'];
    }
    
    redirect('loading.php');
} else {
    // New user - require email verification
    $confirmation_code = rand(100000, 999999); // 6-digit numeric code
    
    // Store temp data in session
    $_SESSION['oauth_temp'] = [
        'provider' => $provider,
        'username' => $username,
        'email' => $email,
        'name' => $name,
        'confirmation_code' => $confirmation_code
    ];
    
    // Send confirmation code
    require_once 'includes/send_mail.php';
    $subject = "Email Verification for OAuth Registration";
    $message = "Your verification code is: " . $confirmation_code . "\n\nPlease enter this code to complete your registration.";
    sendEmail($email, $subject, $message);
    
    flash('info', 'Please check your email for verification code to complete registration.');
    redirect('verify_oauth.php');
}
?>