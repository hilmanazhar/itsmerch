<?php
// reset_admin_password.php - Reset password for all admins
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Railway credentials
$host = 'yamabiko.proxy.rlwy.net';
$port = 24187;
$user = 'root';
$pass = 'jHMFFelpqHzZdwlXBFkjcSLJiQGmnXRI';
$db   = 'railway';

// New password for all admins
$new_password = 'password';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

echo "Connecting to Railway MySQL...\n";

try {
    $mysqli = new mysqli($host, $user, $pass, $db, $port);
    $mysqli->set_charset('utf8mb4');
    echo "✅ Connected!\n\n";
} catch (Exception $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

echo "Resetting passwords for all admins...\n";
echo "New password hash: " . substr($hashed_password, 0, 30) . "...\n\n";

// Update all admin passwords
$stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE role = 'admin'");
$stmt->bind_param('s', $hashed_password);

if ($stmt->execute()) {
    echo "✅ Password updated for " . $stmt->affected_rows . " admin(s)\n\n";
} else {
    echo "❌ Failed: " . $stmt->error . "\n";
}

// List all admins
echo "📋 All admins (password reset to: '{$new_password}'):\n";
$result = $mysqli->query("SELECT id, name, email FROM users WHERE role = 'admin'");
while ($row = $result->fetch_assoc()) {
    echo "   [{$row['id']}] {$row['name']} - {$row['email']}\n";
}

// Also verify the password hash works
echo "\n🔐 Verifying password hash...\n";
$verify_result = $mysqli->query("SELECT password FROM users WHERE role = 'admin' LIMIT 1");
$verify_row = $verify_result->fetch_assoc();
if (password_verify('password', $verify_row['password'])) {
    echo "✅ Password verification: SUCCESS\n";
} else {
    echo "❌ Password verification: FAILED\n";
}

$mysqli->close();
echo "\n🎉 Done! All admins can now login with password: {$new_password}\n";
?>
