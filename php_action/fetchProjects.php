<?php
require_once 'core.php';
header('Content-Type: application/json');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT
        p.project_id,
        p.project_name,
        p.client_name,
        p.budget,
        p.start_date,
        p.end_date,
        p.status,
        COALESCE(SUM(i.amount), 0)  AS project_income,
        COALESCE(SUM(e.amount), 0)  AS project_expense
    FROM projects p
    LEFT JOIN incomes  i ON i.project_id = p.project_id AND i.is_deleted = 0
    LEFT JOIN expenses e ON e.project_id = p.project_id AND e.is_deleted = 0
    WHERE p.is_deleted = 0 AND (p.created_by = ? OR p.created_by IS NULL)
    GROUP BY p.project_id
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$statusClass = [
    'ACTIVE'    => 'success',
    'ON_HOLD'   => 'warning',
    'COMPLETED' => 'info',
];
$statusLabel = [
    'ACTIVE'    => 'Activo',
    'ON_HOLD'   => 'En Espera',
    'COMPLETED' => 'Completado',
];

$output = ['data' => []];
$x = 1;
while ($row = $result->fetch_assoc()) {
    $income  = (float)$row['project_income'];
    $expense = (float)$row['project_expense'];
    $budget  = (float)$row['budget'];
    $base    = max($income, $budget, 1);
    $ratio   = $expense / $base;

    if ($ratio <= 0.70) {
        $forecastBadge = '<span class="label label-success" title="Gasto bajo, proyecto saludable">🟢 BAJO (' . round($ratio * 100, 1) . '%)</span>';
    } elseif ($ratio <= 0.95) {
        $forecastBadge = '<span class="label label-warning" title="Gasto moderado, vigilar presupuesto">🟡 MEDIO (' . round($ratio * 100, 1) . '%)</span>';
    } else {
        $forecastBadge = '<span class="label label-danger" title="Gasto alto, riesgo de déficit">🔴 ALTO (' . round($ratio * 100, 1) . '%)</span>';
    }

    $stClass = $statusClass[$row['status']] ?? 'default';
    $stLabel = $statusLabel[$row['status']] ?? htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8');

    $actionBtn = '
    <div class="btn-group">
        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
            Acción <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
            <li><a href="#" onclick="editProject(' . (int)$row['project_id'] . ')"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
            <li><a href="#" onclick="removeProject(' . (int)$row['project_id'] . ')"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
        </ul>
    </div>';

    $output['data'][] = [
        $x++,
        htmlspecialchars($row['project_name'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($row['client_name'] ?? '—', ENT_QUOTES, 'UTF-8'),
        'S/ ' . number_format($budget, 2),
        htmlspecialchars($row['start_date'] ?? '—', ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($row['end_date']   ?? '—', ENT_QUOTES, 'UTF-8'),
        '<span class="label label-' . $stClass . '">' . $stLabel . '</span>',
        'S/ ' . number_format($income,  2),
        'S/ ' . number_format($expense, 2),
        $forecastBadge,
        $actionBtn,
    ];
}
$stmt->close();
$connect->close();
echo json_encode($output);
