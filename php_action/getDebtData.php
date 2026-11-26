<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$debt_id = (int)($_GET['id'] ?? $_POST['debt_id'] ?? 0);
$userId  = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT debt_id, lender_id, lender_name, lender_type, concept, account_id,
           debt_date, due_date, principal_amount, interest_type, interest_value,
           total_payable, amount_paid, balance_pending, status, notes
    FROM debts
    WHERE debt_id = ? AND created_by = ? AND is_deleted = 0
");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error de consulta.']);
    exit;
}
$stmt->bind_param("ii", $debt_id, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
    echo json_encode(['success' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'messages' => 'Préstamo recibido no encontrado.']);
}
