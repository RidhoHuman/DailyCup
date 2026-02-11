<!DOCTYPE html>
<html>
<head>
    <title>Test GDPR Fix</title>
</head>
<body>
    <h1>Testing GDPR Requests Page...</h1>
    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "<p>Starting test...</p>";
    
    try {
        require_once __DIR__ . '/../includes/functions.php';
        echo "<p>✓ Functions loaded</p>";
        
        $db = getDB();
        echo "<p>✓ Database connected</p>";
        
        // Test query dengan kolom yang benar
        $stmt = $db->query("
            SELECT 
                gr.*,
                u.name as user_name,
                u.email as user_email
            FROM gdpr_requests gr
            LEFT JOIN users u ON gr.user_id = u.id
            ORDER BY gr.requested_at DESC
            LIMIT 1
        ");
        
        $result = $stmt->fetch();
        echo "<p>✓ Query executed successfully</p>";
        
        if ($result) {
            echo "<p>Found request: " . htmlspecialchars($result['request_type']) . " from " . htmlspecialchars($result['user_name']) . "</p>";
        } else {
            echo "<p>No GDPR requests found (this is OK)</p>";
        }
        
        echo "<hr>";
        echo "<p><strong>✓ All tests passed!</strong></p>";
        echo "<p><a href='gdpr_requests.php'>→ Go to GDPR Requests Page</a></p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    ?>
</body>
</html>
