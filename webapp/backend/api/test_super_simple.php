<?php
// Super minimal test - just one header
header('X-Test-Custom: working');
// CORS handled centrally (cors.php / .htaccess)
echo 'ok';
