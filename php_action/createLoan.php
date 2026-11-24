<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$borrower_name    = cleanInput($_POST['borrower_name']    ?? '');
$concept          = cleanInput($_POST['concept']          ?? '');
$account_id       = (int)($_POST['account_id']            ?? 0);
$loan_date        = cleanInput($_POST['loan_date']        ?? '');
$due_date         = !empty($_POST['due_date']) ? cleanInput($_POST['due_date']) : null;
$principal_amount = (float)($_POST['principal_amount']    ?? 0);
$interest_type    = cleanInput($_POST['interest_type']    ?? 'NINGUNO');
$interest_value   = !empty($_POST['interest_value']) ? (float)$_POST['interest_value'] : null;
$total_receivable = (float)($_POST['total_receivable']    ?? $principal_amount);
$notes            = cleanInput($_POST['notes']            ?? '');
$userId           = (int)$_SESSION['userId'];

$validTypes = ['NINGUNO', 'PORCENTAJE', 'FIJO'];
if (!in_array($interest_type, $validTypes)) $interest_type = 'NINGUNO';

if (empty($borrower_name) || empty($concept) || $account_id <= 0 || $principal_amount <= 0 || empty($loan_date)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos o inválidos.']);
    exit;
}

// Recalculate total_receivable server-side
if ($interest_type === 'PORCENTAJE' && $interest_value > 0) {
    $total_receivable = $principal_amount + ($principal_amount * $interest_value / 100);
} elseif ($interest_type === 'FIJO' && $interest_value > 0) {
    $total_receivable = $principal_amount + $interest_value;
} else {
    $total_receivable = $principal_amount;
    $interest_value = null;
}

$balance_pending = $total_receivable;

$stmt = $connect->prepare(
    "INSERT INTO loans (borrower_name, concept, account_id, loan_date, due_date, principal_amount, interest_type, interest_value, total_receivable, amount_repaid, balance_pending, status, notes, created_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'PENDIENTE', ?, ?)"
);
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta.']);
    exit;
}
// s,s,i,s,s,d,s,d,d,d,s,i = 12 params
$stmt->bind_param("ssississddsi",
    $borrower_name, $concept, $account_id, $loan_date, $due_date,
    $principal_amount, $interest_type, $interest_value,
    $total_receivable, $balance_pending, $notes, $userId
);

if ($stmt->execute()) {
    // Deduct from account (money lent out)
    $s = $connect->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?");
    if ($s) { $s->bind_param("di", $principal_amount, $account_id); $s->execute(); $s->close(); }
    echo json_encode(['success' => true, 'messages' => 'Préstamo registrado exitosamente.']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al guardar: ' . $connect->error]);
}
$stmt->close();
