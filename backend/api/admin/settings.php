<?php
/**
 * Admin Settings API
 * GET: Fetch settings (auto-create defaults if not exists)
 * PUT: Update settings
 * 
 * NOTE: CORS is handled by .htaccess in /backend/api/
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Check if settings exist
            $stmt = $pdo->query("SELECT * FROM admin_settings WHERE id = 1 LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings) {
                // Create default settings
                $stmt = $pdo->prepare("INSERT INTO admin_settings (id, store_name, store_email, store_phone, store_address, tax_rate, delivery_fee, min_order, enable_notifications, enable_inventory_alerts) VALUES (1, 'DailyCup Coffee', 'info@dailycup.com', '+62 812 3456 7890', 'Jl. Sudirman No. 123, Jakarta', 11.00, 15000, 50000, 1, 1)");
                $stmt->execute();
                
                // Fetch the newly created settings
                $stmt = $pdo->query("SELECT * FROM admin_settings WHERE id = 1 LIMIT 1");
                $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => (int)$settings['id'],
                    'store_name' => $settings['store_name'],
                    'store_email' => $settings['store_email'],
                    'store_phone' => $settings['store_phone'],
                    'store_address' => $settings['store_address'],
                    'tax_rate' => (float)$settings['tax_rate'],
                    'delivery_fee' => (int)$settings['delivery_fee'],
                    'min_order' => (int)$settings['min_order'],
                    'enable_notifications' => (bool)$settings['enable_notifications'],
                    'enable_inventory_alerts' => (bool)$settings['enable_inventory_alerts'],
                    'created_at' => $settings['created_at'],
                    'updated_at' => $settings['updated_at']
                ]
            ]);
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
                exit();
            }
            
            // Build update query dynamically based on provided fields
            $allowedFields = ['store_name', 'store_email', 'store_phone', 'store_address', 'tax_rate', 'delivery_fee', 'min_order', 'enable_notifications', 'enable_inventory_alerts'];
            $updateFields = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $input[$field];
                }
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
                exit();
            }
            
            $updateFields[] = "updated_at = NOW()";
            $sql = "UPDATE admin_settings SET " . implode(', ', $updateFields) . " WHERE id = 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Fetch updated settings
            $stmt = $pdo->query("SELECT * FROM admin_settings WHERE id = 1 LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => [
                    'id' => (int)$settings['id'],
                    'store_name' => $settings['store_name'],
                    'store_email' => $settings['store_email'],
                    'store_phone' => $settings['store_phone'],
                    'store_address' => $settings['store_address'],
                    'tax_rate' => (float)$settings['tax_rate'],
                    'delivery_fee' => (int)$settings['delivery_fee'],
                    'min_order' => (int)$settings['min_order'],
                    'enable_notifications' => (bool)$settings['enable_notifications'],
                    'enable_inventory_alerts' => (bool)$settings['enable_inventory_alerts'],
                    'created_at' => $settings['created_at'],
                    'updated_at' => $settings['updated_at']
                ]
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
