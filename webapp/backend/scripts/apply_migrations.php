<?php
require_once __DIR__ . '/../config/database.php';


// Ensure migrations table (use PDO $pdo created by config/database.php)
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (id INT PRIMARY KEY AUTO_INCREMENT, filename VARCHAR(255) UNIQUE, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

// First, apply any idempotent base SQL files (schemas, reference data) so migrations can depend on them.
$sqlDir = __DIR__ . '/../sql';
$baseFiles = glob($sqlDir . '/*.sql');
if ($baseFiles && count($baseFiles) > 0) {
    sort($baseFiles, SORT_STRING);
    foreach ($baseFiles as $bf) {
        $bname = basename($bf);
        echo "Applying base SQL: $bname...\n";
        try {
            $pdo->exec(file_get_contents($bf));
            echo "Applied base SQL: $bname\n";
        } catch (Exception $e) {
            echo "Warning: failed applying base SQL $bname: " . $e->getMessage() . "\n";
        }
    }
}

$migrationDir = __DIR__ . '/../migrations';
$files = glob($migrationDir . '/*.sql');
// Ensure deterministic alphabetical order
sort($files, SORT_STRING);

foreach ($files as $file) {
    $filename = basename($file);
    $check = $pdo->prepare("SELECT COUNT(*) as c FROM migrations WHERE filename = ?");
    $check->execute([$filename]);
    $count = $check->fetchColumn();

    if ($count > 0) continue; // already applied

    echo "Applying $filename...\n";
    $sql = file_get_contents($file);

    try {
        // Some SQL (TRUNCATE/CREATE TABLE/CREATE VIEW/ALTER TABLE/DROP TABLE) is not transaction-safe in MySQL
        // Also detect `CREATE OR REPLACE VIEW` so views aren't executed inside a transaction.
        if (preg_match('/\b(TRUNCATE|CREATE\s+TABLE|CREATE(?:\s+OR\s+REPLACE)?\s+VIEW|ALTER\s+TABLE|DROP\s+TABLE)\b/i', $sql)) {
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
