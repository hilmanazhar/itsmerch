<?php
// src/api/add_address.php
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? 0;
$label = $input['label'] ?? 'Home';
$recipient = $input['recipient_name'] ?? '';
$phone = $input['phone'] ?? '';
$address = $input['address_detail'] ?? '';

if (!$user_id || !$recipient || !$address) {
    json_err('Data alamat tidak lengkap');
}

// Check if first address, make it primary
$stmt = $mysqli->prepare("SELECT id FROM user_addresses WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$is_primary = ($stmt->get_result()->num_rows === 0) ? 1 : 0;

$stmt = $mysqli->prepare("INSERT INTO user_addresses (user_id, label, recipient_name, phone, address_detail, is_primary) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('issssi', $user_id, $label, $recipient, $phone, $address, $is_primary);

if ($stmt->execute()) {
    json_ok(['success' => true, 'id' => $stmt->insert_id]);
} else {
    json_err('Gagal menyimpan alamat');
}
?>
