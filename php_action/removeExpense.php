<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$expense_id = (int)($_POST['expense_id'] ?? 0);
$userId     = (int)$_SESSION['userId'];

if ($expense_id <= 0) {
    echo json_encode(['success' => false, 'messages' => 'ID inválido.']);
    exit;
}

$stmtOld = $connect->prepare("SELECT amount, account_id, status, receipt_image FROM expenses WHERE id=? AND created_by=?");
if (!$stmtOld) { echo json_encode(['success'=>false,'messages'=>'Error interno.']); exit; }
$stmtOld->bind_param("ii", $expense_id, $userId);
$stmtOld->execute();
$old = $stmtOld->get_result()->fetch_assoc();
$stmtOld->close();
if (!$old) { echo json_encode(['success'=>false,'messages'=>'Registro no encontrado.']); exit; }

$stmt = $connect->prepare("DELETE FROM expenses WHERE id=? AND created_by=?");
if (!$stmt) { echo json_encode(['success'=>false,'messages'=>'Error preparando consulta.']); exit; }
$stmt->bind_param("ii", $expense_id, $userId);

if ($stmt->execute()) {
    // Delete receipt image
    if (!empty($old['receipt_image']) && file_exists(__DIR__ . '/../' . $old['receipt_image'])) {
        @unlink(__DIR__ . '/../' . $old['receipt_image']);
    }
    // Reverse balance
    if ($old['status'] === 'PAGADO') {
        $s = $connect->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?");
        if ($s) { $s->bind_param("di", $old['amount'], $old['account_id']); $s->execute(); $s->close(); }
    }
    echo json_encode(['success'=>true,'messages'=>'Gasto eliminado exitosamente.']);
} else {
    echo json_encode(['success'=>false,'messages'=>'Error al eliminar.']);
}
$stmt->close();
