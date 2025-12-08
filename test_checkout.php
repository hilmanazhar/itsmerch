<?php
// test_checkout.php - Debug checkout issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Checkout Debug</h2>";

// Test 1: Database Connection
echo "<h3>1. Database Connection</h3>";
try {
    require 'src/api/db.php';
    echo "✅ Database connected successfully<br>";
    echo "Host: $host, DB: $db<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check Tables
echo "<h3>2. Required Tables</h3>";
$required_tables = ['users', 'orders', 'order_details', 'products', 'carts', 'user_addresses', 'coupons'];
foreach ($required_tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' MISSING!<br>";
    }
}

// Test 3: Check Orders Table Structure
echo "<h3>3. Orders Table Columns</h3>";
$result = $mysqli->query("DESCRIBE orders");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
echo "Columns: " . implode(', ', $columns) . "<br>";

$required_columns = ['id', 'user_id', 'status', 'total_amount', 'shipping_cost', 'discount_amount', 'coupon_code', 'shipping_address', 'shipping_courier', 'payment_method', 'payment_status', 'snap_token'];
foreach ($required_columns as $col) {
    if (in_array($col, $columns)) {
        echo "✅ Column '$col' exists<br>";
    } else {
        echo "❌ Column '$col' MISSING!<br>";
    }
}

// Test 4: Check Midtrans Config
echo "<h3>4. Midtrans Configuration</h3>";
try {
    require 'src/api/midtrans_config.php';
    echo "Merchant ID: " . MIDTRANS_MERCHANT_ID . "<br>";
    echo "Client Key: " . substr(MIDTRANS_CLIENT_KEY, 0, 15) . "...<br>";
    echo "Server Key: " . substr(MIDTRANS_SERVER_KEY, 0, 15) . "...<br>";
    echo "Is Production: " . (MIDTRANS_IS_PRODUCTION ? 'Yes' : 'No') . "<br>";
    echo "API URL: " . MIDTRANS_API_URL . "<br>";
    
    if (MIDTRANS_MERCHANT_ID == 'YOUR_MERCHANT_ID') {
        echo "❌ Midtrans credentials not configured!<br>";
    } else {
        echo "✅ Midtrans configured<br>";
    }
} catch (Exception $e) {
    echo "❌ Midtrans config error: " . $e->getMessage() . "<br>";
}

// Test 5: Test Midtrans API Connection
echo "<h3>5. Midtrans API Test</h3>";
$test_params = [
    'transaction_details' => [
        'order_id' => 'TEST-' . time(),
        'gross_amount' => 10000
    ],
    'customer_details' => [
        'first_name' => 'Test',
        'email' => 'test@test.com'
    ]
];

$snap_result = getSnapToken($test_params);
if (isset($snap_result['error'])) {
    echo "❌ Midtrans API Error: " . print_r($snap_result['error'], true) . "<br>";
} elseif (isset($snap_result['token'])) {
    echo "✅ Midtrans API working! Got token: " . substr($snap_result['token'], 0, 20) . "...<br>";
} else {
    echo "⚠️ Unexpected response: " . print_r($snap_result, true) . "<br>";
}

echo "<h3>6. Test User</h3>";
$result = $mysqli->query("SELECT id, name, email FROM users LIMIT 3");
while ($row = $result->fetch_assoc()) {
    echo "User ID: {$row['id']}, Name: {$row['name']}, Email: {$row['email']}<br>";
}

echo "<br><hr><p>Debug complete!</p>";
?>
