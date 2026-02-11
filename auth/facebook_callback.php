<?php
/**
 * Facebook OAuth Callback Handler
 * This file handles the response from Facebook after user authorization
 */

require_once __DIR__ . '/../config/oauth_config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check for errors
if (isset($_GET['error'])) {
    header('Location: ' . SITE_URL . '/auth/login.php?error=oauth_cancelled');
    exit;
}

// Verify state parameter
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    header('Location: ' . SITE_URL . '/auth/login.php?error=invalid_state');
    exit;
}

// Get authorization code
$code = $_GET['code'] ?? '';
if (empty($code)) {
    header('Location: ' . SITE_URL . '/auth/login.php?error=no_code');
    exit;
}

try {
    // Exchange code for access token
    $tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token?' . http_build_query([
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'redirect_uri' => FACEBOOK_REDIRECT_URI,
        'code' => $code
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tokenResponse = curl_exec($ch);
    curl_close($ch);
    
    $tokenResult = json_decode($tokenResponse, true);
    
    if (!isset($tokenResult['access_token'])) {
        throw new Exception('Failed to get access token');
    }
    
    // Get user info from Facebook
    $userInfoUrl = 'https://graph.facebook.com/me?fields=id,name,email&access_token=' . $tokenResult['access_token'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $userInfoResponse = curl_exec($ch);
    curl_close($ch);
    
    $userInfo = json_decode($userInfoResponse, true);
    
    if (!isset($userInfo['email'])) {
        throw new Exception('Failed to get user email. Please grant email permission.');
    }
    
    // Check if user exists
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR (oauth_provider = 'facebook' AND oauth_id = ?)");
    $stmt->execute([$userInfo['email'], $userInfo['id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update OAuth info if needed
        if (!$user['oauth_provider']) {
            $stmt = $db->prepare("UPDATE users SET oauth_provider = 'facebook', oauth_id = ? WHERE id = ?");
            $stmt->execute([$userInfo['id'], $user['id']]);
        }
    } else {
        // Create new user
        $stmt = $db->prepare("INSERT INTO users (name, email, oauth_provider, oauth_id, role) VALUES (?, ?, 'facebook', ?, 'customer')");
        $stmt->execute([$userInfo['name'], $userInfo['email'], $userInfo['id']]);
        $user = [
            'id' => $db->lastInsertId(),
            'name' => $userInfo['name'],
            'email' => $userInfo['email'],
            'role' => 'customer'
        ];
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    
    // Redirect to customer page
    header('Location: ' . SITE_URL . '/customer/index.php');
    exit;
    
} catch (Exception $e) {
    error_log('Facebook OAuth Error: ' . $e->getMessage());
    header('Location: ' . SITE_URL . '/auth/login.php?error=oauth_failed');
    exit;
}
