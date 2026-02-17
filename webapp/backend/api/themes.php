<?php
require_once __DIR__ . '/cors.php';
header('Content-Type: application/json');
// CORS handled centrally (cors.php / .htaccess)
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'get_active';
            
            if ($action === 'get_active') {
                // Get currently active theme
                $query = "
                    SELECT * 
                    FROM seasonal_themes
                    WHERE is_active = 1
                    ORDER BY priority DESC, id DESC
                    LIMIT 1
                ";
                
                $result = $pdo->query($query);
                $theme = $result->fetch(PDO::FETCH_ASSOC);
                
                if (!$theme) {
                    // Return empty if no active theme
                    echo json_encode([
                        'success' => true,
                        'theme' => null
                    ]);
                    break;
                }
                
                // Parse JSON fields and map to frontend structure
                if ($theme) {
                    $config = json_decode($theme['theme_config'], true);
                    $theme['colors'] = [
                        'primary' => $config['primary_color'] ?? '#000000',
                        'secondary' => $config['secondary_color'] ?? '#333333',
                        'accent' => $config['accent_color'] ?? '#666666',
                        'navBackground' => $config['background_color'] ?? '#ffffff',
                        'navText' => $config['text_color'] ?? '#000000'
                    ];
                    $theme['images'] = [
                        'banner' => $config['banner_image'] ?? '',
                        'logo' => $config['logo_variant'] ?? '',
                        'background' => ''
                    ];
                    $theme['custom_css'] = $config['css_overrides'] ?? '';
                    unset($theme['theme_config']); // Remove raw JSON field
                }
                
                echo json_encode([
                    'success' => true,
                    'theme' => $theme
                ]);
                
            } elseif ($action === 'get_all') {
                // Get all themes (admin only)
                // Case-insensitive header detection
                $headers = getallheaders();
                $token = null;
                
                foreach ($headers as $key => $value) {
                    if (strtolower($key) === 'authorization') {
                        $token = str_replace('Bearer ', '', $value);
                        break;
                    }
                }
                
                if (!$token) {
                    http_response_code(401);
                    throw new Exception('No token provided');
                }
                
                try {
                    $decoded = JWT::verify($token);
                    
                    if (($decoded['role'] ?? '') !== 'admin') {
                        http_response_code(403);
                        throw new Exception('Admin access required');
                    }
                } catch (Exception $e) {
                    http_response_code(401);
                    throw new Exception('Invalid token: ' . $e->getMessage());
                }
                
                $query = "SELECT * FROM seasonal_themes ORDER BY is_active DESC, name ASC";
                $result = $pdo->query($query);
                
                $themes = [];
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    // Parse JSON and map to frontend structure
                    $config = json_decode($row['theme_config'], true);
                    $row['colors'] = [
                        'primary' => $config['primary_color'] ?? '#000000',
                        'secondary' => $config['secondary_color'] ?? '#333333',
                        'accent' => $config['accent_color'] ?? '#666666',
                        'navBackground' => $config['background_color'] ?? '#ffffff',
                        'navText' => $config['text_color'] ?? '#000000'
                    ];
                    $row['images'] = [
                        'banner' => $config['banner_image'] ?? '',
                        'logo' => $config['logo_variant'] ?? '',
                        'background' => ''
                    ];
                    $row['custom_css'] = $config['css_overrides'] ?? '';
                    unset($row['theme_config']); // Remove raw JSON field
                    $themes[] = $row;
                }
                
                echo json_encode([
                    'success' => true,
                    'themes' => $themes
                ]);
                
            } elseif ($action === 'get_by_id') {
                $id = intval($_GET['id'] ?? 0);
                
                if (!$id) {
                    throw new Exception('Theme ID required');
                }
                
                $stmt = $pdo->prepare("SELECT * FROM seasonal_themes WHERE id = ?");
                $stmt->execute([$id]);
                $theme = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$theme) {
                    throw new Exception('Theme not found');
                }
                
                // Parse JSON and map to frontend structure
                $config = json_decode($theme['theme_config'], true);
                $theme['colors'] = [
                    'primary' => $config['primary_color'] ?? '#000000',
                    'secondary' => $config['secondary_color'] ?? '#333333',
                    'accent' => $config['accent_color'] ?? '#666666',
                    'navBackground' => $config['background_color'] ?? '#ffffff',
                    'navText' => $config['text_color'] ?? '#000000'
                ];
                $theme['images'] = [
                    'banner' => $config['banner_image'] ?? '',
                    'logo' => $config['logo_variant'] ?? '',
                    'background' => ''
                ];
                $theme['custom_css'] = $config['css_overrides'] ?? '';
                unset($theme['theme_config']); // Remove raw JSON field
                
                echo json_encode([
                    'success' => true,
                    'theme' => $theme
                ]);
            }
            break;
            
        case 'POST':
            // Create new theme (admin only)
            // Case-insensitive header detection
            $headers = getallheaders();
            $token = null;
            
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'authorization') {
                    $token = str_replace('Bearer ', '', $value);
                    break;
                }
            }
            
            if (!$token) {
                http_response_code(401);
                throw new Exception('No token provided');
            }
            
            try {
                $decoded = JWT::verify($token);
                
                if (($decoded['role'] ?? '') !== 'admin') {
                    http_response_code(403);
                    throw new Exception('Admin access required');
                }
            } catch (Exception $e) {
                http_response_code(401);
                throw new Exception('Invalid token');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $name = $input['name'] ?? '';
            $slug = $input['slug'] ?? '';
            $description = $input['description'] ?? '';
            $start_date = $input['start_date'] ?? null;
            $end_date = $input['end_date'] ?? null;
            $year_recurring = isset($input['year_recurring']) ? (int)$input['year_recurring'] : 1;
            $priority = isset($input['priority']) ? (int)$input['priority'] : 0;
            
            // Combine config fields into JSON
            $theme_config = json_encode([
                'colors' => $input['colors'] ?? [],
                'images' => $input['images'] ?? [],
                'custom_css' => $input['custom_css'] ?? ''
            ]);
            
            if (!$name || !$slug || !$start_date || !$end_date) {
                throw new Exception('Name, slug, start_date and end_date are required');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO seasonal_themes (name, slug, description, start_date, end_date, year_recurring, priority, theme_config)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $slug, $description, $start_date, $end_date, $year_recurring, $priority, $theme_config]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Theme created successfully',
                    'theme_id' => $pdo->lastInsertId()
                ]);
            } else {
                throw new Exception('Failed to create theme');
            }
            break;
            
        case 'PUT':
            // Update theme or activate theme
            // Case-insensitive header detection
            $headers = getallheaders();
            $token = null;
            
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'authorization') {
                    $token = str_replace('Bearer ', '', $value);
                    break;
                }
            }
            
            if (!$token) {
                http_response_code(401);
                throw new Exception('No token provided');
            }
            
            try {
                $decoded = JWT::verify($token);
                
                if (($decoded['role'] ?? '') !== 'admin') {
                    http_response_code(403);
                    throw new Exception('Admin access required');
                }
            } catch (Exception $e) {
                http_response_code(401);
                throw new Exception('Invalid token');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? 'update';
            
            if ($action === 'activate') {
                $slug = $input['slug'] ?? '';
                
                if (!$slug) {
                    throw new Exception('Theme slug required');
                }
                
                // Check if theme exists
                $stmt = $pdo->prepare("SELECT id FROM seasonal_themes WHERE slug = ?");
                $stmt->execute([$slug]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception('Theme not found');
                }
                
                // Deactivate all themes
                $pdo->query("UPDATE seasonal_themes SET is_active = 0");
                
                // Activate selected theme
                $stmt = $pdo->prepare("UPDATE seasonal_themes SET is_active = 1 WHERE slug = ?");
                $stmt->execute([$slug]);
                
                // Update settings
                $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'active_theme'");
                $stmt->execute([$slug]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Theme activated successfully'
                ]);
                
            } elseif ($action === 'update') {
                $id = intval($input['id'] ?? 0);
                
                if (!$id) {
                    throw new Exception('Theme ID required');
                }
                
                $name = $input['name'] ?? '';
                $description = $input['description'] ?? '';
                $start_date = $input['start_date'] ?? null;
                $end_date = $input['end_date'] ?? null;
                $year_recurring = isset($input['year_recurring']) ? (int)$input['year_recurring'] : 1;
                $priority = isset($input['priority']) ? (int)$input['priority'] : 0;
                
                // Combine config fields into JSON
                $theme_config = json_encode([
                    'colors' => $input['colors'] ?? [],
                    'images' => $input['images'] ?? [],
                    'custom_css' => $input['custom_css'] ?? ''
                ]);
                
                $stmt = $pdo->prepare("
                    UPDATE seasonal_themes 
                    SET name = ?, description = ?, start_date = ?, end_date = ?, 
                        year_recurring = ?, priority = ?, theme_config = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $start_date, $end_date, $year_recurring, $priority, $theme_config, $id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Theme updated successfully'
                    ]);
                } else {
                    throw new Exception('Failed to update theme');
                }
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    // Set appropriate HTTP status code if not already set
    if (http_response_code() === 200) {
        http_response_code(400);
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
