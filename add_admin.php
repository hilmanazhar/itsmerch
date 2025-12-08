<?php
// add_admin.php - Add new admin user to Railway database
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Railway credentials
$host = 'yamabiko.proxy.rlwy.net';
$port = 24187;
$user = 'root';
$pass = 'jHMFFelpqHzZdwlXBFkjcSLJiQGmnXRI';
$db   = 'railway';

// ========== EDIT DATA ADMIN BARU DI SINI ==========
$new_admins = [
    [
        'name' => 'Admin 2',
        'email' => 'admin2@its.ac.id',
        'password' => 'password',  // Akan di-hash otomatis
        'phone' => '081234567891'
    ],
    [
        'name' => 'Admin 3',
        'email' => 'admin3@its.ac.id',
        'password' => 'password',
        'phone' => '081234567892'
    ],
    // Tambahkan admin lainnya di sini...
];
// ===================================================

echo "Connecting to Railway MySQL...\n";

try {
    $mysqli = new mysqli($host, $user, $pass, $db, $port);
    $mysqli->set_charset('utf8mb4');
    echo "✅ Connected!\n\n";
} catch (Exception $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

echo "Adding new admins...\n\n";

$stmt = $mysqli->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, 'admin', ?)");

foreach ($new_admins as $admin) {
    // Hash password
    $hashed_password = password_hash($admin['password'], PASSWORD_DEFAULT);
    
    // Check if email already exists
    $check = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param('s', $admin['email']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        echo "⚠️  {$admin['email']} - already exists, skipped\n";
        continue;
    }
    
    // Insert new admin
    $stmt->bind_param('ssss', $admin['name'], $admin['email'], $hashed_password, $admin['phone']);
    
    if ($stmt->execute()) {
        echo "✅ Added: {$admin['name']} ({$admin['email']})\n";
    } else {
        echo "❌ Failed: {$admin['email']} - " . $stmt->error . "\n";
    }
}

echo "\n📋 All admins in database:\n";
$result = $mysqli->query("SELECT id, name, email, role FROM users WHERE role = 'admin'");
while ($row = $result->fetch_assoc()) {
    echo "   [{$row['id']}] {$row['name']} - {$row['email']}\n";
}

$mysqli->close();
echo "\n🎉 Done!\n";
?>
