<?php
require_once 'core.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
$userId = (int)$_SESSION['userId'];

if ($id <= 0) { echo json_encode(['success'=>false,'messages'=>'ID inválido.']); exit; }

$stmt = $connect->prepare(
    "SELECT loan_id AS id, borrower_id, borrower_name, concept, account_id, loan_date, due_date,
            principal_amount, interest_type, interest_value, total_receivable,
            amount_repaid, balance_pending, status, notes
     FROM loans WHERE loan_id=? AND created_by=? AND is_deleted=0"
);
if (!$stmt) { echo json_encode(['success'=>false,'messages'=>'Error interno.']); exit; }
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) { echo json_encode(['success'=>false,'messages'=>'Registro no encontrado.']); exit; }
echo json_encode(['success'=>true,'data'=>$row]);
