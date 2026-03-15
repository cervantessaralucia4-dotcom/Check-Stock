<?php
// config/db.php
// Railway inyecta estas variables automáticamente cuando agregas MySQL
// No necesitas cambiar nada si usas Railway

$host = getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: 'localhost';
$port = getenv('MYSQLPORT')     ?: getenv('DB_PORT') ?: '3306';
$name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'stockflow';
$user = getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
}
