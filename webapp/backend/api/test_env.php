<?php
require_once __DIR__ . '/config.php';
$val = getenv('XENDIT_SECRET_KEY');
echo $val ? 'SET' : 'NOT SET';