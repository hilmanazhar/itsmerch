<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Get reviews for a product or by user
if ($method === 'GET') {
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    try {
        if ($product_id > 0) {
            // Get all reviews for a product
            $stmt = $pdo->prepare("
                SELECT r.id, r.product_id, r.user_id, r.rating, r.review_text, r.created_at,
                       u.name as user_name
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.product_id = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$product_id]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get product rating summary
            $summaryStmt = $pdo->prepare("
                SELECT AVG(rating) as average_rating, COUNT(*) as review_count,
                       SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                       SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                       SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                       SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                       SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM reviews WHERE product_id = ?
            ");
            $summaryStmt->execute([$product_id]);
            $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'reviews' => $reviews,
                'summary' => [
                    'average_rating' => round($summary['average_rating'] ?? 0, 1),
                    'review_count' => (int)($summary['review_count'] ?? 0),
                    'distribution' => [
                        5 => (int)($summary['five_star'] ?? 0),
                        4 => (int)($summary['four_star'] ?? 0),
                        3 => (int)($summary['three_star'] ?? 0),
                        2 => (int)($summary['two_star'] ?? 0),
                        1 => (int)($summary['one_star'] ?? 0)
                    ]
                ]
            ]);
        } elseif ($user_id > 0) {
            // Get all reviews by a user
            $stmt = $pdo->prepare("
                SELECT r.id, r.product_id, r.rating, r.review_text, r.created_at,
                       p.name as product_name, p.image_url
                FROM reviews r
                JOIN products p ON r.product_id = p.id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'reviews' => $reviews]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Product ID or User ID required']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// POST - Add new review
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $user_id = $input['user_id'] ?? 0;
    $product_id = $input['product_id'] ?? 0;
    $order_id = $input['order_id'] ?? null;
    $rating = $input['rating'] ?? 0;
    $review_text = trim($input['review_text'] ?? '');
    
    if (!$user_id || !$product_id || !$rating) {
        echo json_encode(['success' => false, 'error' => 'User ID, Product ID, and Rating required']);
        exit;
    }
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
        exit;
    }
    
    try {
        // Check if user already reviewed this product
        $check = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
        $check->execute([$user_id, $product_id]);
        
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'error' => 'You have already reviewed this product']);
            exit;
        }
        
        // Verify user has purchased this product (optional, but recommended)
        $purchaseCheck = $pdo->prepare("
            SELECT o.id FROM orders o
            JOIN order_details od ON o.id = od.order_id
            WHERE o.user_id = ? AND od.product_id = ? AND o.status = 'Selesai'
            LIMIT 1
        ");
        $purchaseCheck->execute([$user_id, $product_id]);
        $purchase = $purchaseCheck->fetch();
        
        if (!$purchase && $order_id === null) {
            // Allow review but without order_id verification for testing
            // In production, you may want to enforce this
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO reviews (user_id, product_id, order_id, rating, review_text) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([$user_id, $product_id, $order_id, $rating, $review_text]);
        
        if ($result) {
            // Update product average rating (backup in case trigger doesn't work)
            $updateStmt = $pdo->prepare("
                UPDATE products 
                SET average_rating = (SELECT AVG(rating) FROM reviews WHERE product_id = ?),
                    review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ?)
                WHERE id = ?
            ");
            $updateStmt->execute([$product_id, $product_id, $product_id]);
            
            echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to submit review']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// PUT - Update existing review
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $review_id = $input['id'] ?? 0;
    $user_id = $input['user_id'] ?? 0;
    $rating = $input['rating'] ?? 0;
    $review_text = trim($input['review_text'] ?? '');
    
    if (!$review_id || !$user_id || !$rating) {
        echo json_encode(['success' => false, 'error' => 'Review ID, User ID, and Rating required']);
        exit;
    }
    
    try {
        // Verify ownership
        $check = $pdo->prepare("SELECT product_id FROM reviews WHERE id = ? AND user_id = ?");
        $check->execute([$review_id, $user_id]);
        $review = $check->fetch();
        
        if (!$review) {
            echo json_encode(['success' => false, 'error' => 'Review not found or unauthorized']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, review_text = ? WHERE id = ?");
        $result = $stmt->execute([$rating, $review_text, $review_id]);
        
        if ($result) {
            // Update product average rating
            $updateStmt = $pdo->prepare("
                UPDATE products 
                SET average_rating = (SELECT AVG(rating) FROM reviews WHERE product_id = ?),
                    review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ?)
                WHERE id = ?
            ");
            $updateStmt->execute([$review['product_id'], $review['product_id'], $review['product_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Review updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update review']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// DELETE - Remove review
if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input);
    if (empty($input)) {
        $input = $_GET;
    }
    
    $review_id = $input['id'] ?? 0;
    $user_id = $input['user_id'] ?? 0;
    
    if (!$review_id || !$user_id) {
        echo json_encode(['success' => false, 'error' => 'Review ID and User ID required']);
        exit;
    }
    
    try {
        // Verify ownership and get product_id
        $check = $pdo->prepare("SELECT product_id FROM reviews WHERE id = ? AND user_id = ?");
        $check->execute([$review_id, $user_id]);
        $review = $check->fetch();
        
        if (!$review) {
            echo json_encode(['success' => false, 'error' => 'Review not found or unauthorized']);
            exit;
        }
        
        $product_id = $review['product_id'];
        
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$review_id, $user_id]);
        
        if ($result) {
            // Update product average rating
            $updateStmt = $pdo->prepare("
                UPDATE products 
                SET average_rating = COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = ?), 0),
                    review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ?)
                WHERE id = ?
            ");
            $updateStmt->execute([$product_id, $product_id, $product_id]);
            
            echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete review']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid method']);
