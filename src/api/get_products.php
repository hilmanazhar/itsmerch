<?php
// src/api/get_products.php
require 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

if ($id > 0) {
    // Single product detail with category
    $stmt = $mysqli->prepare("
        SELECT p.id, p.name, p.description, p.image_url, p.price, p.stock, p.category_id,
               c.name as category_name, c.slug as category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = $res->fetch_assoc();
    $stmt->close();
    if ($out) {
        json_ok($out);
    } else {
        json_err('Product not found', 404);
    }
} else {
    // List products with category filter
    $params = [];
    $types = "";
    
    // Build SQL based on sort type
    if ($sort === 'bestseller') {
        // Special query for bestseller that includes total sold calculation
        $sql = "SELECT p.id, p.name, p.description, p.image_url, p.price, p.stock, p.category_id,
                       c.name as category_name, c.slug as category_slug,
                       COALESCE(SUM(od.quantity), 0) as total_sold
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN order_details od ON p.id = od.product_id
                LEFT JOIN orders o ON od.order_id = o.id AND o.status = 'completed'";
        
        // Apply category filter if exists
        if ($category_id > 0) {
            $sql .= " WHERE p.category_id = ?";
            $params[] = $category_id;
            $types .= "i";
        }
        
        $sql .= " GROUP BY p.id ORDER BY total_sold DESC";
    } else {
        // Regular query for other sorting options
        $sql = "SELECT p.id, p.name, p.description, p.image_url, p.price, p.stock, p.category_id,
                       c.name as category_name, c.slug as category_slug
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id";
        
        // Category filter
        if ($category_id > 0) {
            $sql .= " WHERE p.category_id = ?";
            $params[] = $category_id;
            $types .= "i";
        }
        
        // Sorting
        if ($sort === 'price_low') {
            $sql .= " ORDER BY p.price ASC";
        } elseif ($sort === 'price_high') {
            $sql .= " ORDER BY p.price DESC";
        } else {
            $sql .= " ORDER BY p.id DESC"; // Newest
        }
    }

    if ($limit > 0) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
        $types .= "i";
    }

    $stmt = $mysqli->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $res = $stmt->get_result();
    $products = [];
    while ($row = $res->fetch_assoc()) {
        // Check if product has variants and get total variant stock
        $variantCheck = $mysqli->prepare("
            SELECT COUNT(*) as cnt, COALESCE(SUM(stock), 0) as total_stock 
            FROM product_variants 
            WHERE product_id = ? AND is_active = 1
        ");
        $variantCheck->bind_param('i', $row['id']);
        $variantCheck->execute();
        $variantResult = $variantCheck->get_result()->fetch_assoc();
        $row['has_variants'] = ($variantResult['cnt'] > 0);
        
        // If product has variants, use sum of variant stocks as the display stock
        if ($row['has_variants'] && $variantResult['total_stock'] > 0) {
            $row['stock'] = intval($variantResult['total_stock']);
        }
        
        $variantCheck->close();
        
        $products[] = $row;
    }
    json_ok($products);
}
?>
