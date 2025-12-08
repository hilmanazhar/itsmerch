<?php
// src/api/checkout.php
// Handle checkout/order creation with Midtrans Snap integration
require 'db.php';
require 'midtrans_config.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? 0;
$address_id = $input['address_id'] ?? 0;
$courier = $input['courier'] ?? '';
$shipping_cost = $input['shipping_cost'] ?? 0; // Dynamic shipping cost from RajaOngkir
$items = $input['items'] ?? [];

if (!$user_id || empty($items)) {
    json_err('Data pesanan tidak lengkap');
}

// Get user details
$stmt = $mysqli->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    json_err('User tidak ditemukan');
}

// Get address details
$shipping_address = '';
if ($address_id) {
    $stmt = $mysqli->prepare("SELECT recipient_name, phone, address_detail FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $address_id, $user_id);
    $stmt->execute();
    $addr = $stmt->get_result()->fetch_assoc();
    if ($addr) {
        $shipping_address = $addr['recipient_name'] . ' (' . $addr['phone'] . ') - ' . $addr['address_detail'];
    }
}

// Shipping cost comes from frontend (calculated via RajaOngkir)
$shipping_cost = intval($shipping_cost);

// Calculate total from items
$subtotal = 0;
$item_details = [];
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    
    // Get product name for Midtrans
    $stmt = $mysqli->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->bind_param('i', $item['product_id']);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    $item_details[] = [
        'id' => 'PROD-' . $item['product_id'],
        'price' => (int)$item['price'],
        'quantity' => (int)$item['quantity'],
        'name' => $product['name'] ?? 'Product'
    ];
}

// Add shipping as item
if ($shipping_cost > 0) {
    $item_details[] = [
        'id' => 'SHIPPING',
        'price' => (int)$shipping_cost,
        'quantity' => 1,
        'name' => 'Ongkos Kirim (' . strtoupper($courier) . ')'
    ];
}

// Handle Discount
$discount_amount = floatval($input['discount_amount'] ?? 0);
$coupon_id = intval($input['coupon_id'] ?? 0);
$coupon_code = null;

if ($discount_amount > 0) {
    // Verify coupon validity/code if coupon_id is present
    if ($coupon_id) {
        $stmt_coupon = $mysqli->prepare("SELECT code FROM coupons WHERE id = ?");
        $stmt_coupon->bind_param('i', $coupon_id);
        $stmt_coupon->execute();
        $res_coupon = $stmt_coupon->get_result()->fetch_assoc();
        if ($res_coupon) {
            $coupon_code = $res_coupon['code'];
        }
    }

    // Add discount as a negative item for Midtrans
    $item_details[] = [
        'id' => 'DISCOUNT',
        'price' => -(int)$discount_amount,
        'quantity' => 1,
        'name' => 'Diskon Kupon' . ($coupon_code ? " ($coupon_code)" : '')
    ];
}

$total_amount = $subtotal + $shipping_cost - $discount_amount;
if ($total_amount < 0) $total_amount = 0; // Prevent negative total

$mysqli->begin_transaction();

try {
    // Generate unique order ID
    $order_id_prefix = 'ITS-' . date('Ymd') . '-' . $user_id . '-';
    
    // Insert into orders first to get the order ID
    $stmt = $mysqli->prepare("INSERT INTO orders (user_id, status, total_amount, shipping_cost, discount_amount, coupon_code, shipping_address, shipping_courier, payment_method, payment_status) VALUES (?, 'pending', ?, ?, ?, ?, ?, ?, 'midtrans', 'unpaid')");
    $stmt->bind_param('idddsss', $user_id, $total_amount, $shipping_cost, $discount_amount, $coupon_code, $shipping_address, $courier);
    $stmt->execute();
    $db_order_id = $stmt->insert_id;
    
    // Create Midtrans order ID
    $midtrans_order_id = $order_id_prefix . $db_order_id;
    
    // Insert order details
    $stmt_detail = $mysqli->prepare("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt_detail->bind_param('iiid', $db_order_id, $item['product_id'], $item['quantity'], $item['price']);
        $stmt_detail->execute();
        
        // Reduce stock
        $stmt_stock = $mysqli->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt_stock->bind_param('ii', $item['quantity'], $item['product_id']);
        $stmt_stock->execute();
    }

    // Prepare Midtrans transaction parameters
    $midtrans_params = [
        'transaction_details' => [
            'order_id' => $midtrans_order_id,
            'gross_amount' => (int)$total_amount
        ],
        'customer_details' => [
            'first_name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? ''
        ],
        'item_details' => $item_details,
        'callbacks' => [
            'finish' => 'http://localhost/its-merch-bootstrap/src/index.html?order_success=1'
        ]
    ];
    
    // Get Snap Token from Midtrans
    $snap_response = getSnapToken($midtrans_params);
    
    if (isset($snap_response['error'])) {
        throw new Exception('Midtrans Error: ' . (is_array($snap_response['error']) ? implode(', ', $snap_response['error']) : $snap_response['error']));
    }
    
    $snap_token = $snap_response['token'] ?? null;
    
    if (!$snap_token) {
        throw new Exception('Failed to get Snap token');
    }
    
    // Update order with snap token and midtrans order id
    $stmt = $mysqli->prepare("UPDATE orders SET snap_token = ? WHERE id = ?");
    $stmt->bind_param('si', $snap_token, $db_order_id);
    $stmt->execute();

    // Clear cart
    $stmt_cart = $mysqli->prepare("DELETE FROM carts WHERE user_id = ?");
    $stmt_cart->bind_param('i', $user_id);
    $stmt_cart->execute();

    $mysqli->commit();
    
    json_ok([
        'success' => true, 
        'order_id' => $db_order_id,
        'snap_token' => $snap_token,
        'client_key' => MIDTRANS_CLIENT_KEY
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    json_err('Gagal membuat pesanan: ' . $e->getMessage());
}
?>
