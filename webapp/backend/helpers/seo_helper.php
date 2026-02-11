<?php
/**
 * SEO Helper Functions
 * Helper functions for SEO meta tags, structured data, and Open Graph tags
 * Used by legacy PHP pages
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get SEO metadata for a page
 */
function getSeoMeta($slug, $defaults = []) {
    $conn = Database::getConnection();
    $slug = mysqli_real_escape_string($conn, $slug);
    
    $query = "SELECT * FROM seo_metadata WHERE slug = '$slug' AND is_active = TRUE LIMIT 1";
    $result = mysqli_query($conn, $query);
    $meta = mysqli_fetch_assoc($result);
    
    if ($meta) {
        if ($meta['structured_data']) {
            $meta['structured_data'] = json_decode($meta['structured_data'], true);
        }
        return $meta;
    }
    
    // Return defaults if no custom meta found
    return array_merge([
        'title' => 'DailyCup Coffee Shop',
        'meta_description' => 'Fresh coffee and delicious food',
        'meta_keywords' => 'coffee, daily cup',
        'robots' => 'index, follow'
    ], $defaults);
}

/**
 * Render meta tags in HTML head
 */
function renderMetaTags($slug, $customMeta = []) {
    $meta = getSeoMeta($slug, $customMeta);
    
    $output = '';
    
    // Basic meta tags
    $output .= '<meta name="description" content="' . htmlspecialchars($meta['meta_description'] ?? '') . '">' . "\n";
    if (!empty($meta['meta_keywords'])) {
        $output .= '<meta name="keywords" content="' . htmlspecialchars($meta['meta_keywords']) . '">' . "\n";
    }
    $output .= '<meta name="robots" content="' . htmlspecialchars($meta['robots'] ?? 'index, follow') . '">' . "\n";
    
    // Canonical URL
    if (!empty($meta['canonical_url'])) {
        $output .= '<link rel="canonical" href="' . htmlspecialchars($meta['canonical_url']) . '">' . "\n";
    }
    
    // Open Graph tags
    $output .= '<meta property="og:title" content="' . htmlspecialchars($meta['og_title'] ?? $meta['title']) . '">' . "\n";
    $output .= '<meta property="og:description" content="' . htmlspecialchars($meta['og_description'] ?? $meta['meta_description'] ?? '') . '">' . "\n";
    $output .= '<meta property="og:type" content="' . htmlspecialchars($meta['og_type'] ?? 'website') . '">' . "\n";
    
    if (!empty($meta['og_image'])) {
        $output .= '<meta property="og:image" content="' . htmlspecialchars($meta['og_image']) . '">' . "\n";
    }
    
    $output .= '<meta property="og:url" content="' . getCurrentUrl() . '">' . "\n";
    $output .= '<meta property="og:site_name" content="DailyCup Coffee Shop">' . "\n";
    
    // Twitter Card tags
    $output .= '<meta name="twitter:card" content="' . htmlspecialchars($meta['twitter_card'] ?? 'summary_large_image') . '">' . "\n";
    $output .= '<meta name="twitter:title" content="' . htmlspecialchars($meta['twitter_title'] ?? $meta['og_title'] ?? $meta['title']) . '">' . "\n";
    $output .= '<meta name="twitter:description" content="' . htmlspecialchars($meta['twitter_description'] ?? $meta['og_description'] ?? $meta['meta_description'] ?? '') . '">' . "\n";
    
    if (!empty($meta['twitter_image']) || !empty($meta['og_image'])) {
        $output .= '<meta name="twitter:image" content="' . htmlspecialchars($meta['twitter_image'] ?? $meta['og_image']) . '">' . "\n";
    }
    
    return $output;
}

/**
 * Render structured data (JSON-LD)
 */
function renderStructuredData($slug, $customData = null) {
    if ($customData) {
        $data = $customData;
    } else {
        $meta = getSeoMeta($slug);
        $data = $meta['structured_data'] ?? null;
    }
    
    if (!$data) {
        return '';
    }
    
    $output = '<script type="application/ld+json">' . "\n";
    $output .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $output .= "\n" . '</script>' . "\n";
    
    return $output;
}

/**
 * Generate breadcrumb structured data
 */
function generateBreadcrumbSchema($items) {
    $baseUrl = getBaseUrl();
    $itemList = [];
    
    foreach ($items as $index => $item) {
        $itemList[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => $baseUrl . '/' . ltrim($item['url'], '/')
        ];
    }
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $itemList
    ];
    
    return $schema;
}

/**
 * Generate product structured data
 */
function generateProductSchema($product) {
    $baseUrl = getBaseUrl();
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product['name'],
        'description' => $product['description'] ?? '',
        'image' => $baseUrl . '/assets/images/products/' . ($product['image'] ?? 'default.jpg'),
        'sku' => 'PROD-' . $product['id'],
        'offers' => [
            '@type' => 'Offer',
            'price' => $product['price'],
            'priceCurrency' => 'IDR',
            'availability' => $product['stock'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => $baseUrl . '/product/' . $product['id']
        ]
    ];
    
    // Add ratings if available
    if (isset($product['avg_rating']) && $product['avg_rating'] > 0) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $product['avg_rating'],
            'reviewCount' => $product['review_count'] ?? 0
        ];
    }
    
    return $schema;
}

/**
 * Generate organization structured data
 */
function generateOrganizationSchema() {
    $baseUrl = getBaseUrl();
    
    return [
        '@context' => 'https://schema.org',
        '@type' => 'CafeOrCoffeeShop',
        'name' => 'DailyCup Coffee Shop',
        'image' => $baseUrl . '/assets/images/logo.png',
        'url' => $baseUrl,
        'telephone' => '+62-xxx-xxxx',
        'priceRange' => '$$',
        'address' => [
            '@type' => 'PostalAddress',
            'addressCountry' => 'ID'
        ],
        'openingHoursSpecification' => [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'opens' => '07:00',
            'closes' => '22:00'
        ],
        'servesCuisine' => 'Coffee & Beverages'
    ];
}

/**
 * Check and handle SEO redirects
 */
function checkSeoRedirect() {
    $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    $conn = Database::getConnection();
    $url = mysqli_real_escape_string($conn, $currentUrl);
    
    $query = "SELECT * FROM seo_redirects WHERE old_url = '$url' AND is_active = TRUE LIMIT 1";
    $result = mysqli_query($conn, $query);
    $redirect = mysqli_fetch_assoc($result);
    
    if ($redirect) {
        // Increment hit count
        mysqli_query($conn, "UPDATE seo_redirects SET hit_count = hit_count + 1 WHERE id = " . $redirect['id']);
        
        // Perform redirect
        $httpCode = ($redirect['redirect_type'] === '301') ? 301 : 302;
        header("Location: " . $redirect['new_url'], true, $httpCode);
        exit;
    }
}

/**
 * Track SEO analytics
 */
function trackSeoVisit($pageUrl = null) {
    if (!$pageUrl) {
        $pageUrl = $_SERVER['REQUEST_URI'];
    }
    
    $conn = Database::getConnection();
    
    $pageUrl = mysqli_real_escape_string($conn, $pageUrl);
    $searchKeyword = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : 'NULL';
    $searchKeyword = ($searchKeyword === 'NULL') ? 'NULL' : "'$searchKeyword'";
    
    $referrer = isset($_SERVER['HTTP_REFERER']) ? mysqli_real_escape_string($conn, $_SERVER['HTTP_REFERER']) : 'NULL';
    $referrer = ($referrer === 'NULL') ? 'NULL' : "'$referrer'";
    
    $userAgent = mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT'] ?? '');
    $deviceType = detectDeviceType($userAgent);
    
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $sessionId = session_id();
    
    $query = "
        INSERT INTO seo_analytics (page_url, search_keyword, referrer, user_agent, device_type, session_id, ip_address)
        VALUES ('$pageUrl', $searchKeyword, $referrer, '$userAgent', '$deviceType', '$sessionId', '$ipAddress')
    ";
    
    mysqli_query($conn, $query);
}

/**
 * Detect device type from user agent
 */
function detectDeviceType($userAgent) {
    $userAgent = strtolower($userAgent);
    
    if (strpos($userAgent, 'bot') !== false || strpos($userAgent, 'crawler') !== false || strpos($userAgent, 'spider') !== false) {
        return 'bot';
    }
    
    if (strpos($userAgent, 'mobile') !== false || strpos($userAgent, 'android') !== false || strpos($userAgent, 'iphone') !== false) {
        return 'mobile';
    }
    
    if (strpos($userAgent, 'tablet') !== false || strpos($userAgent, 'ipad') !== false) {
        return 'tablet';
    }
    
    return 'desktop';
}

/**
 * Get current page URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
}

/**
 * Generate SEO-friendly slug
 */
function generateSeoSlug($text) {
    // Convert to lowercase
    $text = strtolower($text);
    
    // Replace spaces with hyphens
    $text = str_replace(' ', '-', $text);
    
    // Remove special characters
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);
    
    // Remove multiple hyphens
    $text = preg_replace('/-+/', '-', $text);
    
    // Trim hyphens from ends
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Truncate text for meta description (160 chars)
 */
function truncateMetaDescription($text, $length = 160) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $lastSpace = strrpos($text, ' ');
    
    if ($lastSpace !== false) {
        $text = substr($text, 0, $lastSpace);
    }
    
    return $text . '...';
}
