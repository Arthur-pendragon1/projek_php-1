<?php
// oauth_config.php
// Replace with your actual credentials

// Google OAuth
// Get from: https://console.developers.google.com/
// Create project, enable Google Sign-In API and People API, create OAuth 2.0 client ID
define('GOOGLE_CLIENT_ID', '1002717791018-jeatl10eaao9jvdomch4kdmcibdn0k0d.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// GitHub OAuth
// Get from: https://github.com/settings/applications/new
// Create OAuth App
define('GITHUB_CLIENT_ID', 'YOUR_GITHUB_CLIENT_ID');
define('GITHUB_CLIENT_SECRET', 'YOUR_GITHUB_CLIENT_SECRET');

// Check your XAMPP port (usually 80 or 8080)
// If not 80, change to http://localhost:PORT/web_sunsal/
define('BASE_URL', 'http://localhost/web_sunsal/');
define('GOOGLE_REDIRECT_URI', BASE_URL . 'oauth_callback.php?provider=google');
define('GITHUB_REDIRECT_URI', BASE_URL . 'oauth_callback.php?provider=github');
?>