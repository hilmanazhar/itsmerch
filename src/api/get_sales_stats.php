<?php
// src/api/get_sales_stats.php
require 'db.php';

// Only for admin (check logic should be in frontend or middleware, but here we assume caller is trusted or checked)
// In a real app, verify session/token here.

// 1. Total Revenue (Completed)
$res = $mysqli->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'completed'");
$revenue = $res->fetch_assoc()['revenue'] ?? 0;

// 1b. Pending Revenue
$res = $mysqli->query("SELECT SUM(total_amount) as pending FROM orders WHERE status = 'pending'");
$pending_revenue = $res->fetch_assoc()['pending'] ?? 0;

// 2. Total Orders
$res = $mysqli->query("SELECT COUNT(*) as total_orders FROM orders");
$total_orders = $res->fetch_assoc()['total_orders'] ?? 0;

// 3. Recent Orders
$res = $mysqli->query("SELECT o.id, u.name, o.total_amount, o.status, o.created_at 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       ORDER BY o.created_at DESC LIMIT 5");
$recent_orders = [];
while ($row = $res->fetch_assoc()) {
    $recent_orders[] = $row;
}

// 4. Top Products
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

json_ok([
    'revenue' => $revenue,
    'pending_revenue' => $pending_revenue,
    'total_orders' => $total_orders,
    'recent_orders' => $recent_orders,
    'top_products' => $top_products
]);
?>
