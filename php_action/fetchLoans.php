<?php
require_once 'core.php';
header('Content-Type: application/json');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT l.loan_id AS id, l.borrower_name, l.concept, a.account_name,
           l.loan_date, l.due_date, l.principal_amount,
           l.interest_type, l.interest_value, l.total_receivable,
           l.amount_repaid, l.balance_pending, l.status, l.notes,
           l.account_id
    FROM loans l
    LEFT JOIN accounts a ON l.account_id = a.account_id
    WHERE l.created_by = ? AND l.is_deleted = 0
    ORDER BY l.loan_date DESC
");
if (!$stmt) { echo json_encode(['data'=>[]]); exit; }
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$no = 1;
while ($row = $result->fetch_assoc()) {
    $status = $row['status'];
    $rowStyle = '';
    $badgeHtml = '';
    switch ($status) {
        case 'PENDIENTE':
            $rowStyle  = 'background-color:#ffcccc;';
            $badgeHtml = '<span class="label" style="background:#e74c3c;">PENDIENTE</span>';
            break;
        case 'PARCIAL':
            $rowStyle  = 'background-color:#fff3cd;';
            $badgeHtml = '<span class="label" style="background:#f39c12;">PARCIAL</span>';
            break;
        case 'PAGADO':
            $rowStyle  = 'background-color:#e8d5f5;';
            $badgeHtml = '<span class="label" style="background:#8e44ad;">PAGADO</span>';
            break;
    }

    $interestInfo = $row['interest_type'] === 'NONE' || $row['interest_type'] === 'NINGUNO' ? '-'
        : ($row['interest_type'] === 'PERCENTAGE' || $row['interest_type'] === 'PORCENTAJE' ? number_format((float)$row['interest_value'],1).'%'
            : 'S/ '.number_format((float)$row['interest_value'],2));

    $dueDate = $row['due_date'] ? date('d/m/Y', strtotime($row['due_date'])) : '-';

    $statusActions = '';
    if ($status !== 'PAGADO') {
        $statusActions .= '<li><a href="#" class="change-status-loan" data-id="'.intval($row['id']).'" data-status="PAGADO"><i class="glyphicon glyphicon-ok-sign text-success"></i> Marcar como PAGADO</a></li>';
    }
    if ($status !== 'PENDIENTE') {
        $statusActions .= '<li><a href="#" class="change-status-loan" data-id="'.intval($row['id']).'" data-status="PENDIENTE"><i class="glyphicon glyphicon-time text-danger"></i> Marcar como PENDIENTE</a></li>';
    }
    if ($status !== 'PARCIAL' && (float)$row['balance_pending'] > 0 && (float)$row['amount_repaid'] > 0) {
        $statusActions .= '<li><a href="#" class="change-status-loan" data-id="'.intval($row['id']).'" data-status="PARCIAL"><i class="glyphicon glyphicon-adjust text-warning"></i> Marcar como PARCIAL</a></li>';
    }

    $actions = '
      <div class="btn-group">
        <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
          <i class="glyphicon glyphicon-cog"></i> Acción <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
          <li><a href="#" class="pay-loan" data-id="'.intval($row['id']).'"
               data-borrower="'.htmlspecialchars($row['borrower_name']).'"
               data-pending="'.number_format((float)$row['balance_pending'],2).'">
               <i class="glyphicon glyphicon-usd text-success"></i> Registrar Pago</a></li>
          <li class="divider"></li>
          <li class="dropdown-header">Cambiar Estado</li>
          ' . $statusActions . '
          <li class="divider"></li>
          <li><a href="#" class="edit-loan" data-id="'.intval($row['id']).'"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
          <li class="divider"></li>
          <li><a href="#" class="remove-loan" data-id="'.intval($row['id']).'"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
        </ul>
      </div>';

    $data[] = [
        $no++,
        '<span style="'.htmlspecialchars($rowStyle).'">'.htmlspecialchars($row['borrower_name']).'</span>',
        htmlspecialchars($row['concept']),
        htmlspecialchars($row['account_name'] ?? '-'),
        date('d/m/Y', strtotime($row['loan_date'])),
        $dueDate,
        'S/ '.number_format((float)$row['principal_amount'],2),
        $interestInfo,
        'S/ '.number_format((float)$row['total_receivable'],2),
        'S/ '.number_format((float)$row['amount_repaid'],2),
        'S/ '.number_format((float)$row['balance_pending'],2),
        $badgeHtml,
        $actions
    ];
}
$stmt->close();
echo json_encode(['data' => $data]);
