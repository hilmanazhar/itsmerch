<?php
// src/api/search_destination.php
// Search destination using RajaOngkir Komerce Direct Search
require 'db.php';
require 'rajaongkir_config.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

if (strlen($search) < 3) {
    json_err('Search keyword must be at least 3 characters');
}

$results = searchDestination($search, $limit);

if (isset($results['error'])) {
    json_err($results['error']);
}

// Format for frontend - return subdistrict info
$formatted = [];
foreach ($results as $r) {
    $formatted[] = [
        'id' => $r['subdistrict_id'] ?? $r['id'],
        'label' => ($r['subdistrict_name'] ?? '') . ', ' . 
                   ($r['city_name'] ?? '') . ', ' . 
                   ($r['province_name'] ?? ''),
        'subdistrict_id' => $r['subdistrict_id'] ?? $r['id'],
        'subdistrict_name' => $r['subdistrict_name'] ?? '',
        'city_id' => $r['city_id'] ?? '',
        'city_name' => $r['city_name'] ?? '',
        'province_id' => $r['province_id'] ?? '',
        'province_name' => $r['province_name'] ?? ''
    ];
}

json_ok($formatted);
?>
