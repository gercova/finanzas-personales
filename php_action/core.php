<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/security.php';

// Initialize hardened cookie-based session
initSecureSession();

if (!isset($_SESSION['userId']) || empty($_SESSION['userId'])) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $isAction   = (strpos($scriptName, '/php_action/') !== false);
    $isAjax     = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                  || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAction || $isAjax) {
        if (!headers_sent()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'messages' => 'Sesión no iniciada o expirada. Inicie sesión nuevamente.']);
        exit();
    }

    header('location: index.php');
    exit();
}
