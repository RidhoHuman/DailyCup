<?php
// Temporary probe to verify central CORS handling for admin folder
require_once __DIR__ . '/../cors.php';
// If cors.php exits for OPTIONS, this won't be reached for OPTIONS requests.
header('Content-Type: application/json');
echo json_encode(["probe" => "ok"]);
