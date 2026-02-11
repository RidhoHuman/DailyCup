# Seasonal Themes System - Implementation Guide

## Overview
Seasonal Themes System memungkinkan automatic theme switching berdasarkan musim, holiday, dan event khusus dengan configuration yang flexible dan management yang mudah.

## Features Implemented

### 1. Database Structure

#### `seasonal_themes` Table
Stores all seasonal theme configurations:
- `id` - Primary key
- `name` - Theme display name (e.g., "Christmas", "Ramadan")
- `slug` - Unique identifier (e.g., "christmas", "ramadan")
- `description` - Theme description
- `start_date` - Theme activation start date
- `end_date` - Theme deactivation end date
- `year_recurring` - Boolean, if TRUE theme repeats every year
- `is_active` - Boolean, enable/disable theme
- `priority` - Integer, higher priority themes override lower ones
- `theme_config` - JSON configuration with colors, images, CSS
- `created_at`, `updated_at` - Timestamps

#### `theme_settings` Table
Global theme settings:
- `auto_theme_switching` - Enable/disable automatic switching
- `manual_theme_override` - Force specific theme regardless of date
- `default_theme_slug` - Fallback theme

#### Theme Config JSON Structure
```json
{
  "primary_color": "#FF0000",
  "secondary_color": "#FFFFFF",
  "accent_color": "#CC0000",
  "background_color": "#FFF5F5",
  "text_color": "#1a1a1a",
  "banner_image": "/assets/images/themes/christmas-banner.jpg",
  "logo_variant": "christmas",
  "css_overrides": ".navbar { background: red; }"
}
```

### 2. Pre-installed Themes

#### New Year (Jan 1-7)
- **Colors**: Gold (#FFD700), Black, Red accent
- **Priority**: 100 (High)
- **Recurring**: Yearly

#### Valentine's Day (Feb 10-15)
- **Colors**: Pink (#FF1493), Light Pink, Hot Pink accent
- **Priority**: 90
- **Recurring**: Yearly

#### Ramadan (Mar 11 - Apr 10)
- **Colors**: Green (#2E8B57), Gold, Teal accent
- **Priority**: 95
- **Recurring**: Yearly

#### Indonesian Independence Day (Aug 15-20)
- **Colors**: Red (#FF0000), White
- **Priority**: 100 (High)
- **Recurring**: Yearly

#### Halloween (Oct 25 - Nov 1)
- **Colors**: Orange (#FF6600), Black, Purple accent
- **Priority**: 85
- **Recurring**: Yearly

#### Christmas (Dec 15-26)
- **Colors**: Red (#C41E3A), Green (#165B33), Gold accent
- **Priority**: 100 (High)
- **Recurring**: Yearly

#### Summer Sale (June 1-30)
- **Colors**: Orange (#FF6B35), Cyan (#00D9FF), Yellow accent
- **Priority**: 80
- **Recurring**: Yearly

### 3. Backend API (`/webapp/backend/api/themes.php`)

#### Public Endpoint (No Auth Required)

**Get Current Active Theme**
```
GET /webapp/backend/api/themes.php?action=current
```

**Response**:
```json
{
  "success": true,
  "theme": {
    "id": 6,
    "name": "Christmas",
    "slug": "christmas",
    "description": "Festive Christmas season with red and green",
    "start_date": "2024-12-15",
    "end_date": "2024-12-26",
    "year_recurring": true,
    "is_active": true,
    "priority": 100,
    "theme_config": {
      "primary_color": "#C41E3A",
      "secondary_color": "#165B33",
      "accent_color": "#FFD700",
      "background_color": "#FFF9F0",
      "text_color": "#1a1a1a",
      "banner_image": "/assets/images/themes/christmas-banner.jpg",
      "logo_variant": "christmas",
      "css_overrides": ".navbar { background: linear-gradient(135deg, #C41E3A 0%, #165B33 100%); }"
    },
    "is_manual": false
  },
  "auto_switching": true
}
```

#### Admin Endpoints (Require Admin Auth)

**Get All Themes**
```
GET /webapp/backend/api/themes.php
Authorization: Bearer {token}
```

**Create Theme**
```
POST /webapp/backend/api/themes.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "New Theme",
  "slug": "new-theme",
  "description": "Description",
  "start_date": "2024-01-01",
  "end_date": "2024-01-07",
  "year_recurring": true,
  "is_active": true,
  "priority": 50,
  "theme_config": {
    "primary_color": "#000000",
    "secondary_color": "#FFFFFF",
    ...
  }
}
```

**Update Theme**
```
PUT /webapp/backend/api/themes.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "id": 1,
  "is_active": false,
  "priority": 90
}
```

**Update Settings**
```
PUT /webapp/backend/api/themes.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "setting_key": "auto_theme_switching",
  "setting_value": "true"
}
```

**Delete Theme**
```
DELETE /webapp/backend/api/themes.php?id=1
Authorization: Bearer {token}
```

### 4. Frontend Implementation

#### A. React Context (`contexts/ThemeContext.tsx`)

**Features**:
- Automatic theme fetching and application
- CSS variable injection
- LocalStorage caching
- Daily refresh
- Manual theme override support

**Usage in Next.js**:
```tsx
// In app/layout.tsx
import { ThemeProvider } from '@/contexts/ThemeContext';

export default function RootLayout({ children }) {
  return (
    <ThemeProvider>
      {children}
    </ThemeProvider>
  );
}

// In any component
import { useTheme } from '@/contexts/ThemeContext';

function MyComponent() {
  const { currentTheme, applyTheme, refreshTheme } = useTheme();
  
  return (
    <div>
      {currentTheme && <p>Active Theme: {currentTheme.name}</p>}
      <button onClick={refreshTheme}>Refresh Theme</button>
    </div>
  );
}
```

#### B. PHP Helper (`includes/seasonal_theme.php`)

**Functions**:
- `getCurrentSeasonalTheme()` - Get active theme
- `applySeasonalThemeCSS()` - Generate theme CSS
- `getSeasonalThemeBanner()` - Get banner image URL
- `getSeasonalThemeName()` - Get theme name
- `isSeasonalThemeActive()` - Check if theme is active

**Auto-applied in `includes/header.php`**:
```php
<?php
require_once __DIR__ . '/seasonal_theme.php';
echo applySeasonalThemeCSS();
?>
```

### 5. Admin Panel (`/admin/themes`)

**Features**:
- View all themes with status
- Enable/disable themes
- Toggle auto-switching
- Set manual theme override
- Delete themes
- View current active theme
- Color preview
- Date range display
- Priority management

**UI Elements**:
- Theme list with status badges
- "Active Now" indicator
- Color swatches preview
- Enable/Disable toggle
- Delete button
- Settings panel
- Auto-switch toggle

## How It Works

### Theme Selection Algorithm

1. **Check Manual Override**
   - If admin set manual override → Use that theme
   - Skip date checking, skip auto-switching

2. **Check Auto-Switching**
   - If disabled → No theme applied
   - If enabled → Continue to step 3

3. **Find Active Theme by Date**
   ```sql
   SELECT * FROM seasonal_themes
   WHERE is_active = 1
   AND (
       (year_recurring = 1 AND 
        DATE_FORMAT(start_date, '%m-%d') <= 'current_month_day' AND 
        DATE_FORMAT(end_date, '%m-%d') >= 'current_month_day')
       OR
       (year_recurring = 0 AND 
        start_date <= 'today' AND 
        end_date >= 'today')
   )
   ORDER BY priority DESC
   LIMIT 1
   ```

4. **Apply Theme**
   - Inject CSS variables
   - Apply custom CSS overrides
   - Set banner image
   - Cache in LocalStorage (frontend)

### CSS Variables Applied

```css
:root {
  --theme-primary: #C41E3A;
  --theme-secondary: #165B33;
  --theme-accent: #FFD700;
  --theme-background: #FFF9F0;
  --theme-text: #1a1a1a;
}
```

### Automatic Class Updates

```css
.btn-coffee {
  background-color: var(--theme-primary);
}

.text-coffee {
  color: var(--theme-primary);
}

.bg-coffee {
  background-color: var(--theme-primary);
}

.navbar {
  background: linear-gradient(var(--theme-primary), var(--theme-secondary));
}
```

## Installation

### 1. Database Setup
```bash
mysql -u root -p dailycup_db < database/seasonal_themes.sql
```

### 2. Backend Files
Already created:
- `/webapp/backend/api/themes.php` - Theme API
- `/includes/seasonal_theme.php` - PHP helper
- `/includes/header.php` - Updated with theme injection

### 3. Frontend Files
Already created:
- `/webapp/frontend/contexts/ThemeContext.tsx` - React context
- `/webapp/frontend/app/admin/(panel)/themes/page.tsx` - Admin panel
- `/webapp/frontend/components/admin/Sidebar.tsx` - Updated with Themes menu

### 4. Verify Installation
1. Import SQL file
2. Login to admin panel
3. Navigate to Themes page
4. Verify 7 pre-installed themes
5. Enable auto-switching
6. Visit customer pages to see theme applied

## Usage

### Admin Tasks

#### Enable/Disable Auto-Switching
1. Go to `/admin/themes`
2. Toggle "Auto Theme Switching"
3. Themes will automatically activate based on dates

#### Force Specific Theme
1. Go to `/admin/themes`
2. Select theme from "Manual Theme Override" dropdown
3. Theme bypasses date checking

#### Enable/Disable Individual Themes
1. Go to `/admin/themes`
2. Click "Enable" or "Disable" button on theme card
3. Disabled themes won't activate even if date matches

#### Delete Theme
1. Go to `/admin/themes`
2. Click "Delete" button
3. Confirm deletion

### Developer Tasks

#### Create Custom Theme
```javascript
const newTheme = {
  name: "Spring Festival",
  slug: "spring",
  description: "Celebrate spring with floral colors",
  start_date: "2024-03-20",
  end_date: "2024-04-05",
  year_recurring: true,
  is_active: true,
  priority: 85,
  theme_config: {
    primary_color: "#FF69B4",
    secondary_color: "#98FB98",
    accent_color: "#FFD700",
    background_color: "#FFFACD",
    text_color: "#2F4F4F",
    banner_image: "/assets/images/themes/spring-banner.jpg",
    logo_variant: "spring",
    css_overrides: `
      .navbar { background: linear-gradient(to right, #FF69B4, #98FB98); }
      .card { border-color: var(--theme-primary); }
    `
  }
};

// POST to API
fetch('/webapp/backend/api/themes.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(newTheme)
});
```

#### Use Theme in Custom Components
```tsx
import { useTheme } from '@/contexts/ThemeContext';

function CustomBanner() {
  const { currentTheme } = useTheme();
  
  if (!currentTheme) return null;
  
  return (
    <div style={{
      backgroundColor: currentTheme.theme_config.primary_color,
      color: currentTheme.theme_config.text_color
    }}>
      <h1>Welcome to {currentTheme.name}!</h1>
      <p>{currentTheme.description}</p>
    </div>
  );
}
```

## Features Highlights

### ✅ Automatic Date-Based Switching
- Changes themes automatically based on date ranges
- Support for yearly recurring themes
- Priority system for overlapping dates
- No manual intervention needed

### ✅ Flexible Configuration
- JSON-based theme config
- Custom CSS overrides
- Banner image support
- Logo variants
- Multiple color schemes

### ✅ Admin Control
- Enable/disable themes
- Manual override option
- Priority management
- Real-time preview
- Easy theme deletion

### ✅ Developer Friendly
- React Context API integration
- PHP helper functions
- CSS variable system
- LocalStorage caching
- RESTful API

### ✅ Performance Optimized
- Cached in LocalStorage
- Minimal database queries
- CSS variable injection (no re-render)
- Daily auto-refresh only

### ✅ Real Data Based
- **NO hardcoded themes in code**
- All themes stored in database
- Dynamic theme loading
- Can be modified without code changes

## Technical Details

### Priority System
When multiple themes overlap:
1. Sort by `priority` DESC
2. Sort by `id` DESC (newer first)
3. Take first result

### Year Recurring Logic
```php
if (year_recurring) {
  // Compare month-day only (ignore year)
  if (current_month_day >= start_month_day && 
      current_month_day <= end_month_day) {
    activate_theme();
  }
} else {
  // Compare full date
  if (current_date >= start_date && 
      current_date <= end_date) {
    activate_theme();
  }
}
```

### CSS Injection Method

**Frontend (React)**:
```typescript
document.documentElement.style.setProperty('--theme-primary', color);
```

**Backend (PHP)**:
```php
echo "<style>:root { --theme-primary: $color; }</style>";
```

### Caching Strategy
1. Fetch theme from API
2. Apply to DOM
3. Store in LocalStorage
4. On next visit: use cached theme while fetching new
5. If fetch fails: fallback to cache
6. Auto-refresh every 24 hours

## Testing

### Test Current Theme API
```bash
curl http://localhost/DailyCup/webapp/backend/api/themes.php?action=current
```

### Test Date Logic
Modify system date and verify correct theme activates:
```php
// Temporary test - change date
date_default_timezone_set('Asia/Jakarta');
// Set to Dec 20
// Expected: Christmas theme
```

### Test Manual Override
1. Set manual override to "valentine"
2. Check any date (even July)
3. Valentine theme should be active

### Test Priority
1. Create two themes with overlapping dates
2. Set different priorities
3. Higher priority theme should win

## Future Enhancements
- Visual theme editor in admin panel
- Theme preview before activation
- Import/export theme configurations
- Theme templates marketplace
- A/B testing different themes
- Analytics on theme engagement
- Gradual theme transitions
- Time-based theme switching (morning/afternoon/evening)
- Location-based themes
- User preference override

## Files Created/Modified

### Created:
- `/database/seasonal_themes.sql` - Database schema
- `/webapp/backend/api/themes.php` - Theme management API
- `/includes/seasonal_theme.php` - PHP helper functions
- `/webapp/frontend/contexts/ThemeContext.tsx` - React context
- `/webapp/frontend/app/admin/(panel)/themes/page.tsx` - Admin panel

### Modified:
- `/includes/header.php` - Added theme CSS injection
- `/webapp/frontend/components/admin/Sidebar.tsx` - Added Themes menu

## Summary
Seasonal Themes System telah berhasil diimplementasikan dengan:
✅ 7 pre-configured seasonal themes (New Year, Valentine, Ramadan, Independence Day, Halloween, Christmas, Summer)
✅ Automatic date-based switching dengan year-recurring support
✅ Manual override option untuk force specific theme
✅ Priority system untuk handle overlapping dates
✅ RESTful API untuk CRUD operations
✅ React Context untuk frontend integration
✅ PHP helper functions untuk legacy site
✅ Admin panel untuk easy management
✅ Real-time CSS variable injection
✅ LocalStorage caching untuk performance
✅ Full database-driven (NO hardcoded themes)
