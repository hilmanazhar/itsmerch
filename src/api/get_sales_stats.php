<?php
// src/api/get_sales_stats.php
require 'db.php';

// 1. Total Revenue (Completed)
$res = $mysqli->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'completed'");
$revenue = $res->fetch_assoc()['revenue'] ?? 0;

// 1b. Pending Revenue
$res = $mysqli->query("SELECT SUM(total_amount) as pending FROM orders WHERE status = 'pending'");
$pending_revenue = $res->fetch_assoc()['pending'] ?? 0;

// 2. Total Orders
$res = $mysqli->query("SELECT COUNT(*) as total_orders FROM orders");
$total_orders = $res->fetch_assoc()['total_orders'] ?? 0;

// 3. Order Status Breakdown
$order_status = [
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'completed' => 0,
    'cancelled' => 0
];
$res = $mysqli->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = $res->fetch_assoc()) {
    $order_status[$row['status']] = (int)$row['count'];
}

// 4. Total Users
$res = $mysqli->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
$total_users = $res->fetch_assoc()['total_users'] ?? 0;

// 5. Total Products
$res = $mysqli->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $res->fetch_assoc()['total_products'] ?? 0;

// 6. Recent Orders
$res = $mysqli->query("SELECT o.id, u.name, o.total_amount, o.status, o.payment_status, o.created_at 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       ORDER BY o.created_at DESC LIMIT 5");
$recent_orders = [];
while ($row = $res->fetch_assoc()) {
    $recent_orders[] = $row;
}

// 7. Top Products
$res = $mysqli->query("SELECT p.name, SUM(od.quantity) as sold 
                       FROM order_details od 
                       JOIN products p ON od.product_id = p.id 
                       JOIN orders o ON od.order_id = o.id 
                       WHERE o.status = 'completed' 
                       GROUP BY p.id 
                       ORDER BY sold DESC LIMIT 5");
$top_products = [];
while ($row = $res->fetch_assoc()) {
    $top_products[] = $row;
}

// 8. Low Stock Products (stock < 10)
$res = $mysqli->query("SELECT id, name, stock, image_url FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5");
$low_stock = [];
while ($row = $res->fetch_assoc()) {
    $low_stock[] = $row;
}

// 9. Weekly Sales Data (last 7 days)
$weekly_sales = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $res = $mysqli->query("SELECT COALESCE(SUM(total_amount), 0) as total 
                           FROM orders 
                           WHERE DATE(created_at) = '$date' AND status = 'completed'");
    $weekly_sales[] = [
        'date' => date('D', strtotime($date)),
        'total' => (float)$res->fetch_assoc()['total']
    ];
}

json_ok([
    'revenue' => $revenue,
    'pending_revenue' => $pending_revenue,
    'total_orders' => $total_orders,
    'order_status' => $order_status,
    'total_users' => $total_users,
    'total_products' => $total_products,
    'recent_orders' => $recent_orders,
    'top_products' => $top_products,
    'low_stock' => $low_stock,
    'weekly_sales' => $weekly_sales
]);
?>
