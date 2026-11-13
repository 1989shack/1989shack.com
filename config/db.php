<?php
// Safe DB loader for 1989shack site — use environment variables or hosting panel env
// DO NOT store sensitive values directly in repo

$DB_HOST = getenv('DB_HOST') ?: 'your-cloud-db-host.example';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_NAME = getenv('DB_NAME') ?: 'your_db_name';
$DB_USER = getenv('DB_USER') ?: 'your_db_user';
$DB_PASS = getenv('DB_PASS') ?: 'your_db_pass';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        $options
    );
} catch (PDOException $e) {
    error_log('DB connection error: ' . $e->getMessage());
    // Fail safely for production — return generic error
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
