<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$concept     = cleanInput($_POST['concept']     ?? '');
$account_id  = (int)($_POST['account_id']       ?? 0);
$category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
$project_id  = !empty($_POST['project_id'])  ? (int)$_POST['project_id']  : null;
$income_date = cleanInput($_POST['income_date'] ?? '');
$amount      = (float)($_POST['amount']         ?? 0);
$is_recurring = isset($_POST['is_recurring']) && $_POST['is_recurring'] == '1' ? 1 : 0;
$status      = cleanInput($_POST['status']      ?? 'ACTIVO');
$notes       = cleanInput($_POST['notes']       ?? '');
$userId      = (int)$_SESSION['userId'];

$validStatuses = ['ACTIVO', 'PENDIENTE', 'CANCELADO'];
if (!in_array($status, $validStatuses)) $status = 'ACTIVO';

if (empty($concept) || $account_id <= 0 || $amount <= 0 || empty($income_date)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos o inválidos.']);
    exit;
}

$stmt = $connect->prepare(
    "INSERT INTO incomes (concept, account_id, category_id, project_id, income_date, amount, is_recurring, status, notes, created_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta.']);
    exit;
}
$stmt->bind_param("siiiidissi", $concept, $account_id, $category_id, $project_id, $income_date, $amount, $is_recurring, $status, $notes, $userId);

if ($stmt->execute()) {
    // Update account balance
    $stmtAcc = $connect->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?");
    if ($stmtAcc) { $stmtAcc->bind_param("di", $amount, $account_id); $stmtAcc->execute(); $stmtAcc->close(); }
    echo json_encode(['success' => true, 'messages' => 'Ingreso registrado exitosamente.']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al guardar: ' . $connect->error]);
}
$stmt->close();
