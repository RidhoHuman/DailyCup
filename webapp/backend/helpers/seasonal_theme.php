<?php
/**
 * Seasonal Theme Helper
 * Provides functions to apply seasonal themes to the legacy PHP site
 */

require_once __DIR__ . '/../config/database.php';

function getCurrentSeasonalTheme() {
    $conn = Database::getConnection();
    
    // Check if auto-switching is enabled
    $query = "SELECT setting_value FROM theme_settings WHERE setting_key = 'auto_theme_switching'";
    $result = mysqli_query($conn, $query);
    $autoSwitch = $result ? mysqli_fetch_assoc($result) : null;
    
    // Check for manual override
    $query = "SELECT setting_value FROM theme_settings WHERE setting_key = 'manual_theme_override'";
    $result = mysqli_query($conn, $query);
    $manualOverride = $result ? mysqli_fetch_assoc($result) : null;
    
    if ($manualOverride && $manualOverride['setting_value']) {
        // Return manual theme
        $slug = mysqli_real_escape_string($conn, $manualOverride['setting_value']);
        $query = "SELECT * FROM seasonal_themes WHERE slug = '$slug' AND is_active = 1";
        $result = mysqli_query($conn, $query);
        $theme = $result ? mysqli_fetch_assoc($result) : null;
        
        if ($theme) {
            $theme['theme_config'] = json_decode($theme['theme_config'], true);
            return $theme;
        }
    }
    
    if ($autoSwitch && $autoSwitch['setting_value'] === 'true') {
        // Get current date
        $today = date('Y-m-d');
        $currentMonth = date('m-d');
        
        // Find active seasonal theme
        $query = "
            SELECT * FROM seasonal_themes
            WHERE is_active = 1
            AND (
                (year_recurring = 1 AND 
                 DATE_FORMAT(start_date, '%m-%d') <= '$currentMonth' AND 
                 DATE_FORMAT(end_date, '%m-%d') >= '$currentMonth')
                OR
                (year_recurring = 0 AND start_date <= '$today' AND end_date >= '$today')
            )
            ORDER BY priority DESC, id DESC
            LIMIT 1
        ";
        
        $result = mysqli_query($conn, $query);
        $theme = $result ? mysqli_fetch_assoc($result) : null;
        
        if ($theme) {
            $theme['theme_config'] = json_decode($theme['theme_config'], true);
            return $theme;
        }
    }
    
    return null;
}

function applySeasonalThemeCSS() {
    $theme = getCurrentSeasonalTheme();
    
    if (!$theme) {
        return '';
    }
    
    $config = $theme['theme_config'];
    
    $css = "<style id='seasonal-theme'>\n";
    $css .= ":root {\n";
    $css .= "    --theme-primary: {$config['primary_color']};\n";
    $css .= "    --theme-secondary: {$config['secondary_color']};\n";
    $css .= "    --theme-accent: {$config['accent_color']};\n";
    $css .= "    --theme-background: {$config['background_color']};\n";
    $css .= "    --theme-text: {$config['text_color']};\n";
    $css .= "}\n";
    
    // Apply theme colors to common elements
    $css .= ".btn-coffee {\n";
    $css .= "    background-color: {$config['primary_color']} !important;\n";
    $css .= "    border-color: {$config['primary_color']} !important;\n";
    $css .= "}\n";
    
    $css .= ".btn-coffee:hover {\n";
    $css .= "    background-color: {$config['accent_color']} !important;\n";
    $css .= "    border-color: {$config['accent_color']} !important;\n";
    $css .= "}\n";
    
    $css .= ".text-coffee {\n";
    $css .= "    color: {$config['primary_color']} !important;\n";
    $css .= "}\n";
    
    $css .= ".bg-coffee {\n";
    $css .= "    background-color: {$config['primary_color']} !important;\n";
    $css .= "}\n";
    
    $css .= ".navbar {\n";
    $css .= "    background: linear-gradient(135deg, {$config['primary_color']} 0%, {$config['secondary_color']} 100%) !important;\n";
    $css .= "}\n";
    
    // Add custom CSS overrides if provided
    if (!empty($config['css_overrides'])) {
        $css .= "\n/* Custom Theme Overrides */\n";
        $css .= $config['css_overrides'] . "\n";
    }
    
    $css .= "</style>\n";
    
    // Add theme banner if provided
    if (!empty($config['banner_image'])) {
        $css .= "<script>\n";
        $css .= "document.addEventListener('DOMContentLoaded', function() {\n";
        $css .= "    var banner = document.querySelector('.hero-banner');\n";
        $css .= "    if (banner) {\n";
        $css .= "        banner.style.backgroundImage = 'url({$config['banner_image']})';\n";
        $css .= "    }\n";
        $css .= "});\n";
        $css .= "</script>\n";
    }
    
    return $css;
}

function getSeasonalThemeBanner() {
    $theme = getCurrentSeasonalTheme();
    
    if (!$theme || empty($theme['theme_config']['banner_image'])) {
        return null;
    }
    
    return $theme['theme_config']['banner_image'];
}

function getSeasonalThemeName() {
    $theme = getCurrentSeasonalTheme();
    return $theme ? $theme['name'] : null;
}

function isSeasonalThemeActive() {
    $theme = getCurrentSeasonalTheme();
    return $theme !== null;
}
?>
