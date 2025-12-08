<?php
// src/api/login.php
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (!$email || !$password) {
    json_err('Email dan password wajib diisi');
}

$stmt = $mysqli->prepare("SELECT id, name, email, password, role, phone, province_id, city_id, address_detail FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if ($user) {
    // Secure password verification using password_verify
    if (password_verify($password, $user['password'])) {
        unset($user['password']);
        json_ok(['success' => true, 'user' => $user]);
    } else {
        json_err('Password salah', 401);
    }
} else {
    json_err('User tidak ditemukan', 404);
}
?>
