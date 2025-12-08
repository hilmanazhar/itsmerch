<?php
// src/api/admin_add_product.php
require 'db.php';

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // Fallback to POST if not JSON
    $input = $_POST;
}

$name = $input['name'] ?? '';
$desc = $input['description'] ?? '';
$price = $input['price'] ?? 0;
$stock = $input['stock'] ?? 0;
$image = $input['image_url'] ?? '';

// Handle File Upload
if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../assets/images/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileInfo = pathinfo($_FILES['image_file']['name']);
    $ext = strtolower($fileInfo['extension']);
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    
    if (in_array($ext, $allowed)) {
        $filename = 'prod_' . time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $filename)) {
            $image = 'assets/images/products/' . $filename;
        }
    }
}
$category_id = $input['category_id'] ?? null;

if (!$name || !$price) {
    json_err('Nama dan harga wajib diisi');
}

// Handle empty category
if (empty($category_id)) {
    $category_id = null;
}

$stmt = $mysqli->prepare("INSERT INTO products (name,description,image_url,price,stock,category_id) VALUES (?,?,?,?,?,?)");
$stmt->bind_param('sssdii', $name, $desc, $image, $price, $stock, $category_id);
$res = $stmt->execute();
$id = $stmt->insert_id;

if ($res) {
    json_ok(['success'=>true, 'id'=>$id]);
} else {
    json_err('Failed to add product: ' . $stmt->error);
}
?>
