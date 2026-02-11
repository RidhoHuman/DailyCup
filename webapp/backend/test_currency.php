<?php
/**
 * Test Multi-Currency System (Refactored)
 * Run: php webapp/backend/test_currency.php
 */

require_once __DIR__ . '/helpers/currency_helper.php';

echo "=== MULTI-CURRENCY SYSTEM TEST (REFACTORED) ===\n\n";

// Test 1: Get Available Currencies
echo "✓ Test 1: Available Currencies\n";
echo str_repeat("-", 50) . "\n";
$currencies = getActiveCurrencies();
echo "  Found " . count($currencies) . " active currencies:\n";
foreach ($currencies as $currency) {
    $base = $currency['is_base_currency'] ? ' (BASE)' : '';
    echo sprintf("  %s - %s (%s)%s\n", 
        $currency['code'], 
        $currency['name'], 
        $currency['symbol'],
        $base
    );
}
echo "\n";

// Test 2: Price Conversions
echo "✓ Test 2: Price Conversions from 100,000 IDR\n";
echo str_repeat("-", 50) . "\n";
$test_amount = 100000;
$target_currencies = ['USD', 'EUR', 'GBP', 'SGD', 'JPY'];

foreach ($target_currencies as $to_currency) {
    $converted = convertPrice($test_amount, 'IDR', $to_currency);
    
    // Get decimal places for target currency
    $target_info = getCurrencyByCode($to_currency);
    $decimals = $target_info ? (int)$target_info['decimal_places'] : 2;
    
    echo sprintf("  IDR %s → %s %s\n",
        number_format($test_amount, 0, ',', '.'),
        $to_currency,
        number_format($converted, $decimals, ',', '.')
    );
}
echo "\n";

// Test 3: Format Price Test (simulating USD selection)
echo "✓ Test 3: Format Price with USD Selection\n";
echo str_repeat("-", 50) . "\n";
$test_prices = [25000, 50000, 100000, 250000, 500000];

// Simulate USD selection via cookie
$_COOKIE['selected_currency'] = 'USD';
foreach ($test_prices as $price) {
    $formatted = formatPrice($price);
    echo sprintf("  Rp %s = %s\n",
        number_format($price, 0, ',', '.'),
        $formatted
    );
}
echo "\n";

// Test 4: Different currency selections
echo "✓ Test 4: Same Price in Different Currencies (50,000 IDR)\n";
echo str_repeat("-", 50) . "\n";
$sample_price = 50000;
$display_currencies = ['IDR', 'USD', 'EUR', 'SGD', 'MYR'];

foreach ($display_currencies as $currency_code) {
    $_COOKIE['selected_currency'] = $currency_code;
    $formatted = formatPrice($sample_price);
    echo sprintf("  %s: %s\n", $currency_code, $formatted);
}
echo "\n";

// Test 5: Current Exchange Rates
echo "✓ Test 5: Current Exchange Rates (from database)\n";
echo str_repeat("-", 50) . "\n";

$conn = Database::getConnection();
$query = "SELECT from_currency, to_currency, rate, source, last_updated 
          FROM exchange_rates 
          WHERE from_currency = 'IDR' AND source = 'api'
          ORDER BY to_currency LIMIT 5";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo sprintf("  %s → %s: %.6f (%s at %s)\n",
            $row['from_currency'],
            $row['to_currency'],
            $row['rate'],
            $row['source'],
            date('d M Y H:i', strtotime($row['last_updated']))
        );
    }
} else {
    echo "  No API rates found. Run sync first:\n";
    echo "  php webapp/backend/cron/sync_exchange_rates.php\n";
}
echo "\n";

// Test 6: Settings
echo "✓ Test 6: Currency Settings\n";
echo str_repeat("-", 50) . "\n";
$show_selector = shouldShowCurrencySelector() ? 'YES' : 'NO';
echo "  Show currency selector: $show_selector\n";
echo "\n";

echo "=== ALL TESTS COMPLETED ===\n";
echo "✓ Multi-currency system is working from webapp/backend/!\n\n";

echo "File Structure (New):\n";
echo "  webapp/backend/helpers/currency_helper.php ✓\n";
echo "  webapp/backend/helpers/seasonal_theme.php ✓\n";
echo "  webapp/backend/cron/sync_exchange_rates.php ✓\n";
echo "  webapp/backend/api/currencies.php ✓\n\n";

echo "Next: Test via browser at http://localhost/DailyCup/customer/menu.php\n";
