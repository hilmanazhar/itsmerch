<?php
// src/api/admin_delete_product.php
require 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

if ($id <= 0) {
    json_err('Invalid ID');
}

$stmt = $mysqli->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param('i',$id);
$res = $stmt->execute();

if ($res) {
    json_ok(['success'=>true]);
} else {
    json_err('Failed to delete product');
}
?>
