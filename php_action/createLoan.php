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
$interest_type    = cleanInput($_POST['interest_type']    ?? 'NONE');
$interest_value   = !empty($_POST['interest_value']) ? (float)$_POST['interest_value'] : null;
$total_receivable = (float)($_POST['total_receivable']    ?? $principal_amount);
$notes            = cleanInput($_POST['notes']            ?? '');
$userId           = (int)$_SESSION['userId'];

$validTypes = ['NONE', 'PERCENTAGE', 'FIXED', 'NINGUNO', 'PORCENTAJE', 'FIJO'];
if (!in_array($interest_type, $validTypes)) $interest_type = 'NONE';
if ($interest_type === 'NINGUNO') $interest_type = 'NONE';
if ($interest_type === 'PORCENTAJE') $interest_type = 'PERCENTAGE';
if ($interest_type === 'FIJO') $interest_type = 'FIXED';

if (empty($borrower_name) || empty($concept) || $account_id <= 0 || $principal_amount <= 0 || empty($loan_date)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos o inválidos.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'accounts', 'account_id', $account_id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Cuenta no válida o sin permiso.']);
    exit;
}

// Recalculate total_receivable server-side
if ($interest_type === 'PERCENTAGE' && $interest_value > 0) {
    $total_receivable = $principal_amount + ($principal_amount * $interest_value / 100);
} elseif ($interest_type === 'FIXED' && $interest_value > 0) {
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
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta: ' . $connect->error]);
    exit;
}
$stmt->bind_param("ssissdsdddsi",
    $borrower_name, $concept, $account_id, $loan_date, $due_date,
    $principal_amount, $interest_type, $interest_value,
    $total_receivable, $balance_pending, $notes, $userId
);

// Account balance deduction is handled automatically by trg_loans_after_insert
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Préstamo registrado exitosamente.']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al guardar: ' . $connect->error]);
}
$stmt->close();
