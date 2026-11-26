<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT account_id AS id, account_name, account_type, account_number, currency,
           initial_balance, current_balance, color, status
    FROM accounts
    WHERE created_by = ? AND is_deleted = 0
    ORDER BY current_balance DESC, account_name ASC
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
        'id'              => (int)$row['id'],
        'account_name'    => (string)($row['account_name'] ?? ''),
        'account_type'    => (string)($row['account_type'] ?? 'OTHER'),
        'account_number'  => (string)($row['account_number'] ?? ''),
        'currency'        => (string)($row['currency'] ?: 'PEN'),
        'initial_balance' => (float)$row['initial_balance'],
        'current_balance' => (float)$row['current_balance'],
        'color'           => (string)($row['color'] ?: '#2e6da4'),
        'status'          => (int)$row['status'],
    ];
}
$stmt->close();
echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
