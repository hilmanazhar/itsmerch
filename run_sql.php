<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'its_merchandise';

$mysqli = new mysqli($host, $user, $pass);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$sql = file_get_contents('complete_database_reset.sql');

if ($mysqli->multi_query($sql)) {
    try {
        do {
            // store first result set
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
            // print divider
            if ($mysqli->more_results()) {
                // printf("-----------------\n");
            }
        } while ($mysqli->next_result());
        echo "Database reset successfully with ALL tables!";
    } catch (mysqli_sql_exception $e) {
        echo "Error executing SQL: " . $e->getMessage();
    }
} else {
    echo "Error executing SQL: " . $mysqli->error;
}

$mysqli->close();
?>
