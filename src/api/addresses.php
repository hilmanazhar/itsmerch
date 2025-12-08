<?php
// src/api/addresses.php
// Unified endpoint for address management with destination ID for shipping
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get addresses for user
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if ($user_id <= 0) {
        json_err('Invalid User ID');
    }

    $stmt = $mysqli->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $addresses = [];
    while ($row = $res->fetch_assoc()) {
        $addresses[] = $row;
    }
    json_ok($addresses);

} elseif ($method === 'POST') {
    // Add new address
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['user_id'] ?? 0;
    $label = $input['label'] ?? 'Home';
    $recipient = $input['recipient_name'] ?? '';
    $phone = $input['phone'] ?? '';
    $address = $input['address_detail'] ?? '';
    $destination_id = $input['destination_id'] ?? null;
    $destination_label = $input['destination_label'] ?? '';

    if (!$user_id || !$recipient || !$address) {
        json_err('Data alamat tidak lengkap');
    }

    // Check if first address, make it primary
    $stmt = $mysqli->prepare("SELECT id FROM user_addresses WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $is_primary = ($stmt->get_result()->num_rows === 0) ? 1 : 0;

    // Combine address with destination label
    $full_address = $address;
    if ($destination_label) {
        $full_address = $address . ' - ' . $destination_label;
    }

    $stmt = $mysqli->prepare("INSERT INTO user_addresses (user_id, label, recipient_name, phone, address_detail, destination_id, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssssi', $user_id, $label, $recipient, $phone, $full_address, $destination_id, $is_primary);

    if ($stmt->execute()) {
        json_ok(['success' => true, 'id' => $stmt->insert_id]);
    } else {
        json_err('Gagal menyimpan alamat: ' . $mysqli->error);
    }

} elseif ($method === 'DELETE') {
    // Delete address
    $address_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if ($address_id && $user_id) {
        $stmt = $mysqli->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $address_id, $user_id);
        $stmt->execute();
        json_ok(['success' => true]);
    } else {
        json_err('Invalid params');
    }
}
?>
