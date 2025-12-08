<?php
// src/api/register.php
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);

$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$phone = $input['phone'] ?? '';
$province_id = $input['province_id'] ?? null;
$city_id = $input['city_id'] ?? null;
$address_detail = $input['address_detail'] ?? '';

if (!$name || !$email || !$password) {
    json_err('Nama, Email, dan Password wajib diisi');
}

// Check existing email
$stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    json_err('Email sudah terdaftar', 409);
}

// Insert user with password hashing
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("INSERT INTO users (name, email, password, role, phone, province_id, city_id, address_detail) VALUES (?, ?, ?, 'user', ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $name, $email, $hashed_password, $phone, $province_id, $city_id, $address_detail);

if ($stmt->execute()) {
    json_ok(['success' => true, 'message' => 'Registrasi berhasil']);
} else {
    json_err('Gagal mendaftar: ' . $mysqli->error, 500);
}
?>
