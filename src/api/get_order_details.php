<?php
// src/api/get_order_details.php
require 'db.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

if ($order_id <= 0) {
    json_err('Invalid Order ID');
}

$stmt = $mysqli->prepare("SELECT od.product_id, od.quantity, od.price, p.name as product_name, p.image_url 
                          FROM order_details od 
                          JOIN products p ON od.product_id = p.id 
                          WHERE od.order_id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$res = $stmt->get_result();
$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}
json_ok($items);
?>
