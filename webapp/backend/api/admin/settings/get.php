<?php
/**
 * Admin Settings API - Get settings
 * GET /api/admin/settings/get.php
 * 
 * Query params:
 * - category: Filter by category (contact, business, payment, loyalty, delivery, system)
 * - public_only: 1 to get only public settings
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../../cors.php';

header('Content-Type: application/json');

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if user is admin (for non-public settings)
$publicOnly = isset($_GET['public_only']) && $_GET['public_only'] == '1';

if (!$publicOnly) {
    $user = JWT::getUser();
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
}

try {
    // Build query
    $query = "SELECT 
                setting_key,
                setting_value,
                setting_type,
                setting_category,
                setting_label,
                setting_description,
                is_public,
                updated_at
              FROM admin_settings
              WHERE 1=1";
    
    $params = [];
    
    // Filter by category
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $query .= " AND setting_category = :category";
        $params[':category'] = $_GET['category'];
    }
    
    // Filter by public
    if ($publicOnly) {
        $query .= " AND is_public = 1";
    }
    
    $query .= " ORDER BY setting_category, setting_key";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transform to key-value pairs for easier frontend use
    $settingsMap = [];
    $settingsByCategory = [];
    
    foreach ($settings as $setting) {
        // Convert value based on type
        $value = $setting['setting_value'];
        if ($setting['setting_type'] === 'number') {
            $value = is_numeric($value) ? floatval($value) : $value;
        } elseif ($setting['setting_type'] === 'json' && !empty($value)) {
            $value = json_decode($value, true);
        }
        
        $settingsMap[$setting['setting_key']] = $value;
        
        $category = $setting['setting_category'];
        if (!isset($settingsByCategory[$category])) {
            $settingsByCategory[$category] = [];
        }
        
        $settingsByCategory[$category][] = [
            'key' => $setting['setting_key'],
            'value' => $value,
            'type' => $setting['setting_type'],
            'label' => $setting['setting_label'],
            'description' => $setting['setting_description'],
            'is_public' => (bool)$setting['is_public'],
            'updated_at' => $setting['updated_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'settings' => $settingsMap,
            'by_category' => $settingsByCategory,
            'total' => count($settings)
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Admin settings get error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch settings'
    ]);
}
