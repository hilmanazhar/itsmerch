<?php
// src/api/update_order_status.php
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$order_id = intval($input['order_id'] ?? 0);
$status = $input['status'] ?? '';
$payment_status = $input['payment_status'] ?? null;
$tracking_number = $input['tracking_number'] ?? null;

if (!$order_id) {
    json_err('Order ID diperlukan');
}

// Get current order
$stmt = $mysqli->prepare("SELECT status, payment_status FROM orders WHERE id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();

if (!$current) {
    json_err('Order tidak ditemukan');
}

// If cancelling, restore stock
if ($status === 'cancelled' && $current['status'] !== 'cancelled') {
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

// Build update query
$updates = [];
$params = [];
$types = '';

if ($status) {
    $valid_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        json_err('Status tidak valid');
    }
    $updates[] = 'status = ?';
    $params[] = $status;
    $types .= 's';
}

if ($payment_status !== null) {
    $valid_payment = ['unpaid', 'paid', 'failed'];
    if (!in_array($payment_status, $valid_payment)) {
        json_err('Payment status tidak valid');
    }
    $updates[] = 'payment_status = ?';
    $params[] = $payment_status;
    $types .= 's';
}

if ($tracking_number !== null) {
    $updates[] = 'tracking_number = ?';
    $params[] = $tracking_number;
    $types .= 's';
}

if (empty($updates)) {
    json_err('Tidak ada data untuk diupdate');
}

$params[] = $order_id;
$types .= 'i';

$sql = "UPDATE orders SET " . implode(', ', $updates) . " WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    json_ok(['success' => true]);
} else {
    json_err('Gagal mengupdate status');
}
?>
