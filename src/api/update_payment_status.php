<?php
// src/api/update_payment_status.php
// Called from frontend after Midtrans payment success (for localhost testing)
require 'db.php';

// Log for debugging
$logFile = __DIR__ . '/payment_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

$input = json_decode(file_get_contents('php://input'), true);
$midtrans_order_id = $input['order_id'] ?? '';
// Handle 'ITS-YYYYMMDD-UserID-OrderID' format
if (strpos($midtrans_order_id, '-') !== false) {
    $parts = explode('-', $midtrans_order_id);
    $order_id = intval(end($parts));
} else {
    $order_id = intval($midtrans_order_id);
}
$status = $input['transaction_status'] ?? '';

file_put_contents($logFile, "Order ID: $order_id, Status: $status\n", FILE_APPEND);

if (!$order_id) {
    json_err('Order ID diperlukan');
    exit;
}

// Get current order
$stmt = $mysqli->prepare("SELECT status, payment_status FROM orders WHERE id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    json_err('Order tidak ditemukan');
    exit;
}

file_put_contents($logFile, "Current order: " . json_encode($order) . "\n", FILE_APPEND);

// Already paid or cancelled, skip
if ($order['payment_status'] === 'paid' || $order['status'] === 'cancelled') {
    json_ok(['success' => true, 'message' => 'Status sudah diupdate', 'payment_status' => $order['payment_status']]);
    exit;
}

// Update based on transaction status
$new_payment_status = 'unpaid';
$new_order_status = 'pending';

// Handle various Midtrans status values
if (in_array($status, ['settlement', 'capture', 'success'])) {
    $new_payment_status = 'paid';
    $new_order_status = 'processing';
} else if ($status === 'pending') {
    $new_payment_status = 'unpaid';
    $new_order_status = 'pending';
} else if (in_array($status, ['cancel', 'deny', 'expire', 'failure'])) {
    $new_payment_status = 'failed';
    $new_order_status = 'cancelled';
    
    // Restore stock
    $stmt = $mysqli->prepare("SELECT product_id, quantity FROM order_details WHERE order_id = ?");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $items = $stmt->get_result();
    
    while ($item = $items->fetch_assoc()) {
        $stmt_stock = $mysqli->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmt_stock->bind_param('ii', $item['quantity'], $item['product_id']);
        $stmt_stock->execute();
    }
}

file_put_contents($logFile, "New status: payment=$new_payment_status, order=$new_order_status\n", FILE_APPEND);

// Update order
$stmt = $mysqli->prepare("UPDATE orders SET payment_status = ?, status = ? WHERE id = ?");
$stmt->bind_param('ssi', $new_payment_status, $new_order_status, $order_id);

if ($stmt->execute()) {
    file_put_contents($logFile, "Update successful!\n\n", FILE_APPEND);
    json_ok(['success' => true, 'payment_status' => $new_payment_status, 'order_status' => $new_order_status]);
} else {
    file_put_contents($logFile, "Update failed: " . $mysqli->error . "\n\n", FILE_APPEND);
    json_err('Gagal update status: ' . $mysqli->error);
}
exit;
?>
