<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT
        p.project_id AS id,
        p.project_name,
        p.client_name,
        p.budget,
        p.start_date,
        p.end_date,
        p.status,
        COALESCE(SUM(i.amount), 0) AS project_income,
        COALESCE(SUM(e.amount), 0) AS project_expense
    FROM projects p
    LEFT JOIN incomes  i ON i.project_id = p.project_id AND i.is_deleted = 0
    LEFT JOIN expenses e ON e.project_id = p.project_id AND e.is_deleted = 0
    WHERE p.is_deleted = 0 AND (p.created_by = ? OR p.created_by IS NULL)
    GROUP BY p.project_id
    ORDER BY p.created_at DESC, p.project_id DESC
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
    $income  = (float)$row['project_income'];
    $expense = (float)$row['project_expense'];
    $budget  = (float)$row['budget'];
    $base    = max($income, $budget, 1);
    $ratio   = round(($expense / $base) * 100, 1);

    $data[] = [
        'id'              => (int)$row['id'],
        'project_name'    => (string)($row['project_name'] ?? ''),
        'client_name'     => (string)($row['client_name'] ?? ''),
        'budget'          => $budget,
        'start_date'      => $row['start_date'] ? date('Y-m-d', strtotime($row['start_date'])) : '',
        'end_date'        => $row['end_date'] ? date('Y-m-d', strtotime($row['end_date'])) : '',
        'status'          => (string)($row['status'] ?? 'ACTIVE'),
        'project_income'  => $income,
        'project_expense' => $expense,
        'forecast_ratio'  => $ratio,
    ];
}
$stmt->close();
echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
