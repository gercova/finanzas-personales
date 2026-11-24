<?php 
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/security.php';

// Initialize hardened cookie-based session
initSecureSession();

if (!isset($_SESSION['userId']) || empty($_SESSION['userId'])) {
    header('location: index.php');	
    exit();
} 
?>