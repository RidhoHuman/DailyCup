<?php
/**
 * Cron Job: Auto-update exchange rates
 * 
 * Add to crontab to run daily:
 * 0 0 * * * php /path/to/DailyCup/webapp/backend/cron/sync_exchange_rates.php
 * 
 * Or run manually: php sync_exchange_rates.php
 */

require_once __DIR__ . '/../config/database.php';

// Log file
$log_file = __DIR__ . '/../../../logs/exchange_rate_sync.log';

function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

try {
    logMessage("Starting exchange rate sync...");
    
    $conn = Database::getConnection();
    
    // Check if auto-update is enabled
    $settings_query = "SELECT setting_value FROM currency_settings WHERE setting_key = 'auto_update_rates'";
    $settings_result = mysqli_query($conn, $settings_query);
    
    if ($settings_result && mysqli_num_rows($settings_result) > 0) {
        $setting = mysqli_fetch_assoc($settings_result);
        if ($setting['setting_value'] !== 'true') {
            logMessage("Auto-update is disabled. Skipping sync.");
            exit(0);
        }
    }
    
    // Get base currency
    $base_query = "SELECT code FROM currencies WHERE is_base_currency = TRUE LIMIT 1";
    $base_result = mysqli_query($conn, $base_query);
    
    if (!$base_result || mysqli_num_rows($base_result) === 0) {
        throw new Exception('Base currency not found');
    }
    
    $base_currency = mysqli_fetch_assoc($base_result)['code'];
    logMessage("Base currency: $base_currency");
    
    // Get all active currencies
    $currencies_query = "SELECT code, name FROM currencies WHERE is_active = TRUE";
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
    
    logMessage("Active currencies: " . implode(', ', $active_currencies));
    
    if (empty($active_currencies)) {
        logMessage("No active currencies to sync. Exiting.");
        exit(0);
    }
    
    // Fetch exchange rates from API
    $api_url = "https://api.exchangerate-api.com/v4/latest/" . $base_currency;
    logMessage("Fetching rates from: $api_url");
    
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
                logMessage("Updated $base_currency -> $currency: $rate");
                $updated_count++;
                
                // Update reverse rate (currency -> base)
                $reverse_rate = 1 / $rate;
                $reverse_query = "INSERT INTO exchange_rates (from_currency, to_currency, rate, source) 
                                  VALUES ('$currency', '$base_currency', $reverse_rate, 'api')
                                  ON DUPLICATE KEY UPDATE rate = $reverse_rate, source = 'api', last_updated = CURRENT_TIMESTAMP";
                
                if (mysqli_query($conn, $reverse_query)) {
                    logMessage("Updated $currency -> $base_currency: $reverse_rate");
                }
            }
        } else {
            logMessage("WARNING: Rate not found for $currency");
        }
    }
    
    // Update cross rates (currency to currency, not through base)
    logMessage("Updating cross rates...");
    $cross_updated = 0;
    
    foreach ($active_currencies as $from_currency) {
        foreach ($active_currencies as $to_currency) {
            if ($from_currency !== $to_currency) {
                if (isset($rates[$from_currency]) && isset($rates[$to_currency])) {
                    // Calculate cross rate: from -> to = (base -> to) / (base -> from)
                    $cross_rate = floatval($rates[$to_currency]) / floatval($rates[$from_currency]);
                    
                    $cross_query = "INSERT INTO exchange_rates (from_currency, to_currency, rate, source) 
                                    VALUES ('$from_currency', '$to_currency', $cross_rate, 'api')
                                    ON DUPLICATE KEY UPDATE rate = $cross_rate, source = 'api', last_updated = CURRENT_TIMESTAMP";
                    
                    if (mysqli_query($conn, $cross_query)) {
                        $cross_updated++;
                    }
                }
            }
        }
    }
    
    logMessage("Cross rates updated: $cross_updated");
    
    logMessage("Sync completed successfully. Updated $updated_count base rates and $cross_updated cross rates.");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    exit(1);
}
