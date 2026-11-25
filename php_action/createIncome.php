<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$concept      = cleanInput($_POST['concept']     ?? '');
$account_id   = (int)($_POST['account_id']       ?? 0);
$category_id  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
$project_id   = !empty($_POST['project_id'])  ? (int)$_POST['project_id']  : null;
$income_date  = cleanInput($_POST['income_date'] ?? '');
$amount       = (float)($_POST['amount']         ?? 0);
$is_recurring = isset($_POST['is_recurring']) && $_POST['is_recurring'] == '1' ? 1 : 0;
$status       = cleanInput($_POST['status']      ?? 'CONFIRMED');
$notes        = cleanInput($_POST['notes']       ?? '');
$userId       = (int)$_SESSION['userId'];

$validStatuses = ['CONFIRMED', 'PENDING', 'CANCELLED', 'ACTIVO', 'PENDIENTE', 'CANCELADO'];
if (!in_array($status, $validStatuses)) $status = 'CONFIRMED';
if ($status === 'ACTIVO') $status = 'CONFIRMED';
if ($status === 'PENDIENTE') $status = 'PENDING';
if ($status === 'CANCELADO') $status = 'CANCELLED';

if (empty($concept) || $account_id <= 0 || $amount <= 0 || empty($income_date)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos o inválidos.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'accounts', 'account_id', $account_id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Cuenta no válida o sin permiso.']);
    exit;
}

$stmt = $connect->prepare(
    "INSERT INTO incomes (concept, account_id, category_id, project_id, income_date, amount, is_recurring, status, notes, created_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta: ' . $connect->error]);
    exit;
}
$stmt->bind_param("siiisdissi", $concept, $account_id, $category_id, $project_id, $income_date, $amount, $is_recurring, $status, $notes, $userId);

// Account balance is synchronized automatically by trg_incomes_after_insert
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Ingreso registrado exitosamente.']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al guardar: ' . $connect->error]);
}
$stmt->close();
