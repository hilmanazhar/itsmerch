<?php
// Import schema to Railway MySQL
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'switchback.proxy.rlwy.net';
$port = 53877;
$user = 'root';
$pass = 'HqvDdwHCaGzKoRAEEeFpMolvqkEPwrJJ';
$db   = 'railway';

echo "Connecting to Railway MySQL...\n";

try {
    $mysqli = new mysqli($host, $user, $pass, $db, $port);
    $mysqli->set_charset('utf8mb4');
    echo "✅ Connected successfully!\n\n";
} catch (Exception $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

// Read SQL file
$sqlFile = 'complete_database_reset.sql';
$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("❌ Could not read SQL file\n");
}

echo "📄 Importing schema from $sqlFile...\n";

// Execute multi-query
$mysqli->multi_query($sql);

$queryCount = 0;
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
    $queryCount++;
} while ($mysqli->next_result());

if ($mysqli->error) {
    echo "⚠️ Warning: " . $mysqli->error . "\n";
} else {
    echo "✅ Schema imported successfully! ($queryCount queries executed)\n";
}

// Verify tables
echo "\n📋 Tables in database:\n";
$result = $mysqli->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    echo "   - " . $row[0] . "\n";
}

$mysqli->close();
echo "\n🎉 Done!\n";
?>
