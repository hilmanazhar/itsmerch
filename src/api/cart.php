<?php
// src/api/cart.php
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    if ($user_id <= 0) json_err('Invalid User ID');

    // Updated query to include variant information
    $sql = "SELECT c.id, c.product_id, c.variant_id, c.quantity, 
                   p.name, p.price, p.image_url,
                   pv.price_adjustment, pv.stock as variant_stock,
                   CONCAT(
                       COALESCE(so.display_value, ''), 
                       IF(so.display_value IS NOT NULL AND co.display_value IS NOT NULL, ' - ', ''),
                       COALESCE(co.display_value, '')
                   ) as variant_info
            FROM carts c 
            JOIN products p ON c.product_id = p.id 
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            LEFT JOIN variant_options so ON pv.size_option_id = so.id
            LEFT JOIN variant_options co ON pv.color_option_id = co.id
            WHERE c.user_id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($row = $res->fetch_assoc()) {
        // Calculate final price with variant adjustment
        $row['price'] = floatval($row['price']) + floatval($row['price_adjustment'] ?? 0);
        unset($row['price_adjustment']);
        $items[] = $row;
    }
    json_ok(['success' => true, 'items' => $items]);

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = intval($input['user_id'] ?? 0);
    $product_id = intval($input['product_id'] ?? 0);
    $variant_id = isset($input['variant_id']) && $input['variant_id'] !== '' ? intval($input['variant_id']) : null;
    $qty = intval($input['quantity'] ?? 1);
    $action = $input['action'] ?? 'add'; // add or update

    if (!$user_id || !$product_id) json_err('Invalid Data');

    // Check if this exact product+variant combo exists in cart
    if ($variant_id) {
        $stmt = $mysqli->prepare("SELECT id, quantity FROM carts WHERE user_id=? AND product_id=? AND variant_id=?");
        $stmt->bind_param('iii', $user_id, $product_id, $variant_id);
    } else {
        $stmt = $mysqli->prepare("SELECT id, quantity FROM carts WHERE user_id=? AND product_id=? AND variant_id IS NULL");
        $stmt->bind_param('ii', $user_id, $product_id);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        // Update existing
        $row = $res->fetch_assoc();
        $new_qty = ($action === 'add') ? $row['quantity'] + $qty : $qty;
        $stmt = $mysqli->prepare("UPDATE carts SET quantity=? WHERE id=?");
        $stmt->bind_param('ii', $new_qty, $row['id']);
    } else {
        // Insert new
        if ($variant_id) {
            $stmt = $mysqli->prepare("INSERT INTO carts (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiii', $user_id, $product_id, $variant_id, $qty);
        } else {
            $stmt = $mysqli->prepare("INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param('iii', $user_id, $product_id, $qty);
        }
    }
    
    if ($stmt->execute()) {
        json_ok(['success' => true]);
    } else {
        json_err('Failed to update cart');
    }

} elseif ($method === 'DELETE') {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    $variant_id = isset($_GET['variant_id']) ? intval($_GET['variant_id']) : null;
    $cart_id = isset($_GET['cart_id']) ? intval($_GET['cart_id']) : 0;

    if ($cart_id) {
        // Delete by cart ID (most specific)
        $stmt = $mysqli->prepare("DELETE FROM carts WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $cart_id, $user_id);
        $stmt->execute();
        json_ok(['success' => true]);
    } elseif ($user_id && $product_id) {
        // Delete by product_id + optional variant_id
        if ($variant_id) {
            $stmt = $mysqli->prepare("DELETE FROM carts WHERE user_id=? AND product_id=? AND variant_id=?");
            $stmt->bind_param('iii', $user_id, $product_id, $variant_id);
        } else {
            $stmt = $mysqli->prepare("DELETE FROM carts WHERE user_id=? AND product_id=?");
            $stmt->bind_param('ii', $user_id, $product_id);
        }
        $stmt->execute();
        json_ok(['success' => true]);
    } else {
        json_err('Invalid params');
    }
}
?>
