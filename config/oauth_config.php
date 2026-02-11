<?php
/**
 * OAuth Configuration for Google and Facebook Login
 * 
 * IMPORTANT: Replace these values with your actual OAuth credentials
 * See docs/PANDUAN_OAUTH.md for instructions on how to obtain these credentials
 */

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', 'http://localhost/dailycup/auth/google_callback.php');

// Facebook OAuth Configuration
define('FACEBOOK_APP_ID', 'YOUR_FACEBOOK_APP_ID_HERE');
define('FACEBOOK_APP_SECRET', 'YOUR_FACEBOOK_APP_SECRET_HERE');
define('FACEBOOK_REDIRECT_URI', 'http://localhost/dailycup/auth/facebook_callback.php');

// OAuth Scopes
define('GOOGLE_SCOPES', ['email', 'profile']);
define('FACEBOOK_SCOPES', ['email', 'public_profile']);
