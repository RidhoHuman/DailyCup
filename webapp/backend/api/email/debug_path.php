<?php
echo "=== DEBUG PATH dari folder email/ ===\n\n";

echo "Current file: " . __FILE__ . "\n";
echo "Current dir: " . __DIR__ . "\n\n";

// __DIR__ = C:\laragon\www\DailyCup\webapp\backend\api\email
// Target = C:\laragon\www\DailyCup\vendor\autoload.php

// Need to go up: email -> api -> backend -> webapp -> DailyCup (4 levels)

$paths = [
    __DIR__ . '/../../vendor/autoload.php',    // api/vendor - NO
    __DIR__ . '/../../../vendor/autoload.php', // backend/vendor - NO
    __DIR__ . '/../../../../vendor/autoload.php', // webapp/vendor - NO
    'C:/laragon/www/DailyCup/vendor/autoload.php', // ABSOLUTE
];

foreach ($paths as $i => $path) {
    echo "Path $i: $path\n";
    echo "  Exists: " . (file_exists($path) ? "✅ YES" : "❌ NO") . "\n\n";
}

echo "=== SOLUTION ===\n";
echo "Use absolute path: C:/laragon/www/DailyCup/vendor/autoload.php\n";
?>
