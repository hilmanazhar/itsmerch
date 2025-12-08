<?php
// Quick test file
$mysqli = new mysqli('localhost', 'root', '', 'its_merchandise');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if destination_id column exists
$result = $mysqli->query("SHOW COLUMNS FROM user_addresses LIKE 'destination_id'");
if ($result->num_rows == 0) {
    echo "Adding destination_id column...\n";
    if ($mysqli->query("ALTER TABLE user_addresses ADD COLUMN destination_id VARCHAR(20) DEFAULT NULL")) {
        echo "Column added successfully!\n";
    } else {
        echo "Error: " . $mysqli->error . "\n";
    }
} else {
    echo "destination_id column already exists\n";
}

// Show all columns
echo "\nCurrent columns:\n";
$result = $mysqli->query("SHOW COLUMNS FROM user_addresses");
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

// Show addresses
echo "\nExisting addresses:\n";
$result = $mysqli->query("SELECT id, label, recipient_name, destination_id FROM user_addresses");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Label: {$row['label']}, Recipient: {$row['recipient_name']}, Dest: " . ($row['destination_id'] ?? 'null') . "\n";
}

$mysqli->close();
echo "\nDone!\n";
?>
