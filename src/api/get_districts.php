<?php
// src/api/get_districts.php
// Get districts by city from RajaOngkir Komerce API
require 'db.php';
require 'rajaongkir_config.php';

$city_id = isset($_GET['city_id']) ? $_GET['city_id'] : null;

if (!$city_id) {
    json_err('city_id is required');
}

$districts = getDistricts($city_id);

if (isset($districts['error'])) {
    json_err($districts['error']);
}

// Format for frontend
$formatted = [];
foreach ($districts as $d) {
    $formatted[] = [
        'district_id' => $d['id'] ?? $d['district_id'],
        'district_name' => $d['name'] ?? $d['district_name']
    ];
}

json_ok($formatted);
?>
