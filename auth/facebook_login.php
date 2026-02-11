<?php
/**
 * Facebook OAuth Login Initiator
 * This file redirects users to Facebook's OAuth consent screen
 */

require_once __DIR__ . '/../config/oauth_config.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/customer/index.php');
    exit;
}

// Generate state parameter for security
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Build Facebook OAuth URL
$params = [
    'client_id' => FACEBOOK_APP_ID,
    'redirect_uri' => FACEBOOK_REDIRECT_URI,
    'state' => $state,
    'scope' => 'email,public_profile'
];

$authUrl = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);

// Redirect to Facebook
header('Location: ' . $authUrl);
exit;
