<?php
// src/api/get_provinces.php
// Get all provinces from RajaOngkir Komerce API
require 'db.php';
require 'rajaongkir_config.php';

$provinces = getProvinces();

if (isset($provinces['error'])) {
    json_err($provinces['error']);
}

// Format for frontend
$formatted = [];
foreach ($provinces as $p) {
    $formatted[] = [
        'province_id' => $p['id'] ?? $p['province_id'],
        'province' => $p['name'] ?? $p['province']
    ];
}

json_ok($formatted);
?>
