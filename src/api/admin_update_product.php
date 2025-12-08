<?php
// src/api/admin_update_product.php
require 'db.php';

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // Fallback to POST if not JSON
    $input = $_POST;
}

$id = $input['id'] ?? 0;
$name = $input['name'] ?? '';
$desc = $input['description'] ?? '';
$price = $input['price'] ?? 0;
$stock = $input['stock'] ?? 0;
$image = $input['image_url'] ?? '';
$category_id = $input['category_id'] ?? null;

if (!$id || !$name) {
    json_err('ID dan Nama wajib diisi');
}

// Handle empty category
if (empty($category_id)) {
    $category_id = null;
}

$stmt = $mysqli->prepare("UPDATE products SET name=?,description=?,image_url=?,price=?,stock=?,category_id=? WHERE id=?");
$stmt->bind_param('sssdiii', $name, $desc, $image, $price, $stock, $category_id, $id);
$res = $stmt->execute();

if ($res) {
    json_ok(['success'=>true]);
} else {
    json_err('Failed to update product: ' . $stmt->error);
}
?>
