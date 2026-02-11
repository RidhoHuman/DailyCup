<?php
require_once __DIR__ . '/../config/database.php';


// Ensure migrations table (use PDO $pdo created by config/database.php)
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (id INT PRIMARY KEY AUTO_INCREMENT, filename VARCHAR(255) UNIQUE, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

$migrationDir = __DIR__ . '/../migrations';
$files = glob($migrationDir . '/*.sql');

foreach ($files as $file) {
    $filename = basename($file);
    $check = $pdo->prepare("SELECT COUNT(*) as c FROM migrations WHERE filename = ?");
    $check->execute([$filename]);
    $count = $check->fetchColumn();

    if ($count > 0) continue; // already applied

    echo "Applying $filename...\n";
    $sql = file_get_contents($file);

    try {
        // Some SQL (TRUNCATE/CREATE VIEW/CREATE TABLE/ALTER TABLE) is not transaction-safe in MySQL
        if (preg_match('/\b(TRUNCATE|CREATE\s+TABLE|CREATE\s+VIEW|ALTER\s+TABLE|DROP\s+TABLE)\b/i', $sql)) {
            $pdo->exec($sql);
            $insert = $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)");
            $insert->execute([$filename]);
            echo "Applied (non-transactional): $filename\n";
        } else {
            $pdo->beginTransaction();
            $pdo->exec($sql);
            $insert = $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)");
            $insert->execute([$filename]);
            $pdo->commit();
            echo "Applied: $filename\n";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "Failed applying $filename: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
