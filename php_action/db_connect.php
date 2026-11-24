<?php
/**
 * Database connection handler
 * Safely loads database credentials from environment variables via env_loader.
 */
require_once __DIR__ . '/env_loader.php';

$localhost = env('DB_HOST', '127.0.0.1');
$username  = env('DB_USER', 'admin');
$password  = env('DB_PASS', '');
$dbname    = env('DB_NAME', 'personal_finance');
$port      = (int)env('DB_PORT', 3306);
$charset   = env('DB_CHARSET', 'utf8mb4');

// Establish MySQLi connection
$connect = new mysqli($localhost, $username, $password, $dbname, $port);

// Check connection
if ($connect->connect_error) {
    if (env('APP_DEBUG', false)) {
        die("Connection Failed: " . $connect->connect_error);
    } else {
        die("Database connection error. Please verify server configuration.");
    }
} else {
    $connect->set_charset($charset);
}
?>
