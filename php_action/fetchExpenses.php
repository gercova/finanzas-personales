<?php
require_once 'core.php';
header('Content-Type: application/json');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT e.expense_id AS id, e.concept, a.account_name, fc.category_name,
           e.expense_date, e.amount, e.receipt_image, e.status, e.notes,
           e.account_id, e.category_id, e.project_id
    FROM expenses e
    LEFT JOIN accounts a ON e.account_id = a.account_id
    LEFT JOIN financial_categories fc ON e.category_id = fc.category_id
    WHERE e.created_by = ? AND e.is_deleted = 0
    ORDER BY e.expense_date DESC
");
if (!$stmt) { echo json_encode(['data'=>[]]); exit; }
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$no = 1;
while ($row = $result->fetch_assoc()) {
    $statusBadge = $row['status'] === 'PAGADO'
        ? '<span class="label label-success">PAGADO</span>'
        : '<span class="label label-warning">PENDIENTE</span>';

    $imgHtml = '-';
    if (!empty($row['receipt_image'])) {
        $imgUrl = htmlspecialchars($row['receipt_image']);
        $imgHtml = '<a href="' . $imgUrl . '" target="_blank"><img src="' . $imgUrl . '" style="height:36px;border-radius:3px;border:1px solid #ddd;"></a>';
    }

    $actions = '
      <div class="btn-group">
        <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
          <i class="glyphicon glyphicon-cog"></i> <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
          <li><a href="#" class="edit-expense" data-id="'.intval($row['id']).'"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
          <li class="divider"></li>
          <li><a href="#" class="remove-expense" data-id="'.intval($row['id']).'"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
        </ul>
      </div>';

    $data[] = [
        $no++,
        htmlspecialchars($row['concept']),
        htmlspecialchars($row['account_name'] ?? '-'),
        htmlspecialchars($row['category_name'] ?? '-'),
        date('d/m/Y', strtotime($row['expense_date'])),
        'S/ '.number_format((float)$row['amount'], 2),
        $imgHtml,
        $statusBadge,
        $actions
    ];
}
$stmt->close();
echo json_encode(['data' => $data]);
