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

// GET - Get all images for a product
if ($method === 'GET') {
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    
    if (!$product_id) {
        echo json_encode(['success' => false, 'error' => 'Product ID required']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, image_url, is_primary, sort_order 
            FROM product_images 
            WHERE product_id = ? 
            ORDER BY is_primary DESC, sort_order ASC
        ");
        $stmt->execute([$product_id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'images' => $images
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// POST - Add a new image to product
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $product_id = intval($input['product_id'] ?? 0);
    $image_url = trim($input['image_url'] ?? '');
    $is_primary = isset($input['is_primary']) && $input['is_primary'] ? 1 : 0;
    $sort_order = intval($input['sort_order'] ?? 0);
    
    if (!$product_id || !$image_url) {
        echo json_encode(['success' => false, 'error' => 'Product ID and image URL required']);
        exit;
    }
    
    try {
        // If setting as primary, remove primary from other images
        if ($is_primary) {
            $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?")->execute([$product_id]);
            
            // Also update the main product image_url
            $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?")->execute([$image_url, $product_id]);
        }
        
        // Get next sort order if not specified
        if ($sort_order === 0) {
            $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM product_images WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $result = $stmt->fetch();
            $sort_order = ($result['max_order'] ?? 0) + 1;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO product_images (product_id, image_url, is_primary, sort_order) 
            VALUES (?, ?, ?, ?)
        ");
        $result = $stmt->execute([$product_id, $image_url, $is_primary, $sort_order]);
        
        if ($result) {
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add image']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// DELETE - Remove an image
if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input);
    if (empty($input)) {
        $input = $_GET;
    }
    
    $id = intval($input['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Image ID required']);
        exit;
    }
    
    try {
        // Check if it's the primary image
        $checkStmt = $pdo->prepare("SELECT product_id, is_primary FROM product_images WHERE id = ?");
        $checkStmt->execute([$id]);
        $image = $checkStmt->fetch();
        
        if (!$image) {
            echo json_encode(['success' => false, 'error' => 'Image not found']);
            exit;
        }
        
        // Delete the image
        $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
        $stmt->execute([$id]);
        
        // If it was primary, set another image as primary
        if ($image['is_primary']) {
            $nextStmt = $pdo->prepare("
                SELECT id, image_url FROM product_images 
                WHERE product_id = ? 
                ORDER BY sort_order ASC 
                LIMIT 1
            ");
            $nextStmt->execute([$image['product_id']]);
            $nextImage = $nextStmt->fetch();
            
            if ($nextImage) {
                $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?")->execute([$nextImage['id']]);
                $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?")->execute([$nextImage['image_url'], $image['product_id']]);
            }
        }
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid method']);
