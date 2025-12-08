<?php
// src/api/get_shipping_cost.php
// Calculate shipping cost using RajaOngkir Komerce API
require 'db.php';
require 'rajaongkir_config.php';

// Accept destination ID (district/subdistrict), weight, and courier
$destination = $_GET['destination'] ?? $_POST['destination'] ?? '';
$weight = $_GET['weight'] ?? $_POST['weight'] ?? 1000;
$courier = $_GET['courier'] ?? $_POST['courier'] ?? 'jne';

if (!$destination) {
    json_err('Destination is required');
}

$result = getShippingCost($destination, $weight, $courier);

if (isset($result['error'])) {
    json_err($result['error']);
}

// Format for frontend
$formatted = [];

// Handle response structure
$costs = $result['costs'] ?? $result;
if (is_array($costs)) {
    foreach ($costs as $service) {
        $formatted[] = [
            'courier' => strtoupper($courier),
            'courier_name' => $result['name'] ?? strtoupper($courier),
            'service' => $service['service'] ?? $service['type'] ?? '',
            'description' => $service['description'] ?? '',
            'cost' => $service['cost'] ?? $service['price'] ?? 0,
            'etd' => $service['etd'] ?? $service['estimation'] ?? '-'
        ];
    }
}

json_ok(['costs' => $formatted]);
?>
