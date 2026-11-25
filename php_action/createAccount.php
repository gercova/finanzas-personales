<?php
require_once 'core.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF. Recargue la página.']);
    exit;
}

$userId         = (int)$_SESSION['userId'];
$accountName    = cleanInput($_POST['account_name'] ?? '');
$accountType    = cleanInput($_POST['account_type'] ?? 'BANK');
$accountNumber  = cleanInput($_POST['account_number'] ?? '');
$currency       = cleanInput($_POST['currency'] ?? 'PEN');
$initialBalance = (float)($_POST['initial_balance'] ?? 0.00);
$color          = trim($_POST['color'] ?? '#2e6da4');
$status         = isset($_POST['status']) ? (int)$_POST['status'] : 1;

$validTypes = ['BANK', 'DIGITAL_WALLET', 'CASH', 'SAVINGS', 'OTHER'];
if (!in_array($accountType, $validTypes, true)) {
    $accountType = 'BANK';
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    $color = '#2e6da4';
}
$status = ($status === 0) ? 0 : 1;
$currency = substr(strtoupper($currency ?: 'PEN'), 0, 10);

if (empty($accountName)) {
    echo json_encode(['success' => false, 'messages' => 'El nombre de la cuenta es obligatorio.']);
    exit;
}

$stmt = $connect->prepare("
    INSERT INTO accounts (account_name, account_type, account_number, currency, initial_balance, current_balance, color, created_by, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando la consulta.']);
    exit;
}

$stmt->bind_param("ssssddsii", $accountName, $accountType, $accountNumber, $currency, $initialBalance, $initialBalance, $color, $userId, $status);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Cuenta registrada exitosamente.']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al registrar la cuenta.']);
}
$stmt->close();
