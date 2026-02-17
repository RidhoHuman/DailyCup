<?php
require_once __DIR__ . '/cors.php';
echo "=== DEBUG PATH ===\n\n";

echo "Current file: " . __FILE__ . "\n";
echo "Current dir: " . __DIR__ . "\n\n";

// Test berbagai path
$paths = [
    __DIR__ . '/../../../../../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/vendor/autoload.php',
];

foreach ($paths as $i => $path) {
    echo "Path $i: $path\n";
    echo "  Exists: " . (file_exists($path) ? "✅ YES" : "❌ NO") . "\n";
    echo "  Realpath: " . realpath($path) . "\n\n";
}

echo "=== EXPECTED PATH ===\n";
echo "C:\\laragon\\www\\DailyCup\\vendor\\autoload.php\n\n";

?>
