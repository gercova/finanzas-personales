<?php
require_once 'core.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF. Recargue la página.']);
    exit;
}

$userId        = (int)$_SESSION['userId'];
$accountId     = (int)($_POST['account_id'] ?? 0);
$accountName   = cleanInput($_POST['account_name'] ?? '');
$accountType   = cleanInput($_POST['account_type'] ?? 'BANK');
$accountNumber = cleanInput($_POST['account_number'] ?? '');
$currency      = cleanInput($_POST['currency'] ?? 'PEN');
$color         = trim($_POST['color'] ?? '#2e6da4');
$status        = isset($_POST['status']) ? (int)$_POST['status'] : 1;

$validTypes = ['BANK', 'DIGITAL_WALLET', 'CASH', 'SAVINGS', 'OTHER'];
if (!in_array($accountType, $validTypes, true)) {
    $accountType = 'BANK';
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    $color = '#2e6da4';
}
$status   = ($status === 0) ? 0 : 1;
$currency = substr(strtoupper($currency ?: 'PEN'), 0, 10);

if ($accountId <= 0 || empty($accountName)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'accounts', 'account_id', $accountId, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Cuenta no encontrada o sin permiso.']);
    exit;
}

$stmt = $connect->prepare("
    UPDATE accounts
    SET account_name = ?, account_type = ?, account_number = ?, currency = ?, color = ?, status = ?
    WHERE account_id = ? AND created_by = ? AND is_deleted = 0
");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta.']);
    exit;
}
$stmt->bind_param("sssssiii", $accountName, $accountType, $accountNumber, $currency, $color, $status, $accountId, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Cuenta actualizada exitosamente.']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al actualizar la cuenta.']);
}
$stmt->close();
