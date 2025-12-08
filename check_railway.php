<?php
// Check Railway MySQL connection and tables
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'switchback.proxy.rlwy.net';
$port = 53877;
$user = 'root';
$pass = 'HqvDdwHCaGzKoRAEEeFpMolvqkEPwrJJ';
$db   = 'railway';

echo "=== Railway MySQL Check ===\n\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $db\n\n";

try {
    $mysqli = new mysqli($host, $user, $pass, $db, $port);
    $mysqli->set_charset('utf8mb4');
    echo "✅ Connected successfully!\n\n";
} catch (Exception $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

// Check current database
$result = $mysqli->query("SELECT DATABASE() as db");
$row = $result->fetch_assoc();
echo "Current database: " . $row['db'] . "\n\n";

// List all databases
echo "All databases:\n";
$result = $mysqli->query("SHOW DATABASES");
while ($row = $result->fetch_array()) {
    echo "   - " . $row[0] . "\n";
}

echo "\n";

// List tables
echo "Tables in '$db':\n";
$result = $mysqli->query("SHOW TABLES");
$count = $result->num_rows;
if ($count == 0) {
    echo "   (no tables found)\n";
} else {
    while ($row = $result->fetch_array()) {
        echo "   - " . $row[0] . "\n";
    }
}

echo "\nTotal tables: $count\n";

$mysqli->close();
?>
