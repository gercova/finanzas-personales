<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT e.expense_id AS id, e.concept, a.account_name, fc.category_name,
           e.expense_date, e.amount, e.receipt_image, e.status, e.notes,
           e.account_id, e.category_id, e.project_id
    FROM expenses e
    LEFT JOIN accounts a ON e.account_id = a.account_id
    LEFT JOIN financial_categories fc ON e.category_id = fc.category_id
    WHERE e.created_by = ? AND e.is_deleted = 0
    ORDER BY e.expense_date DESC, e.expense_id DESC
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
    $imgPath = null;
    if (!empty($row['receipt_image']) && file_exists(__DIR__ . '/../' . $row['receipt_image'])) {
        $imgPath = (string)$row['receipt_image'];
    }

    $data[] = [
        'id'            => (int)$row['id'],
        'concept'       => (string)($row['concept'] ?? ''),
        'account_id'    => $row['account_id'] ? (int)$row['account_id'] : null,
        'account_name'  => (string)($row['account_name'] ?? ''),
        'category_id'   => $row['category_id'] ? (int)$row['category_id'] : null,
        'category_name' => (string)($row['category_name'] ?? ''),
        'project_id'    => $row['project_id'] ? (int)$row['project_id'] : null,
        'expense_date'  => $row['expense_date'] ? date('Y-m-d', strtotime($row['expense_date'])) : '',
        'amount'        => (float)$row['amount'],
        'receipt_image' => $imgPath,
        'status'        => (string)($row['status'] ?? ''),
        'notes'         => (string)($row['notes'] ?? ''),
    ];
}
$stmt->close();
echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
