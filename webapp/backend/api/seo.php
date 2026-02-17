<?php
require_once __DIR__ . '/cors.php';
/**
 * SEO API Endpoint
 * Handles sitemap generation, robots.txt, meta tags retrieval, and SEO redirects
 * 
 * Endpoints:
 * - GET /sitemap.xml - Generate XML sitemap
 * - GET /robots.txt - Generate robots.txt file
 * - GET ?action=get_meta&slug=menu - Get SEO metadata for a page
 * - POST ?action=update_meta - Update SEO metadata (admin only)
 * - GET ?action=check_redirect&url=/old-page - Check if redirect exists
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Handle special endpoints (non-JSON responses)
if (strpos($_SERVER['REQUEST_URI'], '/sitemap.xml') !== false || $action === 'sitemap') {
    generateSitemap();
    exit;
}

if (strpos($_SERVER['REQUEST_URI'], '/robots.txt') !== false || $action === 'robots') {
    generateRobotsTxt();
    exit;
}

// JSON API endpoints
switch ($method) {
    case 'GET':
        handleGet($action);
        break;
    case 'POST':
        handlePost($action);
        break;
    default:
        sendResponse(405, false, 'Method not allowed');
}

/**
 * Handle GET requests
 */
function handleGet($action) {
    switch ($action) {
        case 'get_meta':
            getMetadata();
            break;
        case 'check_redirect':
            checkRedirect();
            break;
        case 'analytics':
            getSeoAnalytics();
            break;
        default:
            sendResponse(400, false, 'Invalid action');
    }
}

/**
 * Handle POST requests
 */
function handlePost($action) {
    // Require authentication for POST operations
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        sendResponse(403, false, 'Unauthorized');
        return;
    }
    
    switch ($action) {
        case 'update_meta':
            updateMetadata();
            break;
        case 'add_redirect':
            addRedirect();
            break;
        case 'regenerate_sitemap':
            regenerateSitemap();
            break;
        default:
            sendResponse(400, false, 'Invalid action');
    }
}

/**
 * Generate XML Sitemap
 */
function generateSitemap() {
    $conn = Database::getConnection();
    
    header('Content-Type: application/xml; charset=utf-8');
    
    $baseUrl = 'https://dailycup.com'; // Change to your domain
    
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Get sitemap configuration
    $configQuery = "SELECT * FROM sitemap_config WHERE include_in_sitemap = TRUE";
    $configResult = mysqli_query($conn, $configQuery);
    $configs = [];
    while ($config = mysqli_fetch_assoc($configResult)) {
        $configs[$config['entity_type']] = $config;
    }
    
    // Add static pages
    $staticPages = [
        ['url' => '', 'priority' => 1.0, 'changefreq' => 'daily'],
        ['url' => 'menu', 'priority' => 0.9, 'changefreq' => 'daily'],
        ['url' => 'about', 'priority' => 0.7, 'changefreq' => 'monthly'],
        ['url' => 'contact', 'priority' => 0.6, 'changefreq' => 'monthly']
    ];
    
    foreach ($staticPages as $page) {
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($baseUrl . '/' . $page['url']) . "</loc>\n";
        echo "    <changefreq>" . $page['changefreq'] . "</changefreq>\n";
        echo "    <priority>" . $page['priority'] . "</priority>\n";
        echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        echo "  </url>\n";
    }
    
    // Add dynamic pages from seo_metadata
    $metaQuery = "SELECT slug, updated_at FROM seo_metadata WHERE is_active = TRUE";
    $metaResult = mysqli_query($conn, $metaQuery);
    while ($meta = mysqli_fetch_assoc($metaResult)) {
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($baseUrl . '/' . $meta['slug']) . "</loc>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "    <lastmod>" . date('Y-m-d', strtotime($meta['updated_at'])) . "</lastmod>\n";
        echo "  </url>\n";
    }
    
    // Add products
    if (isset($configs['product'])) {
        $config = $configs['product'];
        $productQuery = "SELECT id, name, updated_at FROM products WHERE is_active = 1 LIMIT 500";
        $productResult = mysqli_query($conn, $productQuery);
        
        while ($product = mysqli_fetch_assoc($productResult)) {
            $slug = strtolower(str_replace(' ', '-', $product['name']));
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($baseUrl . '/product/' . $product['id'] . '-' . $slug) . "</loc>\n";
            echo "    <changefreq>" . $config['change_frequency'] . "</changefreq>\n";
            echo "    <priority>" . $config['priority'] . "</priority>\n";
            echo "    <lastmod>" . date('Y-m-d', strtotime($product['updated_at'])) . "</lastmod>\n";
            echo "  </url>\n";
        }
    }
    
    // Add categories
    if (isset($configs['category'])) {
        $config = $configs['category'];
        $categoryQuery = "SELECT id, name, updated_at FROM categories LIMIT 100";
        $categoryResult = mysqli_query($conn, $categoryQuery);
        
        while ($category = mysqli_fetch_assoc($categoryResult)) {
            $slug = strtolower(str_replace(' ', '-', $category['name']));
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($baseUrl . '/category/' . $slug) . "</loc>\n";
            echo "    <changefreq>" . $config['change_frequency'] . "</changefreq>\n";
            echo "    <priority>" . $config['priority'] . "</priority>\n";
            echo "    <lastmod>" . date('Y-m-d', strtotime($category['updated_at'])) . "</lastmod>\n";
            echo "  </url>\n";
        }
    }
    
    echo '</urlset>';
    
    // Update last generated timestamp
    mysqli_query($conn, "UPDATE sitemap_config SET last_generated_at = NOW()");
}

/**
 * Generate robots.txt
 */
function generateRobotsTxt() {
    header('Content-Type: text/plain; charset=utf-8');
    
    $baseUrl = 'https://dailycup.com'; // Change to your domain
    
    echo "# DailyCup Robots.txt\n";
    echo "# Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    echo "# Allow all bots\n";
    echo "User-agent: *\n";
    echo "Allow: /\n\n";
    
    echo "# Disallow admin and private areas\n";
    echo "Disallow: /admin/\n";
    echo "Disallow: /api/\n";
    echo "Disallow: /includes/\n";
    echo "Disallow: /config/\n";
    echo "Disallow: /database/\n";
    echo "Disallow: /vendor/\n";
    echo "Disallow: /cache/\n";
    echo "Disallow: /logs/\n";
    echo "Disallow: /temp/\n\n";
    
    echo "# Disallow private customer pages\n";
    echo "Disallow: /customer/cart.php\n";
    echo "Disallow: /customer/checkout.php\n";
    echo "Disallow: /customer/profile.php\n";
    echo "Disallow: /customer/orders.php\n\n";
    
    echo "# Disallow search results\n";
    echo "Disallow: /*?search=*\n";
    echo "Disallow: /*?s=*\n\n";
    
    echo "# Allow specific bots with special rules\n";
    echo "User-agent: Googlebot\n";
    echo "Allow: /assets/\n";
    echo "Crawl-delay: 1\n\n";
    
    echo "# Sitemap location\n";
    echo "Sitemap: " . $baseUrl . "/sitemap.xml\n";
}

/**
 * Get metadata for a specific page/entity
 */
function getMetadata() {
    $slug = $_GET['slug'] ?? '';
    $entityType = $_GET['entity_type'] ?? 'page';
    $entityId = $_GET['entity_id'] ?? null;
    
    if (empty($slug) && empty($entityId)) {
        sendResponse(400, false, 'Slug or entity_id required');
        return;
    }
    
    $conn = Database::getConnection();
    
    if ($slug) {
        $slug = mysqli_real_escape_string($conn, $slug);
        $query = "SELECT * FROM seo_metadata WHERE slug = '$slug' AND is_active = TRUE LIMIT 1";
    } else {
        $entityId = (int)$entityId;
        $entityType = mysqli_real_escape_string($conn, $entityType);
        $query = "SELECT * FROM seo_metadata WHERE entity_type = '$entityType' AND entity_id = $entityId AND is_active = TRUE LIMIT 1";
    }
    
    $result = mysqli_query($conn, $query);
    $meta = mysqli_fetch_assoc($result);
    
    if ($meta) {
        // Decode JSON structured data
        if ($meta['structured_data']) {
            $meta['structured_data'] = json_decode($meta['structured_data'], true);
        }
        sendResponse(200, true, 'Metadata retrieved successfully', $meta);
    } else {
        // Return default metadata
        $defaultMeta = [
            'title' => 'DailyCup - Premium Coffee Shop',
            'meta_description' => 'Fresh coffee and delicious food delivered to your door',
            'meta_keywords' => 'coffee, daily cup, coffee shop',
            'robots' => 'index, follow'
        ];
        sendResponse(200, true, 'Using default metadata', $defaultMeta);
    }
}

/**
 * Update SEO metadata (admin only)
 */
function updateMetadata() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['slug']) || !isset($data['title'])) {
        sendResponse(400, false, 'Slug and title are required');
        return;
    }
    
    $conn = Database::getConnection();
    
    $slug = mysqli_real_escape_string($conn, $data['slug']);
    $entityType = mysqli_real_escape_string($conn, $data['entity_type'] ?? 'page');
    $entityId = isset($data['entity_id']) ? (int)$data['entity_id'] : 'NULL';
    $title = mysqli_real_escape_string($conn, $data['title']);
    $metaDesc = mysqli_real_escape_string($conn, $data['meta_description'] ?? '');
    $metaKeywords = mysqli_real_escape_string($conn, $data['meta_keywords'] ?? '');
    $canonicalUrl = mysqli_real_escape_string($conn, $data['canonical_url'] ?? '');
    $ogTitle = mysqli_real_escape_string($conn, $data['og_title'] ?? $data['title']);
    $ogDesc = mysqli_real_escape_string($conn, $data['og_description'] ?? $data['meta_description'] ?? '');
    $ogImage = mysqli_real_escape_string($conn, $data['og_image'] ?? '');
    $ogType = mysqli_real_escape_string($conn, $data['og_type'] ?? 'website');
    $robots = mysqli_real_escape_string($conn, $data['robots'] ?? 'index, follow');
    
    // Handle structured data
    $structuredData = 'NULL';
    if (isset($data['structured_data'])) {
        $structuredData = "'" . mysqli_real_escape_string($conn, json_encode($data['structured_data'])) . "'";
    }
    
    $query = "
        INSERT INTO seo_metadata (
            entity_type, entity_id, slug, title, meta_description, meta_keywords,
            canonical_url, og_title, og_description, og_image, og_type, robots, structured_data
        ) VALUES (
            '$entityType', $entityId, '$slug', '$title', '$metaDesc', '$metaKeywords',
            '$canonicalUrl', '$ogTitle', '$ogDesc', '$ogImage', '$ogType', '$robots', $structuredData
        )
        ON DUPLICATE KEY UPDATE
            title = '$title',
            meta_description = '$metaDesc',
            meta_keywords = '$metaKeywords',
            canonical_url = '$canonicalUrl',
            og_title = '$ogTitle',
            og_description = '$ogDesc',
            og_image = '$ogImage',
            og_type = '$ogType',
            robots = '$robots',
            structured_data = $structuredData,
            updated_at = NOW()
    ";
    
    if (mysqli_query($conn, $query)) {
        sendResponse(200, true, 'SEO metadata updated successfully');
    } else {
        sendResponse(500, false, 'Failed to update metadata: ' . mysqli_error($conn));
    }
}

/**
 * Check if a redirect exists for a URL
 */
function checkRedirect() {
    $url = $_GET['url'] ?? '';
    
    if (empty($url)) {
        sendResponse(400, false, 'URL parameter required');
        return;
    }
    
    $conn = Database::getConnection();
    $url = mysqli_real_escape_string($conn, $url);
    
    $query = "SELECT * FROM seo_redirects WHERE old_url = '$url' AND is_active = TRUE LIMIT 1";
    $result = mysqli_query($conn, $query);
    $redirect = mysqli_fetch_assoc($result);
    
    if ($redirect) {
        // Increment hit count
        mysqli_query($conn, "UPDATE seo_redirects SET hit_count = hit_count + 1 WHERE id = " . $redirect['id']);
        
        sendResponse(200, true, 'Redirect found', [
            'new_url' => $redirect['new_url'],
            'redirect_type' => $redirect['redirect_type']
        ]);
    } else {
        sendResponse(404, false, 'No redirect found');
    }
}

/**
 * Add a new redirect (admin only)
 */
function addRedirect() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['old_url']) || !isset($data['new_url'])) {
        sendResponse(400, false, 'old_url and new_url are required');
        return;
    }
    
    $conn = Database::getConnection();
    
    $oldUrl = mysqli_real_escape_string($conn, $data['old_url']);
    $newUrl = mysqli_real_escape_string($conn, $data['new_url']);
    $redirectType = mysqli_real_escape_string($conn, $data['redirect_type'] ?? '301');
    
    $query = "
        INSERT INTO seo_redirects (old_url, new_url, redirect_type)
        VALUES ('$oldUrl', '$newUrl', '$redirectType')
        ON DUPLICATE KEY UPDATE
            new_url = '$newUrl',
            redirect_type = '$redirectType',
            is_active = TRUE
    ";
    
    if (mysqli_query($conn, $query)) {
        sendResponse(200, true, 'Redirect added successfully');
    } else {
        sendResponse(500, false, 'Failed to add redirect: ' . mysqli_error($conn));
    }
}

/**
 * Get SEO analytics data
 */
function getSeoAnalytics() {
    $conn = Database::getConnection();
    $days = (int)($_GET['days'] ?? 30);
    
    // Top pages by visits
    $topPagesQuery = "
        SELECT page_url, COUNT(*) as visits
        FROM seo_analytics
        WHERE visited_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
        GROUP BY page_url
        ORDER BY visits DESC
        LIMIT 10
    ";
    $topPages = [];
    $result = mysqli_query($conn, $topPagesQuery);
    while ($row = mysqli_fetch_assoc($result)) {
        $topPages[] = $row;
    }
    
    // Top search keywords
    $topKeywordsQuery = "
        SELECT search_keyword, COUNT(*) as count
        FROM seo_analytics
        WHERE search_keyword IS NOT NULL
        AND visited_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
        GROUP BY search_keyword
        ORDER BY count DESC
        LIMIT 10
    ";
    $topKeywords = [];
    $result = mysqli_query($conn, $topKeywordsQuery);
    while ($row = mysqli_fetch_assoc($result)) {
        $topKeywords[] = $row;
    }
    
    // Device breakdown
    $deviceQuery = "
        SELECT device_type, COUNT(*) as count
        FROM seo_analytics
        WHERE visited_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
        GROUP BY device_type
    ";
    $devices = [];
    $result = mysqli_query($conn, $deviceQuery);
    while ($row = mysqli_fetch_assoc($result)) {
        $devices[$row['device_type']] = $row['count'];
    }
    
    sendResponse(200, true, 'Analytics retrieved', [
        'top_pages' => $topPages,
        'top_keywords' => $topKeywords,
        'device_breakdown' => $devices
    ]);
}

/**
 * Regenerate sitemap
 */
function regenerateSitemap() {
    ob_start();
    generateSitemap();
    $sitemap = ob_get_clean();
    
    // Save to file (optional)
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml', $sitemap);
    
    sendResponse(200, true, 'Sitemap regenerated successfully');
}

/**
 * Send JSON response
 */
function sendResponse($code, $success, $message, $data = null) {
    http_response_code($code);
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}
