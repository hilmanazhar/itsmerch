<?php
// src/api/delete_address.php
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;
$user_id = $input['user_id'] ?? 0;

if (!$id || !$user_id) {
    json_err('Invalid Params');
}

$stmt = $mysqli->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);

if ($stmt->execute()) {
    json_ok(['success' => true]);
} else {
    json_err('Gagal menghapus alamat');
}
?>
