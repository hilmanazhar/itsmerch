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
    $sql = "SELECT p.id, p.name, p.description, p.image_url, p.price, p.stock, p.category_id,
                   c.name as category_name, c.slug as category_slug
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id";
    
    $whereClause = "";
    $params = [];
    $types = "";
    
    // Category filter
    if ($category_id > 0) {
        $whereClause = " WHERE p.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    $sql .= $whereClause;
    
    // Sorting
    if ($sort === 'bestseller') {
        $sql .= " ORDER BY RAND()"; 
    } elseif ($sort === 'price_low') {
        $sql .= " ORDER BY p.price ASC";
    } elseif ($sort === 'price_high') {
        $sql .= " ORDER BY p.price DESC";
    } else {
        $sql .= " ORDER BY p.id DESC"; // Newest
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
        // Check if product has variants
        $variantCheck = $mysqli->prepare("SELECT COUNT(*) as cnt FROM product_variants WHERE product_id = ?");
        $variantCheck->bind_param('i', $row['id']);
        $variantCheck->execute();
        $variantResult = $variantCheck->get_result()->fetch_assoc();
        $row['has_variants'] = ($variantResult['cnt'] > 0);
        $variantCheck->close();
        
        $products[] = $row;
    }
    json_ok($products);
}
?>
