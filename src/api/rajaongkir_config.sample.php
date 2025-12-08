<?php
// src/api/rajaongkir_config.sample.php
// Copy this file to rajaongkir_config.php and update API Key

// API Key for Shipping Cost
define('RAJAONGKIR_API_KEY', 'CWd6ZSw7e4dd34937017a660nedHnolF');

// Komerce RajaOngkir API V2 Base URL
define('RAJAONGKIR_API_URL', 'https://rajaongkir.komerce.id/api/v1');

// Origin location (Sukolilo, Surabaya - ITS campus area)
define('ORIGIN_SUBDISTRICT_ID', '69316'); // KEPUTIH, SUKOLILO, SURABAYA

/**
 * Make request to RajaOngkir Komerce API
 */
function rajaongkirRequest($endpoint, $method = 'GET', $params = []) {
    $url = RAJAONGKIR_API_URL . '/' . $endpoint;
    
    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $curl = curl_init();
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER => [
            'key: ' . RAJAONGKIR_API_KEY,
            'Accept: application/json'
        ],
    ];
    
    if ($method === 'POST') {
        $options[CURLOPT_CUSTOMREQUEST] = 'POST';
        $options[CURLOPT_POSTFIELDS] = json_encode($params);
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
    }
    
    curl_setopt_array($curl, $options);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);

    if ($err) {
        return ['error' => 'Curl Error: ' . $err];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode !== 200) {
        return ['error' => $result['meta']['message'] ?? 'API request failed (HTTP ' . $httpCode . ')'];
    }
    
    return $result;
}

// Helper functions included
function getProvinces() {
    $result = rajaongkirRequest('destination/province');
    return isset($result['error']) ? $result : ($result['data'] ?? []);
}

function getCities($provinceId = null) {
    $params = [];
    if ($provinceId) $params['province_id'] = $provinceId;
    $result = rajaongkirRequest('destination/city', 'GET', $params);
    return isset($result['error']) ? $result : ($result['data'] ?? []);
}

function getDistricts($cityId) {
    $result = rajaongkirRequest('destination/district', 'GET', ['city_id' => $cityId]);
    return isset($result['error']) ? $result : ($result['data'] ?? []);
}

function searchDestination($keyword, $limit = 10) {
    $result = rajaongkirRequest('destination/domestic-destination', 'GET', [
        'search' => $keyword, 'limit' => $limit, 'offset' => 0
    ]);
    return isset($result['error']) ? $result : ($result['data'] ?? []);
}

function getShippingCost($destinationId, $weight, $courier) {
    $url = RAJAONGKIR_API_URL . '/calculate/domestic-cost';
    $postData = http_build_query([
        'origin' => ORIGIN_SUBDISTRICT_ID,
        'destination' => $destinationId,
        'weight' => $weight,
        'courier' => strtolower($courier)
    ]);
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'key: ' . RAJAONGKIR_API_KEY,
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ],
    ]);
    
    $response = curl_exec($curl);
    $result = json_decode($response, true);
    curl_close($curl);
    
    return $result['data'] ?? [];
}
?>
