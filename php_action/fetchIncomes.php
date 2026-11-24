<?php
require_once 'core.php';
header('Content-Type: application/json');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT i.id, i.concept, a.account_name, fc.category_name,
           p.project_name, i.income_date, i.amount, i.is_recurring, i.status, i.notes,
           i.account_id, i.category_id, i.project_id
    FROM incomes i
    LEFT JOIN accounts a ON i.account_id = a.id
    LEFT JOIN financial_categories fc ON i.category_id = fc.id
    LEFT JOIN projects p ON i.project_id = p.id
    WHERE i.created_by = ?
    ORDER BY i.income_date DESC
");
if (!$stmt) { echo json_encode(['data'=>[]]); exit; }
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$no = 1;
while ($row = $result->fetch_assoc()) {
    $statusBadge = '';
    switch ($row['status']) {
        case 'ACTIVO':    $statusBadge = '<span class="label label-success">ACTIVO</span>'; break;
        case 'PENDIENTE': $statusBadge = '<span class="label label-warning">PENDIENTE</span>'; break;
        case 'CANCELADO': $statusBadge = '<span class="label label-default">CANCELADO</span>'; break;
        default:          $statusBadge = '<span class="label label-default">'.htmlspecialchars($row['status']).'</span>';
    }
    $recurIcon = $row['is_recurring'] ? ' <i class="glyphicon glyphicon-repeat text-info" title="Recurrente"></i>' : '';
    $actions = '
      <div class="btn-group">
        <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
          <i class="glyphicon glyphicon-cog"></i> <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
          <li><a href="#" class="edit-income" data-id="'.intval($row['id']).'"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
          <li class="divider"></li>
          <li><a href="#" class="remove-income" data-id="'.intval($row['id']).'"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
        </ul>
      </div>';
    $data[] = [
        $no++,
        htmlspecialchars($row['concept']).$recurIcon,
        htmlspecialchars($row['account_name'] ?? '-'),
        htmlspecialchars($row['category_name'] ?? '-'),
        htmlspecialchars($row['project_name'] ?? '-'),
        date('d/m/Y', strtotime($row['income_date'])),
        'S/ '.number_format((float)$row['amount'], 2),
        $statusBadge,
        $actions
    ];
}
$stmt->close();
echo json_encode(['data' => $data]);
