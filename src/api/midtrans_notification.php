<?php
// src/api/midtrans_notification.php
// Webhook handler for Midtrans payment notifications
require 'db.php';
require 'midtrans_config.php';

// Get JSON notification from Midtrans
$notification = json_decode(file_get_contents('php://input'), true);

if (!$notification) {
    json_err('Invalid notification data');
}

$order_id = $notification['order_id'] ?? '';
$status_code = $notification['status_code'] ?? '';
$gross_amount = $notification['gross_amount'] ?? '';
$signature_key = $notification['signature_key'] ?? '';
$transaction_status = $notification['transaction_status'] ?? '';
$fraud_status = $notification['fraud_status'] ?? '';
$payment_type = $notification['payment_type'] ?? '';

// Verify signature
if (!verifySignature($order_id, $status_code, $gross_amount, $signature_key)) {
    json_err('Invalid signature', 403);
}

// Extract database order ID from midtrans order ID (format: ITS-YYYYMMDD-userid-orderid)
$parts = explode('-', $order_id);
$db_order_id = end($parts);

if (!$db_order_id || !is_numeric($db_order_id)) {
    json_err('Invalid order ID format');
}

// Determine payment status and order status
$payment_status = 'unpaid';
$order_status = 'pending';

if ($transaction_status == 'capture') {
    if ($fraud_status == 'accept') {
        $payment_status = 'paid';
        $order_status = 'processing';
    } else if ($fraud_status == 'challenge') {
        $payment_status = 'unpaid';
        $order_status = 'pending';
    }
} else if ($transaction_status == 'settlement') {
    $payment_status = 'paid';
    $order_status = 'processing';
} else if ($transaction_status == 'pending') {
    $payment_status = 'unpaid';
    $order_status = 'pending';
} else if ($transaction_status == 'deny' || $transaction_status == 'cancel' || $transaction_status == 'expire') {
    $payment_status = 'failed';
    $order_status = 'cancelled';
    
    // Restore stock for cancelled/failed orders
    $stmt = $mysqli->prepare("SELECT product_id, quantity FROM order_details WHERE order_id = ?");
    $stmt->bind_param('i', $db_order_id);
    $stmt->execute();
    $items = $stmt->get_result();
    
    while ($item = $items->fetch_assoc()) {
        $stmt_stock = $mysqli->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmt_stock->bind_param('ii', $item['quantity'], $item['product_id']);
        $stmt_stock->execute();
    }
} else if ($transaction_status == 'refund') {
    $payment_status = 'failed';
    $order_status = 'cancelled';
}

// Update order
$stmt = $mysqli->prepare("UPDATE orders SET payment_status = ?, status = ?, payment_method = ? WHERE id = ?");
$stmt->bind_param('sssi', $payment_status, $order_status, $payment_type, $db_order_id);
$stmt->execute();

// Log notification
error_log("Midtrans Notification - Order: $db_order_id, Status: $transaction_status, Payment: $payment_status");

// Send email notification on successful payment
if ($payment_status === 'paid') {
    try {
        // Include email notification class
        require_once 'email_notification.php';
        
        // Get PDO connection for email class
        $pdo = new PDO("mysql:host=localhost;dbname=its_merchandise;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Send payment success email with invoice
        $emailService = new EmailNotification($pdo);
        $emailResult = $emailService->sendPaymentSuccess($db_order_id);
        
        error_log("Email notification sent for order $db_order_id: " . ($emailResult ? "Success" : "Failed"));
    } catch (Exception $e) {
        error_log("Email notification error for order $db_order_id: " . $e->getMessage());
        // Don't fail the webhook response even if email fails
    }
}

json_ok(['success' => true, 'message' => 'Notification processed']);
?>
