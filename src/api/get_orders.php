<?php
// src/api/get_orders.php
require 'db.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$is_admin = isset($_GET['admin']) && $_GET['admin'] == 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

if ($is_admin) {
    // Admin: All orders with user name
    $sql = "SELECT o.id, o.user_id, u.name as user_name, o.created_at as order_date, o.status, 
                   o.total_amount, o.discount_amount, o.coupon_code, o.payment_status, o.shipping_courier, o.shipping_address, 
                   o.shipping_cost, o.tracking_number
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            ORDER BY o.id DESC LIMIT ? OFFSET ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
} elseif ($user_id > 0) {
    // User: Own orders with complete details
    $sql = "SELECT id, created_at as order_date, status, total_amount, shipping_cost, discount_amount, coupon_code,
                   payment_status, payment_method, shipping_courier, shipping_address, 
                   snap_token, tracking_number 
            FROM orders WHERE user_id = ? ORDER BY id DESC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user_id);
} else {
    json_err('Unauthorized', 403);
}

$stmt->execute();
$res = $stmt->get_result();
$orders = [];
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
}
json_ok($orders);
?>
