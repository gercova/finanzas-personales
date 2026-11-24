<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$loan_id          = (int)($_POST['loan_id']             ?? 0);
$borrower_name    = cleanInput($_POST['borrower_name']   ?? '');
$concept          = cleanInput($_POST['concept']         ?? '');
$account_id       = (int)($_POST['account_id']           ?? 0);
$loan_date        = cleanInput($_POST['loan_date']       ?? '');
$due_date         = !empty($_POST['due_date']) ? cleanInput($_POST['due_date']) : null;
$principal_amount = (float)($_POST['principal_amount']   ?? 0);
$interest_type    = cleanInput($_POST['interest_type']   ?? 'NINGUNO');
$interest_value   = !empty($_POST['interest_value']) ? (float)$_POST['interest_value'] : null;
$status           = cleanInput($_POST['status']          ?? 'PENDIENTE');
$notes            = cleanInput($_POST['notes']           ?? '');
$userId           = (int)$_SESSION['userId'];

$validTypes    = ['NINGUNO','PORCENTAJE','FIJO'];
$validStatuses = ['PENDIENTE','PARCIAL','PAGADO'];
if (!in_array($interest_type, $validTypes)) $interest_type = 'NINGUNO';
if (!in_array($status, $validStatuses)) $status = 'PENDIENTE';

if ($loan_id <= 0 || empty($borrower_name) || $account_id <= 0 || $principal_amount <= 0 || empty($loan_date)) {
    echo json_encode(['success'=>false,'messages'=>'Datos incompletos o inválidos.']);
    exit;
}

// Ownership check + get old data
$stmtOld = $connect->prepare("SELECT principal_amount, account_id, amount_repaid FROM loans WHERE id=? AND created_by=?");
if (!$stmtOld) { echo json_encode(['success'=>false,'messages'=>'Error interno.']); exit; }
$stmtOld->bind_param("ii", $loan_id, $userId);
$stmtOld->execute();
$old = $stmtOld->get_result()->fetch_assoc();
$stmtOld->close();
if (!$old) { echo json_encode(['success'=>false,'messages'=>'Registro no encontrado.']); exit; }

// Recalculate
if ($interest_type === 'PORCENTAJE' && $interest_value > 0) {
    $total_receivable = $principal_amount + ($principal_amount * $interest_value / 100);
} elseif ($interest_type === 'FIJO' && $interest_value > 0) {
    $total_receivable = $principal_amount + $interest_value;
} else {
    $total_receivable = $principal_amount;
    $interest_value = null;
}
$balance_pending = max(0, $total_receivable - (float)$old['amount_repaid']);

$stmt = $connect->prepare(
    "UPDATE loans SET borrower_name=?, concept=?, account_id=?, loan_date=?, due_date=?, principal_amount=?, interest_type=?, interest_value=?, total_receivable=?, balance_pending=?, status=?, notes=?
     WHERE id=? AND created_by=?"
);
if (!$stmt) { echo json_encode(['success'=>false,'messages'=>'Error preparando consulta.']); exit; }
$stmt->bind_param("ssississddssii",
    $borrower_name, $concept, $account_id, $loan_date, $due_date,
    $principal_amount, $interest_type, $interest_value,
    $total_receivable, $balance_pending, $status, $notes,
    $loan_id, $userId
);

if ($stmt->execute()) {
    echo json_encode(['success'=>true,'messages'=>'Préstamo actualizado exitosamente.']);
} else {
    echo json_encode(['success'=>false,'messages'=>'Error al actualizar: '.$connect->error]);
}
$stmt->close();
