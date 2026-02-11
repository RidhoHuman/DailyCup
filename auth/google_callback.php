<?php
/**
 * Google OAuth Callback Handler
 * This file handles the response from Google after user authorization
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
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tokenResponse = curl_exec($ch);
    curl_close($ch);
    
    $tokenResult = json_decode($tokenResponse, true);
    
    if (!isset($tokenResult['access_token'])) {
        throw new Exception('Failed to get access token');
    }
    
    // Get user info from Google
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenResult['access_token']]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $userInfoResponse = curl_exec($ch);
    curl_close($ch);
    
    $userInfo = json_decode($userInfoResponse, true);
    
    if (!isset($userInfo['email'])) {
        throw new Exception('Failed to get user info');
    }
    
    // Check if user exists
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR (oauth_provider = 'google' AND oauth_id = ?)");
    $stmt->execute([$userInfo['email'], $userInfo['id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update OAuth info if needed
        if (!$user['oauth_provider']) {
            $stmt = $db->prepare("UPDATE users SET oauth_provider = 'google', oauth_id = ? WHERE id = ?");
            $stmt->execute([$userInfo['id'], $user['id']]);
        }
    } else {
        // Create new user
        $stmt = $db->prepare("INSERT INTO users (name, email, oauth_provider, oauth_id, role) VALUES (?, ?, 'google', ?, 'customer')");
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
    error_log('Google OAuth Error: ' . $e->getMessage());
    header('Location: ' . SITE_URL . '/auth/login.php?error=oauth_failed');
    exit;
}
