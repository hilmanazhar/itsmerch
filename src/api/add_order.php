<?php
// src/api/add_order.php
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? 0;
$items = $input['items'] ?? [];
$shipping_cost = $input['shipping_cost'] ?? 0;
$shipping_address = $input['shipping_address'] ?? '';
$shipping_courier = $input['shipping_courier'] ?? '';
$payment_method = $input['payment_method'] ?? 'manual';

if (!$user_id || empty($items)) {
    json_err('Data pesanan tidak lengkap');
}

$mysqli->begin_transaction();

try {
    // Calculate total
    $total_amount = 0;
    foreach ($items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }
    $total_amount += $shipping_cost;
    $payment_status = 'unpaid';

    // Insert into orders
    $stmt = $mysqli->prepare("INSERT INTO orders (user_id, status, total_amount, shipping_cost, shipping_address, shipping_courier, payment_method, payment_status) VALUES (?, 'pending', ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iddssss', $user_id, $total_amount, $shipping_cost, $shipping_address, $shipping_courier, $payment_method, $payment_status);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Insert order details
    $stmt_detail = $mysqli->prepare("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt_detail->bind_param('iiid', $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $stmt_detail->execute();
        
        // Reduce stock (optional, check if stock sufficient first)
        $stmt_stock = $mysqli->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt_stock->bind_param('ii', $item['quantity'], $item['product_id']);
        $stmt_stock->execute();
    }

    // Clear cart if needed
    $stmt_cart = $mysqli->prepare("DELETE FROM carts WHERE user_id = ?");
    $stmt_cart->bind_param('i', $user_id);
    $stmt_cart->execute();

    $mysqli->commit();
    json_ok(['success' => true, 'order_id' => $order_id]);

} catch (Exception $e) {
    $mysqli->rollback();
    json_err('Gagal membuat pesanan: ' . $e->getMessage());
}
?>
