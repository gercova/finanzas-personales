<?php
require_once 'core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT account_id, account_name, account_type, account_number, currency,
           initial_balance, current_balance, color, status
    FROM accounts
    WHERE created_by = ? AND is_deleted = 0
    ORDER BY current_balance DESC, account_name ASC
");
if (!$stmt) {
    echo json_encode(['data' => []]);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$typeLabels = [
    'BANK'           => '<span class="label label-primary">Banco</span>',
    'DIGITAL_WALLET' => '<span class="label label-info">Billetera Digital</span>',
    'CASH'           => '<span class="label label-success">Efectivo</span>',
    'SAVINGS'        => '<span class="label label-warning">Ahorros</span>',
    'OTHER'          => '<span class="label label-default">Otro</span>',
];

$data = [];
$no   = 1;
while ($row = $result->fetch_assoc()) {
    $accId      = (int)$row['account_id'];
    $typeHtml   = $typeLabels[$row['account_type']] ?? htmlspecialchars($row['account_type'], ENT_QUOTES, 'UTF-8');
    $statusHtml = (int)$row['status'] === 1
        ? '<span class="label label-success">Activa</span>'
        : '<span class="label label-default">Inactiva</span>';

    $colorDot = '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' . htmlspecialchars($row['color'] ?: '#2e6da4', ENT_QUOTES, 'UTF-8') . ';margin-right:6px;"></span>';

    $actions = '
      <div class="btn-group">
        <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
          <i class="glyphicon glyphicon-cog"></i> Acción <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
          <li><a href="#" class="edit-account" data-id="' . $accId . '"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
          <li class="divider"></li>
          <li><a href="#" class="remove-account" data-id="' . $accId . '"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
        </ul>
      </div>';

    $bal      = (float)$row['current_balance'];
    $balClass = $bal >= 0 ? 'text-success' : 'text-danger';

    $data[] = [
        $no++,
        $colorDot . '<strong>' . htmlspecialchars($row['account_name'], ENT_QUOTES, 'UTF-8') . '</strong>',
        $typeHtml,
        htmlspecialchars($row['account_number'] ?: '—', ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($row['currency'] ?: 'PEN', ENT_QUOTES, 'UTF-8'),
        'S/ ' . number_format((float)$row['initial_balance'], 2),
        '<strong class="' . $balClass . '">S/ ' . number_format($bal, 2) . '</strong>',
        $statusHtml,
        $actions
    ];
}
$stmt->close();
echo json_encode(['data' => $data]);
