<?php
// src/api/midtrans_config.sample.php
// Midtrans Configuration with Railway environment variable support

// Get credentials from environment or use defaults
$merchantId = getenv('MIDTRANS_MERCHANT_ID') ?: 'YOUR_MERCHANT_ID';
$clientKey = getenv('MIDTRANS_CLIENT_KEY') ?: 'YOUR_CLIENT_KEY';
$serverKey = getenv('MIDTRANS_SERVER_KEY') ?: 'YOUR_SERVER_KEY';
$isProduction = getenv('MIDTRANS_IS_PRODUCTION') === 'true';

define('MIDTRANS_MERCHANT_ID', $merchantId);
define('MIDTRANS_CLIENT_KEY', $clientKey);
define('MIDTRANS_SERVER_KEY', $serverKey);
define('MIDTRANS_IS_PRODUCTION', $isProduction);

// Set URLs based on environment
if (MIDTRANS_IS_PRODUCTION) {
    define('MIDTRANS_SNAP_URL', 'https://app.midtrans.com/snap/snap.js');
    define('MIDTRANS_API_URL', 'https://app.midtrans.com/snap/v1/transactions');
} else {
    define('MIDTRANS_SNAP_URL', 'https://app.sandbox.midtrans.com/snap/snap.js');
    define('MIDTRANS_API_URL', 'https://app.sandbox.midtrans.com/snap/v1/transactions');
}

/**
 * Get Snap Token from Midtrans
 */
function getSnapToken($params) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => MIDTRANS_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);

    if ($err) {
        return ['error' => 'Curl Error: ' . $err];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode !== 201 && $httpCode !== 200) {
        return ['error' => $result['error_messages'] ?? 'Failed to get snap token'];
    }
    
    return $result;
}

/**
 * Verify Midtrans notification signature
 */
function verifySignature($orderId, $statusCode, $grossAmount, $signatureKey) {
    $serverKey = MIDTRANS_SERVER_KEY;
    $input = $orderId . $statusCode . $grossAmount . $serverKey;
    $generatedSignature = hash('sha512', $input);
    
    return $signatureKey === $generatedSignature;
}
?>
