<?php
/**
 * Currency Helper Functions
 * Helper functions for multi-currency support in legacy PHP pages
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get all active currencies
 */
function getActiveCurrencies() {
    $conn = Database::getConnection();
    
    $query = "SELECT * FROM currencies WHERE is_active = TRUE ORDER BY display_order ASC";
    $result = mysqli_query($conn, $query);
    
    $currencies = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $currencies[] = $row;
        }
    }
    
    return $currencies;
}

/**
 * Get base currency
 */
function getBaseCurrency() {
    $conn = Database::getConnection();
    
    $query = "SELECT * FROM currencies WHERE is_base_currency = TRUE LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    $currency = null;
    if ($result && mysqli_num_rows($result) > 0) {
        $currency = mysqli_fetch_assoc($result);
    }
    
    return $currency;
}

/**
 * Get current selected currency from session/cookie
 */
function getCurrentCurrency() {
    // Check cookie first
    if (isset($_COOKIE['selected_currency'])) {
        $code = $_COOKIE['selected_currency'];
        $currency = getCurrencyByCode($code);
        if ($currency && $currency['is_active']) {
            return $currency;
        }
    }
    
    // Default to base currency
    return getBaseCurrency();
}

/**
 * Get currency by code
 */
function getCurrencyByCode($code) {
    $conn = Database::getConnection();
    
    $code = mysqli_real_escape_string($conn, strtoupper($code));
    $query = "SELECT * FROM currencies WHERE code = '$code' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    $currency = null;
    if ($result && mysqli_num_rows($result) > 0) {
        $currency = mysqli_fetch_assoc($result);
    }
    
    return $currency;
}

/**
 * Convert price from one currency to another
 */
function convertPrice($amount, $fromCurrency = null, $toCurrency = null) {
    $conn = Database::getConnection();
    
    // Default from currency is base currency
    if (!$fromCurrency) {
        $base = getBaseCurrency();
        $fromCurrency = $base['code'];
    }
    
    // Default to currency is current selected currency
    if (!$toCurrency) {
        $current = getCurrentCurrency();
        $toCurrency = $current['code'];
    }
    
    // If same currency, no conversion needed
    if ($fromCurrency === $toCurrency) {
        return $amount;
    }
    
    // Get exchange rate
    $from = mysqli_real_escape_string($conn, $fromCurrency);
    $to = mysqli_real_escape_string($conn, $toCurrency);
    
    $query = "SELECT rate FROM exchange_rates 
              WHERE from_currency = '$from' AND to_currency = '$to' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $rate = floatval($row['rate']);
        
        return $amount * $rate;
    }
    
    return $amount;
}

/**
 * Format price with currency symbol
 */
function formatPrice($amount, $fromCurrency = null, $includeSymbol = true) {
    $current = getCurrentCurrency();
    $converted = convertPrice($amount, $fromCurrency, $current['code']);
    
    $decimals = intval($current['decimal_places']);
    $formatted = number_format($converted, $decimals, ',', '.');
    
    if ($includeSymbol) {
        return $current['symbol'] . $formatted;
    }
    
    return $formatted;
}

/**
 * Render currency selector HTML
 */
function renderCurrencySelector() {
    $currencies = getActiveCurrencies();
    $current = getCurrentCurrency();
    
    // Don't show if only one currency
    if (count($currencies) <= 1) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="currency-selector-wrapper" style="position: relative; display: inline-block;">
        <button type="button" class="currency-selector-btn" 
                style="display: flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0.75rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer; transition: background-color 0.2s;"
                onclick="document.getElementById('currency-dropdown').classList.toggle('hidden')">
            <span style="font-size: 0.875rem; font-weight: 500;"><?php echo htmlspecialchars($current['symbol']); ?></span>
            <span style="font-size: 0.875rem; font-weight: 600;"><?php echo htmlspecialchars($current['code']); ?></span>
            <svg style="width: 1rem; height: 1rem; color: #4b5563;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        
        <div id="currency-dropdown" class="hidden" 
             style="position: absolute; right: 0; margin-top: 0.5rem; width: 14rem; background: white; border-radius: 0.5rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; z-index: 50;">
            
            <div style="padding: 0.5rem; border-bottom: 1px solid #e5e7eb;">
                <span style="font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; padding: 0 0.5rem;">
                    Select Currency
                </span>
            </div>
            
            <div style="max-height: 16rem; overflow-y: auto; padding: 0.25rem;">
                <?php foreach ($currencies as $currency): ?>
                    <a href="?currency=<?php echo htmlspecialchars($currency['code']); ?>" 
                       onclick="setCurrency('<?php echo htmlspecialchars($currency['code']); ?>'); return false;"
                       style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 1rem; font-size: 0.875rem; text-decoration: none; color: inherit; transition: background-color 0.2s; <?php echo $currency['code'] === $current['code'] ? 'background-color: #eff6ff;' : ''; ?>"
                       onmouseover="this.style.backgroundColor='#f9fafb'" 
                       onmouseout="this.style.backgroundColor='<?php echo $currency['code'] === $current['code'] ? '#eff6ff' : 'white'; ?>'">
                        
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-weight: 600; color: #111827; width: 2rem;">
                                <?php echo htmlspecialchars($currency['symbol']); ?>
                            </span>
                            <div>
                                <div style="font-weight: 500; color: #111827;">
                                    <?php echo htmlspecialchars($currency['code']); ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #6b7280;">
                                    <?php echo htmlspecialchars($currency['name']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($currency['code'] === $current['code']): ?>
                            <svg style="width: 1.25rem; height: 1.25rem; color: #2563eb;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
    function setCurrency(code) {
        // Set cookie for 30 days
        const expires = new Date();
        expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000));
        document.cookie = 'selected_currency=' + code + ';expires=' + expires.toUTCString() + ';path=/';
        
        // Reload page to apply new currency
        window.location.reload();
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('currency-dropdown');
        const wrapper = document.querySelector('.currency-selector-wrapper');
        
        if (dropdown && wrapper && !wrapper.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
    </script>
    <?php
    
    return ob_get_clean();
}

/**
 * Check if currency selector should be shown
 */  
function shouldShowCurrencySelector() {
    $conn = Database::getConnection();
    
    $query = "SELECT setting_value FROM currency_settings WHERE setting_key = 'show_currency_selector' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    $show = true; // Default to true
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $show = ($row['setting_value'] === 'true');
    }
    
    return $show;
}
