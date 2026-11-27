<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

if (!tableExists($connect, 'debts') || !tableExists($connect, 'lenders')) {
    echo json_encode([
        'success'         => false,
        'needs_migration' => true,
        'data'            => [],
        'messages'        => 'Las tablas del módulo de Préstamos Recibidos aún no existen. Ejecute la migración 004 desde Configuración General del Sistema (Admin).'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $connect->prepare("
        SELECT d.debt_id AS id, d.lender_id, d.lender_name, d.lender_type, d.concept, a.account_name,
               d.debt_date, d.due_date, d.principal_amount,
               d.interest_type, d.interest_value, d.total_payable,
               d.amount_paid, d.balance_pending, d.status, d.notes,
               d.account_id, l.document_type, l.document_number
        FROM debts d
        LEFT JOIN accounts a ON d.account_id = a.account_id
        LEFT JOIN lenders l ON d.lender_id = l.lender_id
        WHERE d.created_by = ? AND d.is_deleted = 0
        ORDER BY d.debt_date DESC, d.debt_id DESC
    ");
    if (!$stmt) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
} catch (\Throwable $e) {
    echo json_encode([
        'success'  => false,
        'data'     => [],
        'messages' => 'Error consultando préstamos recibidos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id'               => (int)$row['id'],
        'lender_id'        => $row['lender_id'] ? (int)$row['lender_id'] : null,
        'lender_name'      => (string)($row['lender_name'] ?? ''),
        'lender_type'      => (string)($row['lender_type'] ?? 'PERSONA'),
        'document_type'    => (string)($row['document_type'] ?? ''),
        'document_number'  => (string)($row['document_number'] ?? ''),
        'concept'          => (string)($row['concept'] ?? ''),
        'notes'            => (string)($row['notes'] ?? ''),
        'account_id'       => $row['account_id'] ? (int)$row['account_id'] : null,
        'account_name'     => (string)($row['account_name'] ?? ''),
        'debt_date'        => $row['debt_date'] ? date('Y-m-d', strtotime($row['debt_date'])) : '',
        'due_date'         => $row['due_date'] ? date('Y-m-d', strtotime($row['due_date'])) : '',
        'principal_amount' => (float)$row['principal_amount'],
        'interest_type'    => (string)($row['interest_type'] ?? 'NONE'),
        'interest_value'   => (float)$row['interest_value'],
        'total_payable'    => (float)$row['total_payable'],
        'amount_paid'      => (float)$row['amount_paid'],
        'balance_pending'  => (float)$row['balance_pending'],
        'status'           => (string)($row['status'] ?? 'PENDIENTE'),
    ];
}
$stmt->close();
echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
