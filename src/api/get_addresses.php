<?php
// src/api/get_addresses.php
require 'db.php';

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
?>
