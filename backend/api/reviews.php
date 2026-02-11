<?php
/**
 * Product Reviews API
 * Handles CRUD operations for product reviews
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/input_sanitizer.php';

header('Content-Type: application/json');

// Database connection
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'dailycup_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get request method and parameters
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Get product_id from query string
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
$review_id = isset($_GET['review_id']) ? intval($_GET['review_id']) : null;

try {
    switch ($method) {
        case 'GET':
            if ($review_id) {
                // Get single review
                getSingleReview($db, $review_id);
            } elseif ($product_id) {
                // Get all reviews for a product
                getProductReviews($db, $product_id);
            } else {
                // Get all reviews (admin)
                getAllReviews($db);
            }
            break;

        case 'POST':
            // Create new review (requires authentication)
            $userData = validateToken();
            createReview($db, $input, $userData['user_id']);
            break;

        case 'PUT':
            // Update review (requires authentication)
            $userData = validateToken();
            updateReview($db, $review_id, $input, $userData['user_id']);
            break;

        case 'DELETE':
            // Delete review (requires authentication)
            $userData = validateToken();
            deleteReview($db, $review_id, $userData['user_id']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Get all reviews for a product
 */
function getProductReviews($db, $product_id) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'recent'; // recent, helpful, rating_high, rating_low

    // Whitelist sort options to prevent SQL injection
    $validSorts = [
        'recent' => 'pr.created_at DESC',
        'helpful' => 'pr.helpful_count DESC, pr.created_at DESC',
        'rating_high' => 'pr.rating DESC, pr.created_at DESC',
        'rating_low' => 'pr.rating ASC, pr.created_at DESC'
    ];
    
    $orderBy = isset($validSorts[$sort]) ? $validSorts[$sort] : $validSorts['recent'];

    // Get reviews - use string concatenation for ORDER BY (safe because whitelisted)
    // Use string concatenation for LIMIT/OFFSET too since PDO binds them as strings
    $query = "SELECT 
                pr.id,
                pr.product_id,
                pr.user_id,
                pr.rating,
                pr.review_title,
                pr.review_text,
                pr.helpful_count,
                pr.verified_purchase,
                pr.status,
                pr.created_at,
                pr.updated_at,
                u.name as user_name,
                u.email as user_email
              FROM product_reviews pr
              LEFT JOIN users u ON pr.user_id = u.id
              WHERE pr.product_id = ?AND pr.status = 'approved'
              ORDER BY " . $orderBy . "
              LIMIT " . $limit . " OFFSET " . $offset;

    $stmt = $db->prepare($query);
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM product_reviews WHERE product_id = ? AND status = 'approved'";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute([$product_id]);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get rating summary
    $summaryQuery = "SELECT * FROM product_ratings_summary WHERE product_id = ?";
    $summaryStmt = $db->prepare($summaryQuery);
    $summaryStmt->execute([$product_id]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'summary' => $summary ?: [
            'product_id' => $product_id,
            'total_reviews' => 0,
            'average_rating' => 0,
            'five_star' => 0,
            'four_star' => 0,
            'three_star' => 0,
            'two_star' => 0,
            'one_star' => 0
        ],
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => intval($total),
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get single review
 */
function getSingleReview($db, $review_id) {
    $query = "SELECT 
                pr.*,
                u.name as user_name,
                u.email as user_email
              FROM product_reviews pr
              LEFT JOIN users u ON pr.user_id = u.id
              WHERE pr.id = ?";

    $stmt = $db->prepare($query);
    $stmt->execute([$review_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($review) {
        echo json_encode(['success' => true, 'review' => $review]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Review not found']);
    }
}

/**
 * Get all reviews (admin only)
 */
function getAllReviews($db) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;

    $query = "SELECT 
                pr.*,
                u.name as user_name,
                u.email as user_email
              FROM product_reviews pr
              LEFT JOIN users u ON pr.user_id = u.id
              ORDER BY pr.created_at DESC
              LIMIT ? OFFSET ?";

    $stmt = $db->prepare($query);
    $stmt->execute([$limit, $offset]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count
    $countStmt = $db->query("SELECT COUNT(*) as total FROM product_reviews");
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => intval($total),
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Create new review
 */
function createReview($db, $input, $user_id) {
    // Validate required fields
    $required = ['product_id', 'rating', 'review_title', 'review_text'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }

    // Sanitize input
    $product_id = intval($input['product_id']);
    $rating = intval($input['rating']);
    $review_title = sanitizeInput($input['review_title']);
    $review_text = sanitizeInput($input['review_text']);

    // Validate rating range
    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Rating must be between 1 and 5']);
        return;
    }

    // Check if user already reviewed this product
    $checkQuery = "SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$product_id, $user_id]);
    if ($checkStmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'You have already reviewed this product']);
        return;
    }

    // Check if user purchased this product (optional)
    $verifiedPurchase = false;
    $purchaseQuery = "SELECT COUNT(*) as count FROM orders o
                      JOIN order_items oi ON o.id = oi.order_id
                      WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status = 'paid'";
    $purchaseStmt = $db->prepare($purchaseQuery);
    $purchaseStmt->execute([$user_id, $product_id]);
    $purchaseCount = $purchaseStmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($purchaseCount > 0) {
        $verifiedPurchase = true;
    }

    // Insert review
    $query = "INSERT INTO product_reviews (product_id, user_id, rating, review_title, review_text, verified_purchase, status) 
              VALUES (?, ?, ?, ?, ?, ?, 'approved')";
    $stmt = $db->prepare($query);
    $stmt->execute([$product_id, $user_id, $rating, $review_title, $review_text, $verifiedPurchase]);

    $review_id = $db->lastInsertId();

    // Get the created review
    $getQuery = "SELECT pr.*, u.name as user_name FROM product_reviews pr 
                 LEFT JOIN users u ON pr.user_id = u.id WHERE pr.id = ?";
    $getStmt = $db->prepare($getQuery);
    $getStmt->execute([$review_id]);
    $review = $getStmt->fetch(PDO::FETCH_ASSOC);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Review created successfully',
        'review' => $review
    ]);
}

/**
 * Update review
 */
function updateReview($db, $review_id, $input, $user_id) {
    if (!$review_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Review ID is required']);
        return;
    }

    // Check if review exists and belongs to user
    $checkQuery = "SELECT * FROM product_reviews WHERE id = ? AND user_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$review_id, $user_id]);
    $existingReview = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingReview) {
        http_response_code(404);
        echo json_encode(['error' => 'Review not found or unauthorized']);
        return;
    }

    // Build update query
    $updates = [];
    $params = [];

    if (isset($input['rating'])) {
        $rating = intval($input['rating']);
        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Rating must be between 1 and 5']);
            return;
        }
        $updates[] = 'rating = ?';
        $params[] = $rating;
    }

    if (isset($input['review_title'])) {
        $updates[] = 'review_title = ?';
        $params[] = sanitizeInput($input['review_title']);
    }

    if (isset($input['review_text'])) {
        $updates[] = 'review_text = ?';
        $params[] = sanitizeInput($input['review_text']);
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }

    $params[] = $review_id;
    $query = "UPDATE product_reviews SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute($params);

    // Get updated review
    $getQuery = "SELECT pr.*, u.name as user_name FROM product_reviews pr 
                 LEFT JOIN users u ON pr.user_id = u.id WHERE pr.id = ?";
    $getStmt = $db->prepare($getQuery);
    $getStmt->execute([$review_id]);
    $review = $getStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Review updated successfully',
        'review' => $review
    ]);
}

/**
 * Delete review
 */
function deleteReview($db, $review_id, $user_id) {
    if (!$review_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Review ID is required']);
        return;
    }

    // Check if review exists and belongs to user
    $checkQuery = "SELECT * FROM product_reviews WHERE id = ? AND user_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$review_id, $user_id]);
    $existingReview = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingReview) {
        http_response_code(404);
        echo json_encode(['error' => 'Review not found or unauthorized']);
        return;
    }

    // Delete review
    $query = "DELETE FROM product_reviews WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$review_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Review deleted successfully'
    ]);
}
