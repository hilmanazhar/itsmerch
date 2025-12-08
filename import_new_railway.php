<?php
// Import database to NEW Railway project
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'yamabiko.proxy.rlwy.net';
$port = 24187;
$user = 'root';
$pass = 'jHMFFelpqHzZdwlXBFkjcSLJiQGmnXRI';
$db   = 'railway';

echo "Connecting to NEW Railway MySQL (database: $db)...\n";

try {
    $mysqli = new mysqli($host, $user, $pass, $db, $port);
    $mysqli->set_charset('utf8mb4');
    echo "✅ Connected!\n\n";
} catch (Exception $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

// Drop existing tables first
echo "Dropping existing tables...\n";
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
$result = $mysqli->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $mysqli->query("DROP TABLE IF EXISTS `" . $row[0] . "`");
    echo "   Dropped: " . $row[0] . "\n";
}
$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
echo "✅ Tables dropped!\n\n";

// Read SQL file
$sqlFile = 'its_merchandise (3).sql';
echo "Reading $sqlFile...\n";
$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("❌ Could not read SQL file\n");
}

// Remove database-specific statements
$sql = preg_replace('/USE `[^`]+`;?/i', '', $sql);
$sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);

echo "Executing SQL...\n";

$mysqli->multi_query($sql);

$queryCount = 0;
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
    $queryCount++;
} while ($mysqli->next_result());

if ($mysqli->errno) {
    echo "⚠️ MySQL Error: " . $mysqli->error . "\n";
}

echo "\n✅ Import completed! ($queryCount queries)\n\n";

// Verify
echo "📋 Tables in database:\n";
$result = $mysqli->query("SHOW TABLES");
$tableCount = 0;
while ($row = $result->fetch_array()) {
    $tableCount++;
    $countResult = $mysqli->query("SELECT COUNT(*) as cnt FROM `" . $row[0] . "`");
    $countRow = $countResult->fetch_assoc();
    echo "   - " . $row[0] . " (" . $countRow['cnt'] . " rows)\n";
}

echo "\nTotal tables: $tableCount\n";
$mysqli->close();
echo "\n🎉 Done!\n";
?>
