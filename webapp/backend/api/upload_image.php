<?php
/**
 * Image Upload API
 * 
 * Handles image uploads for products, categories, and other resources
 * Supports: JPG, PNG, WebP
 * Max size: 5MB
 */

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Verify authentication
$authUser = JWT::getUser();
if (!$authUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$userId = $authUser['user_id'] ?? null;
$userRole = $authUser['role'] ?? 'customer';

// Only admin can upload images
if ($userRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

// Parse request
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Handle file upload
    if (!isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No image file provided']);
        exit;
    }
    
    $file = $_FILES['image'];
    $uploadType = $_POST['type'] ?? 'product'; // product, category, user, general
    $resourceId = $_POST['resource_id'] ?? null;
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid file type. Only JPG, PNG, and WebP are allowed'
        ]);
        exit;
    }
    
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'File too large. Maximum size is 5MB'
        ]);
        exit;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Upload failed: ' . $file['error']
        ]);
        exit;
    }
    
    // Create upload directory if not exists
    // Fix: Correct path handling
    $baseUploadDir = __DIR__ . '/../../uploads/';
    if (!file_exists($baseUploadDir)) {
        if (!mkdir($baseUploadDir, 0777, true)) {
             error_log("Failed to create base upload dir: " . $baseUploadDir);
        }
    }

    $uploadDir = $baseUploadDir . $uploadType . 's/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to create upload directory. Check server permissions.'
            ]);
            exit;
        }
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid($uploadType . '_') . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        $error = error_get_last();
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to save file. System error: ' . ($error['message'] ?? 'Unknown'),
            'debug_path' => $filepath
        ]);
        exit;
    }
    
    // Generate URL
    $baseUrl = getenv('APP_URL') ?: '';
    if (empty($baseUrl)) {
        error_log('WARNING: APP_URL not set, using relative path');
    }
    $imageUrl = '/uploads/' . $uploadType . 's/' . $filename;
    $fullUrl = $baseUrl . $imageUrl;
    
    // Update database if resource_id provided
    if ($resourceId) {
        try {
            if ($uploadType === 'product') {
                $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
                $stmt->execute([$imageUrl, $resourceId]);
            } elseif ($uploadType === 'category') {
                $stmt = $pdo->prepare("UPDATE categories SET image = ? WHERE id = ?"); // Assuming column is 'image' for categories too, need verification
                $stmt->execute([$imageUrl, $resourceId]);
            }
        } catch (PDOException $e) {
            error_log("Database update error: " . $e->getMessage());
            // Continue even if DB update fails
        }
    }
    
    // Log upload
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, resource_type, resource_id, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        $logStmt->execute([
            $userId,
            'upload_image',
            $uploadType,
            $resourceId,
            json_encode([
                'filename' => $filename,
                'size' => $file['size'],
                'type' => $file['type']
            ])
        ]);
    } catch (PDOException $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Image uploaded successfully',
        'data' => [
            'filename' => $filename,
            'url' => $imageUrl,
            'full_url' => $fullUrl,
            'size' => $file['size'],
            'type' => $file['type']
        ]
    ]);
    exit;
}

if ($method === 'DELETE') {
    // Delete image
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['filename'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Filename required']);
        exit;
    }
    
    $filename = basename($input['filename']); // Security: prevent path traversal
    $uploadType = $input['type'] ?? 'product';
    $filepath = __DIR__ . '/../../uploads/' . $uploadType . 's/' . $filename;
    
    if (!file_exists($filepath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'File not found']);
        exit;
    }
    
    if (unlink($filepath)) {
        // Log deletion
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, resource_type, details)
                VALUES (?, ?, ?, ?)
            ");
            $logStmt->execute([
                $userId,
                'delete_image',
                $uploadType,
                json_encode(['filename' => $filename])
            ]);
        } catch (PDOException $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete file']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
