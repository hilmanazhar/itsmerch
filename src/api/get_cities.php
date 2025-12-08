<?php
// src/api/get_cities.php
// Get cities by province from RajaOngkir Komerce API
require 'db.php';
require 'rajaongkir_config.php';

$province_id = isset($_GET['province_id']) ? $_GET['province_id'] : null;

// According to Komerce API, use 'id' as the parameter for province
$params = [];
if ($province_id) {
    $params['id'] = $province_id;  // Changed from province_id to id
}

$result = rajaongkirRequest('destination/city', 'GET', $params);

if (isset($result['error'])) {
    json_err($result['error']);
}

$cities = $result['data'] ?? [];

// Format for frontend
$formatted = [];
foreach ($cities as $c) {
    $formatted[] = [
        'city_id' => $c['id'] ?? $c['city_id'],
        'city_name' => $c['name'] ?? $c['city_name'],
        'type' => $c['type'] ?? ''
    ];
}

json_ok($formatted);
?>
