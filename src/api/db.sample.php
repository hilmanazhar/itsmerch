<?php
// src/api/db.php
// Database connection with Railway environment variable support

// Railway provides DATABASE_URL or individual vars
$host = getenv('MYSQL_HOST') ?: getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQL_USER') ?: getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQL_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '';
$db   = getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'its_merchandise';
$port = getenv('MYSQL_PORT') ?: getenv('MYSQLPORT') ?: 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($host, $user, $pass, $db, $port);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// PDO connection for newer APIs
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'PDO connection failed: ' . $e->getMessage()]);
    exit;
}

function json_ok($data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function json_err($msg, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $msg]);
    exit;
}
?>
