# Multi-Currency Support - Implementation Guide

## Overview
The DailyCup e-commerce platform now supports multiple currencies with real-time exchange rates. Customers can view prices in their preferred currency, and exchange rates are automatically updated from external APIs.

## Features Implemented

### 1. **Database Schema**
- **currencies** table: Stores currency information (code, name, symbol, decimal places)
- **exchange_rates** table: Stores conversion rates between currencies
- **currency_settings** table: Global currency settings

### 2. **Admin Panel**
- Full currency management dashboard
- Enable/disable currencies
- Add new currencies
- Sync exchange rates from live API
- Auto-update settings
- View all currencies and their status

### 3. **Exchange Rate Synchronization**
- Manual sync via admin panel
- Automated sync via cron job
- Uses exchangerate-api.com for real-time rates
- Updates both direct and reverse rates
- Calculates cross-currency rates

### 4. **Customer Experience**
- Currency selector in navigation bar
- Automatic price conversion
- Preferred currency saved in cookies
- Seamless price display across all pages

### 5. **Developer Integration**
- PHP helper functions for legacy pages
- React components for Next.js pages
- Context API for state management
- Backward compatible with existing code

## Installation

### Step 1: Create Database Tables
Run the SQL file to create required tables and populate initial data:

```sql
SOURCE database/multi_currency.sql;
```

or via phpMyAdmin:
1. Open phpMyAdmin
2. Select `dailycup_db` database
3. Go to Import tab
4. Choose `database/multi_currency.sql`
5. Click Go

### Step 2: Verify Installation
Check that the following tables were created:
- `currencies` (should have 10 pre-installed currencies)
- `exchange_rates` (should have initial exchange rates)
- `currency_settings` (should have default settings)

### Step 3: (Optional) Set Up Automated Exchange Rate Updates
Add to crontab for daily updates:

```bash
# Update exchange rates daily at midnight
0 0 * * * php /path/to/DailyCup/api/sync_exchange_rates.php
```

Or manually sync via admin panel.

## File Structure

```
DailyCup/
├── database/
│   └── multi_currency.sql                   # Database schema + initial data
│
├── webapp/
│   ├── backend/
│   │   └── api/
│   │       └── currencies.php               # Currency management API
│   │
│   └── frontend/
│       ├── app/
│       │   └── admin/
│       │       └── (panel)/
│       │           └── currencies/
│       │               └── page.tsx         # Admin currency manager
│       │
│       ├── components/
│       │   └── CurrencySelector.tsx         # Currency dropdown component
│       │
│       └── contexts/
│           └── CurrencyContext.tsx          # React context for currency
│
├── api/
│   └── sync_exchange_rates.php              # Cron job for auto-sync
│
└── includes/
    ├── currency_helper.php                  # PHP helper functions
    ├── functions.php                        # Updated formatCurrency()
    └── navbar.php                           # Added currency selector
```

## API Endpoints

### Public Endpoints (No Authentication)

#### Get All Currencies
```
GET /webapp/backend/api/currencies.php?action=list
```
Returns all currencies (active and inactive).

#### Get Active Currencies
```
GET /webapp/backend/api/currencies.php?action=active
```
Returns only active currencies.

#### Convert Price
```
GET /webapp/backend/api/currencies.php?action=convert&amount=100000&from=IDR&to=USD
```
Converts amount from one currency to another.

**Response:**
```json
{
  "success": true,
  "original_amount": 100000,
  "converted_amount": 6.4,
  "from_currency": "IDR",
  "to_currency": "USD",
  "rate": 0.000064,
  "last_updated": "2024-01-15 10:30:00"
}
```

### Admin Endpoints (Require Authentication)

#### Enable/Disable Currency
```
POST /webapp/backend/api/currencies.php?action=update_status
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "currency_id": 2,
  "is_active": true
}
```

#### Update Exchange Rate Manually
```
POST /webapp/backend/api/currencies.php?action=update_rate
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "from": "IDR",
  "to": "USD",
  "rate": 0.000064
}
```

#### Sync Exchange Rates from API
```
POST /webapp/backend/api/currencies.php?action=sync_rates
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "success": true,
  "message": "Updated 9 exchange rates",
  "updated_count": 9,
  "last_sync": "2024-01-15 10:35:00"
}
```

#### Get Currency Settings
```
GET /webapp/backend/api/currencies.php?action=get_settings
Authorization: Bearer {admin_token}
```

#### Update Currency Settings
```
POST /webapp/backend/api/currencies.php?action=update_settings
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "auto_update_rates": "true",
  "show_currency_selector": "true"
}
```

#### Add New Currency
```
POST /webapp/backend/api/currencies.php?action=add_currency
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "code": "KRW",
  "name": "South Korean Won",
  "symbol": "₩",
  "decimal_places": 0
}
```

## Usage Guide

### For Legacy PHP Pages

#### 1. Format Price with Currency Conversion
```php
<?php
require_once __DIR__ . '/../includes/currency_helper.php';

// Convert and format price
$price = 50000; // IDR
$formatted = formatPrice($price);
echo $formatted; // Output: $3.20 (if USD is selected)

// Or use the updated formatCurrency function
echo formatCurrency($price); // Automatically uses selected currency
?>
```

#### 2. Render Currency Selector
```php
<?php
require_once __DIR__ . '/../includes/currency_helper.php';

// Check if should show
if (shouldShowCurrencySelector()) {
    echo renderCurrencySelector();
}
?>
```

#### 3. Get Current Currency
```php
<?php
require_once __DIR__ . '/../includes/currency_helper.php';

$current = getCurrentCurrency();
echo "Current: " . $current['code']; // USD
echo "Symbol: " . $current['symbol']; // $
?>
```

#### 4. Convert Price Programmatically
```php
<?php
require_once __DIR__ . '/../includes/currency_helper.php';

$idr_price = 100000;
$usd_price = convertPrice($idr_price, 'IDR', 'USD');
echo $usd_price; // 6.4
?>
```

### For Next.js/React Pages

#### 1. Wrap App with CurrencyProvider
```tsx
// app/layout.tsx
import { CurrencyProvider } from '@/contexts/CurrencyContext';

export default function RootLayout({ children }) {
  return (
    <html>
      <body>
        <CurrencyProvider>
          {children}
        </CurrencyProvider>
      </body>
    </html>
  );
}
```

#### 2. Use Currency in Components
```tsx
'use client';

import { useCurrency } from '@/contexts/CurrencyContext';

export default function ProductCard({ product }) {
  const { formatPrice } = useCurrency();
  const [formattedPrice, setFormattedPrice] = useState('');
  
  useEffect(() => {
    formatPrice(product.base_price).then(setFormattedPrice);
  }, [product.base_price]);
  
  return <div>Price: {formattedPrice}</div>;
}
```

#### 3. Add Currency Selector to Page
```tsx
import CurrencySelector from '@/components/CurrencySelector';

export default function Header() {
  return (
    <header>
      <nav>
        <CurrencySelector />
      </nav>
    </header>
  );
}
```

## Pre-installed Currencies

| Code | Name | Symbol | Decimals | Status |
|------|------|--------|----------|--------|
| IDR | Indonesian Rupiah | Rp | 0 | Active (Base) |
| USD | US Dollar | $ | 2 | Active |
| EUR | Euro | € | 2 | Active |
| GBP | British Pound | £ | 2 | Active |
| SGD | Singapore Dollar | S$ | 2 | Active |
| MYR | Malaysian Ringgit | RM | 2 | Active |
| JPY | Japanese Yen | ¥ | 0 | Active |
| CNY | Chinese Yuan | ¥ | 2 | Active |
| AUD | Australian Dollar | A$ | 2 | Active |
| THB | Thai Baht | ฿ | 2 | Active |

## Exchange Rate Provider

The system uses **exchangerate-api.com** for fetching live exchange rates:
- **Free tier**: 1,500 requests/month
- **No API key required** for basic usage
- **Updates**: Daily (configurable)
- **Format**: JSON
- **Base currency**: Configurable (default: IDR)

### API Response Structure
```json
{
  "result": "success",
  "base_code": "IDR",
  "conversion_rates": {
    "USD": 0.000064,
    "EUR": 0.000059,
    "GBP": 0.000051,
    ...
  }
}
```

## Admin Features

### 1. Currency Management Dashboard
Access: `/admin/currencies`

Features:
- View all currencies with status
- Enable/disable currencies individually
- Add new currencies manually
- See base currency indicator
- Sort by display order

### 2. Exchange Rate Synchronization
- **Manual Sync**: Click "Sync Exchange Rates Now" button
- **Auto Sync**: Enable in settings for daily automatic updates
- **Source Indicator**: Shows whether rate is from API or manual
- **Last Updated**: Timestamp for each rate

### 3. Settings
- **Auto-update rates**: Enable/disable automatic daily sync
- **Show currency selector**: Show/hide selector from customers
- **Default display currency**: Set default for new visitors

## Customization

### Add Custom Currency
1. Go to Admin Panel → Currencies
2. Click "Add Currency"
3. Fill in:
   - Currency Code (3 letters, e.g., KRW)
   - Currency Name (e.g., South Korean Won)
   - Symbol (e.g., ₩)
   - Decimal Places (usually 0 or 2)
4. Click "Add Currency"
5. Sync exchange rates to populate conversion rates

### Change Base Currency
**Warning**: Changing base currency requires updating all stored prices.

1. Update database:
```sql
-- Set new base currency (USD)
UPDATE currencies SET is_base_currency = FALSE;
UPDATE currencies SET is_base_currency = TRUE WHERE code = 'USD';
```

2. Convert all product prices:
```sql
-- Multiply all prices by conversion rate
UPDATE products SET base_price = base_price * (
  SELECT rate FROM exchange_rates 
  WHERE from_currency = 'IDR' AND to_currency = 'USD' 
  LIMIT 1
);
```

3. Sync exchange rates from new base

### Use Different Exchange Rate API

Edit `api/sync_exchange_rates.php` and `webapp/backend/api/currencies.php`:

```php
// Replace API URL
$api_url = "https://api.example.com/latest/" . $base_currency;

// Update response parsing based on new API format
```

Popular alternatives:
- **Fixer.io** (requires API key, €10/month)
- **CurrencyAPI.com** (300 requests/month free)
- **OpenExchangeRates.org** (1,000 requests/month free)

## Troubleshooting

### Exchange rates not updating
1. Check admin settings: Auto-update should be enabled
2. Verify cron job is running:
```bash
tail -f /path/to/DailyCup/logs/exchange_rate_sync.log
```
3. Manually sync via admin panel
4. Check API is accessible:
```bash
curl https://api.exchangerate-api.com/v4/latest/IDR
```

### Currency selector not showing
1. Check setting in admin panel: "Show currency selector" should be enabled
2. Verify at least 2 currencies are active
3. Clear browser cookies
4. Check PHP errors in logs

### Prices not converting
1. Verify exchange rates exist in database:
```sql
SELECT * FROM exchange_rates WHERE from_currency = 'IDR' AND to_currency = 'USD';
```
2. Check selected currency in browser cookies
3. Sync exchange rates via admin panel

### Wrong decimal places
1. Update currency settings in database:
```sql
UPDATE currencies SET decimal_places = 0 WHERE code = 'JPY';
```
2. Currencies like JPY and IDR typically use 0 decimals
3. Most others use 2 decimals

## Performance Considerations

### Caching Strategy
1. **Exchange rates**: Cached in database, updated daily
2. **Currency list**: Fetched once per page load
3. **Converted prices**: Calculated on-demand (consider implementing price cache)

### Optimization Tips
1. **Index exchange_rates table** on (from_currency, to_currency)
2. **Cache active currencies** in Redis/Memcached
3. **Pre-calculate common conversions** for frequently accessed products
4. **Use CDN** for currency selector dropdown

### Database Queries
All currency operations use indexed queries:
- `currencies.is_active` - indexed
- `exchange_rates.from_currency` - indexed
- `exchange_rates.to_currency` - indexed

## Security

### Input Validation
- Currency codes validated as 3-letter uppercase
- Exchange rates must be positive numbers
- Decimal places limited to 0-8

### Authentication
- Public endpoints: No authentication required
- Admin endpoints: Require Bearer token + admin role
- Rate updates: Admin only

### Rate Limiting
Consider implementing rate limiting for conversion API:
```php
// In currencies.php
if (rateLimitExceeded($ip)) {
    http_response_code(429);
    exit('Too many requests');
}
```

## Backup & Restore

### Backup Currency Data
```bash
mysqldump -u root dailycup_db currencies exchange_rates currency_settings > currency_backup.sql
```

### Restore Currency Data
```bash
mysql -u root dailycup_db < currency_backup.sql
```

## Future Enhancements

Potential improvements for future versions:
1. **Multiple base currencies** for different regions
2. **Historical exchange rate tracking** for reporting
3. **Price rounding rules** per currency
4. **Custom exchange rate markups** for profit margin
5. **Cryptocurrency support** (BTC, ETH)
6. **Regional pricing** (different base prices per region)
7. **Exchange rate alerts** when rates change significantly

## Testing Checklist

- [ ] Import SQL file successfully
- [ ] Admin panel shows all currencies
- [ ] Enable/disable currency works
- [ ] Add new currency works
- [ ] Sync exchange rates button works
- [ ] Currency selector appears in navbar
- [ ] Selecting currency persists after page reload
- [ ] Prices convert correctly
- [ ] Decimal places display correctly
- [ ] Guest users can select currency
- [ ] Logged-in users can select currency
- [ ] Cron job runs successfully (if configured)
- [ ] Settings save correctly

## Support

For issues or questions:
1. Check logs: `/logs/exchange_rate_sync.log`
2. Verify database tables exist
3. Test API endpoint manually
4. Check browser console for JavaScript errors
5. Review PHP error logs

## License
This multi-currency system is part of the DailyCup e-commerce platform.

---

**Version**: 1.0.0  
**Last Updated**: January 2024  
**Requires**: PHP 7.4+, MySQL 5.7+, Bootstrap 5+
