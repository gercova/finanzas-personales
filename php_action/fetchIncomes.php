<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT i.income_id AS id, i.concept, a.account_name, fc.category_name,
           p.project_name, i.income_date, i.amount, i.is_recurring, i.status, i.notes,
           i.account_id, i.category_id, i.project_id
    FROM incomes i
    LEFT JOIN accounts a ON i.account_id = a.account_id
    LEFT JOIN financial_categories fc ON i.category_id = fc.category_id
    LEFT JOIN projects p ON i.project_id = p.project_id
    WHERE i.created_by = ? AND i.is_deleted = 0
    ORDER BY i.income_date DESC, i.income_id DESC
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
        'id'            => (int)$row['id'],
        'concept'       => (string)($row['concept'] ?? ''),
        'account_id'    => $row['account_id'] ? (int)$row['account_id'] : null,
        'account_name'  => (string)($row['account_name'] ?? ''),
        'category_id'   => $row['category_id'] ? (int)$row['category_id'] : null,
        'category_name' => (string)($row['category_name'] ?? ''),
        'project_id'    => $row['project_id'] ? (int)$row['project_id'] : null,
        'project_name'  => (string)($row['project_name'] ?? ''),
        'income_date'   => $row['income_date'] ? date('Y-m-d', strtotime($row['income_date'])) : '',
        'amount'        => (float)$row['amount'],
        'is_recurring'  => (bool)$row['is_recurring'],
        'status'        => (string)($row['status'] ?? ''),
        'notes'         => (string)($row['notes'] ?? ''),
    ];
}
$stmt->close();
echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
