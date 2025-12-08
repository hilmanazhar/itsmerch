<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Get user's wishlist
if ($method === 'GET') {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT w.id, w.product_id, w.created_at,
                   p.name, p.description, p.price, p.stock, p.image_url, p.category_id,
                   c.name as category_name
            FROM wishlists w
            JOIN products p ON w.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'items' => $items]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// POST - Add to wishlist
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $user_id = $input['user_id'] ?? 0;
    $product_id = $input['product_id'] ?? 0;
    
    if (!$user_id || !$product_id) {
        echo json_encode(['success' => false, 'error' => 'User ID and Product ID required']);
        exit;
    }
    
    try {
        // Check if already in wishlist
        $check = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
        $check->execute([$user_id, $product_id]);
        
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Already in wishlist']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
        $result = $stmt->execute([$user_id, $product_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// DELETE - Remove from wishlist
if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input);
    if (empty($input)) {
        $input = $_GET;
    }
    
    $user_id = $input['user_id'] ?? 0;
    $product_id = $input['product_id'] ?? 0;
    
    if (!$user_id || !$product_id) {
        echo json_encode(['success' => false, 'error' => 'User ID and Product ID required']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
        $result = $stmt->execute([$user_id, $product_id]);
        
        echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid method']);
