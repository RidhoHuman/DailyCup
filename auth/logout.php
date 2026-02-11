<?php
require_once __DIR__ . '/../includes/functions.php';

// Destroy session
session_destroy();

// Redirect to home
header('Location: ' . SITE_URL . '/index.php?logout=1');
exit;
