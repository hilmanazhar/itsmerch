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

// GET - Get variants for a product or all variant options
if ($method === 'GET') {
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    $get_options = isset($_GET['options']) && $_GET['options'] === 'true';
    
    try {
        // Get all variant options (for admin forms)
        if ($get_options) {
            $sizes = $pdo->query("
                SELECT id, value, display_value, sort_order
                FROM variant_options
                WHERE type = 'size'
                ORDER BY sort_order, id
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            $colors = $pdo->query("
                SELECT id, value, display_value, sort_order
                FROM variant_options
                WHERE type = 'color'
                ORDER BY sort_order, id
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'sizes' => $sizes,
                'colors' => $colors
            ]);
            exit;
        }
        
        // Get variants for a specific product
        if (!$product_id) {
            echo json_encode(['success' => false, 'error' => 'Product ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                pv.id,
                pv.sku,
                pv.stock,
                pv.price_adjustment,
                pv.is_active,
                so.id as size_id,
                so.value as size_value,
                so.display_value as size_display,
                co.id as color_id,
                co.value as color_value,
                co.display_value as color_display
            FROM product_variants pv
            LEFT JOIN variant_options so ON pv.size_option_id = so.id AND so.type = 'size'
            LEFT JOIN variant_options co ON pv.color_option_id = co.id AND co.type = 'color'
            WHERE pv.product_id = ? AND pv.is_active = 1
            ORDER BY so.sort_order, co.sort_order
        ");
        $stmt->execute([$product_id]);
        $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get available sizes and colors for this product
        $availableSizes = [];
        $availableColors = [];
        foreach ($variants as $v) {
            if ($v['size_id'] && !in_array($v['size_id'], array_column($availableSizes, 'id'))) {
                $availableSizes[] = ['id' => $v['size_id'], 'value' => $v['size_value'], 'display' => $v['size_display']];
            }
            if ($v['color_id'] && !in_array($v['color_id'], array_column($availableColors, 'id'))) {
                $availableColors[] = ['id' => $v['color_id'], 'value' => $v['color_value'], 'display' => $v['color_display']];
            }
        }
        
        echo json_encode([
            'success' => true,
            'variants' => $variants,
            'available_sizes' => $availableSizes,
            'available_colors' => $availableColors
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}


// POST - Create new variant
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $product_id = intval($input['product_id'] ?? 0);
    $stock = intval($input['stock'] ?? 0);
    $price_adjustment = floatval($input['price_adjustment'] ?? 0);
    $sku = trim($input['sku'] ?? '');
    
    // Handle text-based variant input (admin types comma-separated values)
    $size_text = trim($input['size_text'] ?? '');
    $color_text = trim($input['color_text'] ?? '');
    
    // Handle ID-based variant input (legacy)
    $size_option_id = isset($input['size_option_id']) && $input['size_option_id'] !== '' && $input['size_option_id'] !== 'null' ? intval($input['size_option_id']) : null;
    $color_option_id = isset($input['color_option_id']) && $input['color_option_id'] !== '' && $input['color_option_id'] !== 'null' ? intval($input['color_option_id']) : null;
    
    if (!$product_id) {
        echo json_encode(['success' => false, 'error' => 'Product ID required']);
        exit;
    }
    
    try {
        // If text provided, find or create the variant option
        if ($size_text && !$size_option_id) {
            // Find existing size option or create new
            $stmt = $pdo->prepare("SELECT id FROM variant_options WHERE type = 'size' AND value = ?");
            $stmt->execute([$size_text]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $size_option_id = $existing['id'];
            } else {
                // Create new size option
                $stmt = $pdo->prepare("INSERT INTO variant_options (type, value, display_value) VALUES ('size', ?, ?)");
                $stmt->execute([$size_text, $size_text]);
                $size_option_id = $pdo->lastInsertId();
            }
        }
        
        if ($color_text && !$color_option_id) {
            // Find existing color option or create new
            $stmt = $pdo->prepare("SELECT id FROM variant_options WHERE type = 'color' AND value = ?");
            $stmt->execute([$color_text]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $color_option_id = $existing['id'];
            } else {
                // Create new color option
                $stmt = $pdo->prepare("INSERT INTO variant_options (type, value, display_value) VALUES ('color', ?, ?)");
                $stmt->execute([$color_text, $color_text]);
                $color_option_id = $pdo->lastInsertId();
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO product_variants (product_id, size_option_id, color_option_id, stock, price_adjustment, sku)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE stock = VALUES(stock), price_adjustment = VALUES(price_adjustment), sku = VALUES(sku), is_active = 1
        ");
        $result = $stmt->execute([$product_id, $size_option_id, $color_option_id, $stock, $price_adjustment, $sku]);
        
        // Update product to has_variants = 1
        $pdo->prepare("UPDATE products SET has_variants = 1 WHERE id = ?")->execute([$product_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create variant']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}


// PUT - Update variant stock
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $stock = isset($input['stock']) ? intval($input['stock']) : null;
    $is_active = isset($input['is_active']) ? ($input['is_active'] ? 1 : 0) : null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Variant ID required']);
        exit;
    }
    
    try {
        $updates = [];
        $params = [];
        
        if ($stock !== null) {
            $updates[] = "stock = ?";
            $params[] = $stock;
        }
        if ($is_active !== null) {
            $updates[] = "is_active = ?";
            $params[] = $is_active;
        }
        
        if (!empty($updates)) {
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE product_variants SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($params);
        }
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// DELETE - Remove variant
if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input);
    if (empty($input)) {
        $input = $_GET;
    }
    
    $id = intval($input['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Variant ID required']);
        exit;
    }
    
    try {
        // Soft delete - set is_active to 0
        $stmt = $pdo->prepare("UPDATE product_variants SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// Check variant stock before adding to cart
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'check_stock') {
    $input = json_decode(file_get_contents('php://input'), true);
    $variant_id = intval($input['variant_id'] ?? 0);
    $quantity = intval($input['quantity'] ?? 1);
    
    try {
        $stmt = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ? AND is_active = 1");
        $stmt->execute([$variant_id]);
        $variant = $stmt->fetch();
        
        if (!$variant) {
            echo json_encode(['success' => false, 'error' => 'Variant not found']);
            exit;
        }
        
        if ($variant['stock'] < $quantity) {
            echo json_encode(['success' => false, 'error' => 'Stok tidak mencukupi', 'available' => $variant['stock']]);
            exit;
        }
        
        echo json_encode(['success' => true, 'available' => $variant['stock']]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid method']);
