<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

try {
    $stmt = $connect->prepare("
        SELECT
            p.project_id AS id,
            p.project_name,
            p.client_name,
            p.budget,
            p.start_date,
            p.end_date,
            p.status,
            p.description,
            COALESCE(inc.total_income, 0)  AS project_income,
            COALESCE(exp.total_expense, 0) AS project_expense
        FROM projects p
        LEFT JOIN (
            SELECT project_id, SUM(amount) AS total_income
            FROM incomes
            WHERE created_by = ? AND is_deleted = 0 AND status NOT IN ('CANCELLED', 'CANCELADO') AND project_id IS NOT NULL
            GROUP BY project_id
        ) inc ON inc.project_id = p.project_id
        LEFT JOIN (
            SELECT project_id, SUM(amount) AS total_expense
            FROM expenses
            WHERE created_by = ? AND is_deleted = 0 AND project_id IS NOT NULL
            GROUP BY project_id
        ) exp ON exp.project_id = p.project_id
        WHERE p.is_deleted = 0 AND p.created_by = ?
        ORDER BY p.created_at DESC, p.project_id DESC
    ");
    if (!$stmt) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $stmt->bind_param("iii", $userId, $userId, $userId);
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
            'description'     => (string)($row['description'] ?? ''),
            'project_income'  => $income,
            'project_expense' => $expense,
            'forecast_ratio'  => $ratio,
        ];
    }
    $stmt->close();
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'data' => [], 'messages' => 'Error cargando proyectos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
