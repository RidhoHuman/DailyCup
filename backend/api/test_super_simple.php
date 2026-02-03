<?php
// Super minimal test - just one header
header('X-Test-Custom: working');
header('Access-Control-Allow-Origin: *');
echo 'ok';
