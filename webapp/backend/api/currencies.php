<?php
require_once __DIR__ . '/../cors.php';
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';

// Get database connection
$conn = Database::getConnection();

// Get action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Public endpoints (no authentication required)
if ($action === 'list' || $action === 'active' || $action === 'convert') {
    handlePublicRequest($conn, $action);
    exit;
}

// Admin endpoints (require authentication and admin role)
// Verify JWT token
$headers = getallheaders();
$token = null;

// Check for Authorization header (case-insensitive)
foreach ($headers as $key => $value) {
    if (strtolower($key) === 'authorization') {
        $token = str_replace('Bearer ', '', $value);
        break;
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No token provided']);
    exit;
}

// Verify JWT token
$user = JWT::verify($token);
if (!$user || !isset($user['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
    exit;
}

// Check if user is admin
if (($user['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

// Handle admin requests
handleAdminRequest($conn, $action);

// ==================== PUBLIC FUNCTIONS ====================

function handlePublicRequest($conn, $action) {
    switch ($action) {
        case 'list':
            getAllCurrencies($conn);
            break;
        case 'active':
            getActiveCurrencies($conn);
            break;
        case 'convert':
            convertPrice($conn);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function getAllCurrencies($conn) {
    try {
        $query = "SELECT * FROM currencies ORDER BY display_order ASC, name ASC";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $currencies = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $currencies[] = [
                'id' => (int)$row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'symbol' => $row['symbol'],
                'decimal_places' => (int)$row['decimal_places'],
                'is_active' => (bool)$row['is_active'],
                'is_base_currency' => (bool)$row['is_base_currency'],
                'display_order' => (int)$row['display_order']
            ];
        }
        
        echo json_encode(['success' => true, 'currencies' => $currencies]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getActiveCurrencies($conn) {
    try {
        $query = "SELECT * FROM currencies WHERE is_active = TRUE ORDER BY display_order ASC, name ASC";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $currencies = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $currencies[] = [
                'id' => (int)$row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'symbol' => $row['symbol'],
                'decimal_places' => (int)$row['decimal_places'],
                'is_base_currency' => (bool)$row['is_base_currency']
            ];
        }
        
        echo json_encode(['success' => true, 'currencies' => $currencies]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function convertPrice($conn) {
    try {
        $amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
        $from = isset($_GET['from']) ? strtoupper(mysqli_real_escape_string($conn, $_GET['from'])) : '';
        $to = isset($_GET['to']) ? strtoupper(mysqli_real_escape_string($conn, $_GET['to'])) : '';
        
        if ($amount <= 0 || empty($from) || empty($to)) {
            throw new Exception('Invalid parameters');
        }
        
        // If same currency, return original amount
        if ($from === $to) {
            echo json_encode([
                'success' => true,
                'original_amount' => $amount,
                'converted_amount' => $amount,
                'from_currency' => $from,
                'to_currency' => $to,
                'rate' => 1.0
            ]);
            return;
        }
        
        // Get exchange rate
        $query = "SELECT rate, last_updated FROM exchange_rates 
                  WHERE from_currency = '$from' AND to_currency = '$to'";
        $result = mysqli_query($conn, $query);
        
        if (!$result || mysqli_num_rows($result) === 0) {
            throw new Exception('Exchange rate not found');
        }
        
        $rate_data = mysqli_fetch_assoc($result);
        $rate = floatval($rate_data['rate']);
        $converted_amount = $amount * $rate;
        
        // Get target currency decimal places
        $decimal_query = "SELECT decimal_places FROM currencies WHERE code = '$to'";
        $decimal_result = mysqli_query($conn, $decimal_query);
        $decimal_places = 2;
        
        if ($decimal_result && mysqli_num_rows($decimal_result) > 0) {
            $decimal_row = mysqli_fetch_assoc($decimal_result);
            $decimal_places = (int)$decimal_row['decimal_places'];
        }
        
        $converted_amount = round($converted_amount, $decimal_places);
        
        echo json_encode([
            'success' => true,
            'original_amount' => $amount,
            'converted_amount' => $converted_amount,
            'from_currency' => $from,
            'to_currency' => $to,
            'rate' => $rate,
            'last_updated' => $rate_data['last_updated']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ==================== ADMIN FUNCTIONS ====================

function handleAdminRequest($conn, $action) {
    switch ($action) {
        case 'update_status':
            updateCurrencyStatus($conn);
            break;
        case 'update_rate':
            updateExchangeRate($conn);
            break;
        case 'sync_rates':
            syncExchangeRates($conn);
            break;
        case 'get_settings':
            getCurrencySettings($conn);
            break;
        case 'update_settings':
            updateCurrencySettings($conn);
            break;
        case 'add_currency':
            addCurrency($conn);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function updateCurrencyStatus($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $currency_id = isset($data['currency_id']) ? intval($data['currency_id']) : 0;
        $is_active = isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : null;
        
        if ($currency_id <= 0 || $is_active === null) {
            throw new Exception('Invalid parameters');
        }
        
        // Don't allow disabling base currency
        $check_query = "SELECT is_base_currency FROM currencies WHERE id = $currency_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $currency = mysqli_fetch_assoc($check_result);
            if ($currency['is_base_currency'] && !$is_active) {
                throw new Exception('Cannot disable base currency');
            }
        }
        
        $query = "UPDATE currencies SET is_active = $is_active WHERE id = $currency_id";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception(mysqli_error($conn));
        }
        
        echo json_encode(['success' => true, 'message' => 'Currency status updated']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateExchangeRate($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $from = isset($data['from']) ? strtoupper(mysqli_real_escape_string($conn, $data['from'])) : '';
        $to = isset($data['to']) ? strtoupper(mysqli_real_escape_string($conn, $data['to'])) : '';
        $rate = isset($data['rate']) ? floatval($data['rate']) : 0;
        
        if (empty($from) || empty($to) || $rate <= 0) {
            throw new Exception('Invalid parameters');
        }
        
        $query = "INSERT INTO exchange_rates (from_currency, to_currency, rate, source) 
                  VALUES ('$from', '$to', $rate, 'manual')
                  ON DUPLICATE KEY UPDATE rate = $rate, source = 'manual', last_updated = CURRENT_TIMESTAMP";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception(mysqli_error($conn));
        }
        
        echo json_encode(['success' => true, 'message' => 'Exchange rate updated']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function syncExchangeRates($conn) {
    try {
        // Get base currency
        $base_query = "SELECT code FROM currencies WHERE is_base_currency = TRUE LIMIT 1";
        $base_result = mysqli_query($conn, $base_query);
        
        if (!$base_result || mysqli_num_rows($base_result) === 0) {
            throw new Exception('Base currency not found');
        }
        
        $base_currency = mysqli_fetch_assoc($base_result)['code'];
        
        // Get all active currencies
        $currencies_query = "SELECT code FROM currencies WHERE is_active = TRUE";
        $currencies_result = mysqli_query($conn, $currencies_query);
        
        if (!$currencies_result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $active_currencies = [];
        while ($row = mysqli_fetch_assoc($currencies_result)) {
            if ($row['code'] !== $base_currency) {
                $active_currencies[] = $row['code'];
            }
        }
        
        if (empty($active_currencies)) {
            throw new Exception('No active currencies to sync');
        }
        
        // Fetch exchange rates from API
        $api_url = "https://api.exchangerate-api.com/v4/latest/" . $base_currency;
        $response = @file_get_contents($api_url);
        
        if ($response === false) {
            throw new Exception('Failed to fetch exchange rates from API');
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['rates'])) {
            throw new Exception('Invalid API response');
        }
        
        $updated_count = 0;
        $rates = $data['rates'];
        
        // Update rates from base currency to other currencies
        foreach ($active_currencies as $currency) {
            if (isset($rates[$currency])) {
                $rate = floatval($rates[$currency]);
                
                // Update forward rate (base -> currency)
                $query = "INSERT INTO exchange_rates (from_currency, to_currency, rate, source) 
                          VALUES ('$base_currency', '$currency', $rate, 'api')
                          ON DUPLICATE KEY UPDATE rate = $rate, source = 'api', last_updated = CURRENT_TIMESTAMP";
                
                if (mysqli_query($conn, $query)) {
                    $updated_count++;
                    
                    // Update reverse rate (currency -> base)
                    $reverse_rate = 1 / $rate;
                    $reverse_query = "INSERT INTO exchange_rates (from_currency, to_currency, rate, source) 
                                      VALUES ('$currency', '$base_currency', $reverse_rate, 'api')
                                      ON DUPLICATE KEY UPDATE rate = $reverse_rate, source = 'api', last_updated = CURRENT_TIMESTAMP";
                    
                    mysqli_query($conn, $reverse_query);
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Updated $updated_count exchange rates",
            'updated_count' => $updated_count,
            'last_sync' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getCurrencySettings($conn) {
    try {
        $query = "SELECT setting_key, setting_value FROM currency_settings";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $settings = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        echo json_encode(['success' => true, 'settings' => $settings]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateCurrencySettings($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data)) {
            throw new Exception('No settings provided');
        }
        
        foreach ($data as $key => $value) {
            $key_escaped = mysqli_real_escape_string($conn, $key);
            $value_escaped = mysqli_real_escape_string($conn, $value);
            
            $query = "INSERT INTO currency_settings (setting_key, setting_value) 
                      VALUES ('$key_escaped', '$value_escaped')
                      ON DUPLICATE KEY UPDATE setting_value = '$value_escaped', updated_at = CURRENT_TIMESTAMP";
            
            if (!mysqli_query($conn, $query)) {
                throw new Exception(mysqli_error($conn));
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Settings updated']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addCurrency($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $code = isset($data['code']) ? strtoupper(mysqli_real_escape_string($conn, $data['code'])) : '';
        $name = isset($data['name']) ? mysqli_real_escape_string($conn, $data['name']) : '';
        $symbol = isset($data['symbol']) ? mysqli_real_escape_string($conn, $data['symbol']) : '';
        $decimal_places = isset($data['decimal_places']) ? intval($data['decimal_places']) : 2;
        
        if (empty($code) || empty($name) || empty($symbol)) {
            throw new Exception('Missing required fields');
        }
        
        if (strlen($code) !== 3) {
            throw new Exception('Currency code must be 3 characters');
        }
        
        $query = "INSERT INTO currencies (code, name, symbol, decimal_places, is_active) 
                  VALUES ('$code', '$name', '$symbol', $decimal_places, TRUE)";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception(mysqli_error($conn));
        }
        
        $currency_id = mysqli_insert_id($conn);
        
        echo json_encode([
            'success' => true,
            'message' => 'Currency added successfully',
            'currency_id' => $currency_id
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

mysqli_close($conn);
