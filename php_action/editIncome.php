<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$income_id    = (int)($_POST['income_id']        ?? 0);
$concept      = cleanInput($_POST['concept']      ?? '');
$account_id   = (int)($_POST['account_id']        ?? 0);
$category_id  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
$project_id   = !empty($_POST['project_id'])  ? (int)$_POST['project_id']  : null;
$income_date  = cleanInput($_POST['income_date']  ?? '');
$amount       = (float)($_POST['amount']          ?? 0);
$is_recurring = isset($_POST['is_recurring']) && $_POST['is_recurring'] == '1' ? 1 : 0;
$status       = cleanInput($_POST['status']       ?? 'CONFIRMED');
$notes        = cleanInput($_POST['notes']        ?? '');
$userId       = (int)$_SESSION['userId'];

$validStatuses = ['CONFIRMED', 'PENDING', 'CANCELLED', 'ACTIVO', 'PENDIENTE', 'CANCELADO'];
if (!in_array($status, $validStatuses)) $status = 'CONFIRMED';
if ($status === 'ACTIVO') $status = 'CONFIRMED';
if ($status === 'PENDIENTE') $status = 'PENDING';
if ($status === 'CANCELADO') $status = 'CANCELLED';

if ($income_id <= 0 || empty($concept) || $account_id <= 0 || $amount <= 0 || empty($income_date)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos o inválidos.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'incomes', 'income_id', $income_id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Registro no encontrado.']);
    exit;
}
if (!verifyOwnedEntity($connect, 'accounts', 'account_id', $account_id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Cuenta no válida o sin permiso.']);
    exit;
}

$stmt = $connect->prepare(
    "UPDATE incomes SET concept=?, account_id=?, category_id=?, project_id=?, income_date=?, amount=?, is_recurring=?, status=?, notes=?
     WHERE income_id=? AND created_by=? AND is_deleted=0"
);
if (!$stmt) { echo json_encode(['success'=>false,'messages'=>'Error preparando consulta: ' . $connect->error]); exit; }
$stmt->bind_param("siiisdissii", $concept, $account_id, $category_id, $project_id, $income_date, $amount, $is_recurring, $status, $notes, $income_id, $userId);

// Account balance adjustment is handled automatically by trg_incomes_after_update
if ($stmt->execute()) {
    echo json_encode(['success'=>true,'messages'=>'Ingreso actualizado exitosamente.']);
} else {
    echo json_encode(['success'=>false,'messages'=>'Error al actualizar: '.$connect->error]);
}
$stmt->close();
