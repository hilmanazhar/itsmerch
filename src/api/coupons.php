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

// GET - Get all coupons (admin) or validate a coupon code
if ($method === 'GET') {
    $code = isset($_GET['code']) ? trim(strtoupper($_GET['code'])) : null;
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $subtotal = isset($_GET['subtotal']) ? floatval($_GET['subtotal']) : 0;
    $admin = isset($_GET['admin']) && $_GET['admin'] === 'true';
    
    try {
        if ($admin) {
            // Admin: Get all coupons
            $stmt = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC");
            $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'coupons' => $coupons]);
            exit;
        }
        
        if (!$code) {
            echo json_encode(['success' => false, 'error' => 'Coupon code required']);
            exit;
        }
        
        // Validate coupon
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            echo json_encode(['success' => false, 'error' => 'Kode kupon tidak valid']);
            exit;
        }
        
        // Check validity dates
        $now = new DateTime();
        if ($coupon['valid_from'] && new DateTime($coupon['valid_from']) > $now) {
            echo json_encode(['success' => false, 'error' => 'Kupon belum berlaku']);
            exit;
        }
        if ($coupon['valid_until'] && new DateTime($coupon['valid_until']) < $now) {
            echo json_encode(['success' => false, 'error' => 'Kupon sudah kadaluarsa']);
            exit;
        }
        
        // Check usage limit
        if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
            echo json_encode(['success' => false, 'error' => 'Kupon sudah habis digunakan']);
            exit;
        }
        
        // Check minimum purchase
        if ($subtotal < $coupon['min_purchase']) {
            echo json_encode([
                'success' => false, 
                'error' => 'Belanja minimal Rp ' . number_format($coupon['min_purchase'], 0, ',', '.') . ' untuk menggunakan kupon ini'
            ]);
            exit;
        }
        
        // Check if user already used this coupon (optional: one-time per user)
        if ($user_id) {
            $usageCheck = $pdo->prepare("SELECT id FROM coupon_usages WHERE coupon_id = ? AND user_id = ?");
            $usageCheck->execute([$coupon['id'], $user_id]);
            if ($usageCheck->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Anda sudah pernah menggunakan kupon ini']);
                exit;
            }
        }
        
        // Calculate discount
        $discount = 0;
        if ($coupon['type'] === 'percentage') {
            $discount = $subtotal * ($coupon['value'] / 100);
            if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
        } else {
            $discount = $coupon['value'];
        }
        
        $discount = round($discount, 0);
        
        echo json_encode([
            'success' => true,
            'coupon' => [
                'id' => $coupon['id'],
                'code' => $coupon['code'],
                'type' => $coupon['type'],
                'value' => $coupon['value'],
                'description' => $coupon['description']
            ],
            'discount' => $discount,
            'message' => 'Kupon berhasil diterapkan! Diskon Rp ' . number_format($discount, 0, ',', '.')
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// POST - Create new coupon (admin) or apply coupon to order
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // If creating a new coupon (admin)
    if (isset($input['action']) && $input['action'] === 'create') {
        $code = strtoupper(trim($input['code'] ?? ''));
        $type = $input['type'] ?? 'percentage';
        $value = floatval($input['value'] ?? 0);
        $min_purchase = floatval($input['min_purchase'] ?? 0);
        $max_discount = isset($input['max_discount']) && $input['max_discount'] !== '' ? floatval($input['max_discount']) : null;
        $usage_limit = isset($input['usage_limit']) && $input['usage_limit'] !== '' ? intval($input['usage_limit']) : null;
        $valid_from = $input['valid_from'] ?? null;
        $valid_until = $input['valid_until'] ?? null;
        $description = trim($input['description'] ?? '');
        
        if (!$code || $value <= 0) {
            echo json_encode(['success' => false, 'error' => 'Code and value required']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO coupons (code, type, value, min_purchase, max_discount, usage_limit, valid_from, valid_until, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$code, $type, $value, $min_purchase, $max_discount, $usage_limit, $valid_from, $valid_until, $description]);
            
            if ($result) {
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to create coupon']);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'error' => 'Kode kupon sudah ada']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error']);
            }
        }
        exit;
    }
    
    // Record coupon usage (called when order is placed)
    if (isset($input['action']) && $input['action'] === 'use') {
        $coupon_id = intval($input['coupon_id'] ?? 0);
        $user_id = intval($input['user_id'] ?? 0);
        $order_id = intval($input['order_id'] ?? 0);
        $discount_amount = floatval($input['discount_amount'] ?? 0);
        
        if (!$coupon_id || !$user_id || !$order_id) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }
        
        try {
            // Record usage
            $stmt = $pdo->prepare("INSERT INTO coupon_usages (coupon_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)");
            $stmt->execute([$coupon_id, $user_id, $order_id, $discount_amount]);
            
            // Update used_count
            $updateStmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
            $updateStmt->execute([$coupon_id]);
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// PUT - Update coupon (admin)
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Coupon ID required']);
        exit;
    }
    
    try {
        // If only updating is_active status
        if (isset($input['is_active']) && count($input) === 2) {
            $is_active = $input['is_active'] ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE coupons SET is_active = ? WHERE id = ?");
            $stmt->execute([$is_active, $id]);
        } 
        // Full update
        else {
            $code = strtoupper(trim($input['code'] ?? ''));
            $type = $input['type'] ?? 'percentage';
            $value = floatval($input['value'] ?? 0);
            $min_purchase = floatval($input['min_purchase'] ?? 0);
            $max_discount = isset($input['max_discount']) && $input['max_discount'] !== '' ? floatval($input['max_discount']) : null;
            $usage_limit = isset($input['usage_limit']) && $input['usage_limit'] !== '' ? intval($input['usage_limit']) : null;
            $valid_from = $input['valid_from'] ?? null;
            $valid_until = $input['valid_until'] ?? null;
            $description = trim($input['description'] ?? '');
            
            if (!$code || $value <= 0) {
                echo json_encode(['success' => false, 'error' => 'Code and value required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                UPDATE coupons SET 
                code = ?, type = ?, value = ?, min_purchase = ?, max_discount = ?, 
                usage_limit = ?, valid_from = ?, valid_until = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([$code, $type, $value, $min_purchase, $max_discount, $usage_limit, $valid_from, $valid_until, $description, $id]);
    }
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'error' => 'Kode kupon sudah ada']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }
    exit;
}

// DELETE - Delete coupon (admin)
if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input);
    if (empty($input)) {
        $input = $_GET;
    }
    
    $id = intval($input['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Coupon ID required']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid method']);
