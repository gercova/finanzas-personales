<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$loan_id = (int)($_POST['loan_id'] ?? 0);
$userId  = (int)$_SESSION['userId'];

if ($loan_id <= 0) { echo json_encode(['success'=>false,'messages'=>'ID inválido.']); exit; }

if (!verifyOwnedEntity($connect, 'loans', 'loan_id', $loan_id, $userId)) {
    echo json_encode(['success'=>false,'messages'=>'Registro no encontrado o ya fue eliminado.']);
    exit;
}

// Soft-delete payments first (trg_loan_payments_after_update reverses their effect)
$stmtPay = $connect->prepare("UPDATE loan_payments SET is_deleted=1, deleted_at=NOW() WHERE loan_id=? AND created_by=? AND is_deleted=0");
if ($stmtPay) { $stmtPay->bind_param("ii", $loan_id, $userId); $stmtPay->execute(); $stmtPay->close(); }

$stmt = $connect->prepare("UPDATE loans SET is_deleted=1, deleted_at=NOW() WHERE loan_id=? AND created_by=?");
if (!$stmt) { echo json_encode(['success'=>false,'messages'=>'Error preparando consulta.']); exit; }
$stmt->bind_param("ii", $loan_id, $userId);

// Account balance restoration is handled automatically by trg_loans_after_update
if ($stmt->execute()) {
    echo json_encode(['success'=>true,'messages'=>'Préstamo eliminado exitosamente (borrado lógico).']);
} else {
    echo json_encode(['success'=>false,'messages'=>'Error al eliminar.']);
}
$stmt->close();
