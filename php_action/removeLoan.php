<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$loan_id = (int)($_POST['loan_id'] ?? 0);
$userId  = (int)$_SESSION['userId'];

if ($loan_id <= 0) { echo json_encode(['success'=>false,'messages'=>'ID inválido.']); exit; }

$stmtOld = $connect->prepare("SELECT principal_amount, account_id, status FROM loans WHERE loan_id=? AND created_by=? AND is_deleted=0");
if (!$stmtOld) { echo json_encode(['success'=>false,'messages'=>'Error interno.']); exit; }
$stmtOld->bind_param("ii", $loan_id, $userId);
$stmtOld->execute();
$old = $stmtOld->get_result()->fetch_assoc();
$stmtOld->close();
if (!$old) { echo json_encode(['success'=>false,'messages'=>'Registro no encontrado o ya fue eliminado.']); exit; }

// Soft-delete payments first
$stmtPay = $connect->prepare("UPDATE loan_payments SET is_deleted=1, deleted_at=NOW() WHERE loan_id=?");
if ($stmtPay) { $stmtPay->bind_param("i", $loan_id); $stmtPay->execute(); $stmtPay->close(); }

$stmt = $connect->prepare("UPDATE loans SET is_deleted=1, deleted_at=NOW() WHERE loan_id=? AND created_by=?");
if (!$stmt) { echo json_encode(['success'=>false,'messages'=>'Error preparando consulta.']); exit; }
$stmt->bind_param("ii", $loan_id, $userId);

if ($stmt->execute()) {
    // Restore account balance (loan was deducted when created)
    if ($old['status'] !== 'PAGADO') {
        $s = $connect->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE account_id = ?");
        if ($s) { $s->bind_param("di", $old['principal_amount'], $old['account_id']); $s->execute(); $s->close(); }
    }
    echo json_encode(['success'=>true,'messages'=>'Préstamo eliminado exitosamente (borrado lógico).']);
} else {
    echo json_encode(['success'=>false,'messages'=>'Error al eliminar.']);
}
$stmt->close();
