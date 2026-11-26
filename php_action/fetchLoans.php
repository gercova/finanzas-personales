<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT l.loan_id AS id, l.borrower_id, l.borrower_name, l.concept, a.account_name,
           l.loan_date, l.due_date, l.principal_amount,
           l.interest_type, l.interest_value, l.total_receivable,
           l.amount_repaid, l.balance_pending, l.status, l.notes,
           l.account_id, b.document_type, b.document_number
    FROM loans l
    LEFT JOIN accounts a ON l.account_id = a.account_id
    LEFT JOIN borrowers b ON l.borrower_id = b.borrower_id
    WHERE l.created_by = ? AND l.is_deleted = 0
    ORDER BY l.loan_date DESC, l.loan_id DESC
");
if (!$stmt) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id'               => (int)$row['id'],
        'borrower_id'      => $row['borrower_id'] ? (int)$row['borrower_id'] : null,
        'borrower_name'    => (string)($row['borrower_name'] ?? ''),
        'document_type'    => (string)($row['document_type'] ?? ''),
        'document_number'  => (string)($row['document_number'] ?? ''),
        'concept'          => (string)($row['concept'] ?? ''),
        'notes'            => (string)($row['notes'] ?? ''),
        'account_id'       => $row['account_id'] ? (int)$row['account_id'] : null,
        'account_name'     => (string)($row['account_name'] ?? ''),
        'loan_date'        => $row['loan_date'] ? date('Y-m-d', strtotime($row['loan_date'])) : '',
        'due_date'         => $row['due_date'] ? date('Y-m-d', strtotime($row['due_date'])) : '',
        'principal_amount' => (float)$row['principal_amount'],
        'interest_type'    => (string)($row['interest_type'] ?? 'NONE'),
        'interest_value'   => (float)$row['interest_value'],
        'total_receivable' => (float)$row['total_receivable'],
        'amount_repaid'    => (float)$row['amount_repaid'],
        'balance_pending'  => (float)$row['balance_pending'],
        'status'           => (string)($row['status'] ?? 'PENDIENTE'),
    ];
}
$stmt->close();
echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
