<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$income_id = (int)($_POST['income_id'] ?? 0);
$userId    = (int)$_SESSION['userId'];

if ($income_id <= 0) {
    echo json_encode(['success' => false, 'messages' => 'ID inválido.']);
    exit;
}

$stmtOld = $connect->prepare("SELECT amount, account_id FROM incomes WHERE income_id=? AND created_by=? AND is_deleted=0");
if (!$stmtOld) { echo json_encode(['success'=>false,'messages'=>'Error interno.']); exit; }
$stmtOld->bind_param("ii", $income_id, $userId);
$stmtOld->execute();
$old = $stmtOld->get_result()->fetch_assoc();
$stmtOld->close();
if (!$old) { echo json_encode(['success'=>false,'messages'=>'Registro no encontrado o ya fue eliminado.']); exit; }

$stmt = $connect->prepare("UPDATE incomes SET is_deleted = 1, deleted_at = NOW() WHERE income_id=? AND created_by=?");
if (!$stmt) { echo json_encode(['success'=>false,'messages'=>'Error preparando consulta.']); exit; }
$stmt->bind_param("ii", $income_id, $userId);

if ($stmt->execute()) {
    // Reverse balance
    $stmtAcc = $connect->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE account_id = ?");
    if ($stmtAcc) { $stmtAcc->bind_param("di", $old['amount'], $old['account_id']); $stmtAcc->execute(); $stmtAcc->close(); }
    echo json_encode(['success'=>true,'messages'=>'Ingreso eliminado exitosamente (borrado lógico).']);
} else {
    echo json_encode(['success'=>false,'messages'=>'Error al eliminar.']);
}
$stmt->close();
