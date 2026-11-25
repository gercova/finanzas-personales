<?php
require_once 'core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT category_id, category_name, category_type, color, icon, status
    FROM financial_categories
    WHERE created_by = ? AND is_deleted = 0
    ORDER BY category_type ASC, category_name ASC
");
if (!$stmt) {
    echo json_encode(['data' => []]);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$no   = 1;
while ($row = $result->fetch_assoc()) {
    $catId      = (int)$row['category_id'];
    $typeHtml   = $row['category_type'] === 'INCOME'
        ? '<span class="label label-success">Ingreso</span>'
        : '<span class="label label-danger">Gasto</span>';
    $statusHtml = (int)$row['status'] === 1
        ? '<span class="label label-success">Activa</span>'
        : '<span class="label label-default">Inactiva</span>';
    $colorDot   = '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' . htmlspecialchars($row['color'] ?: '#5bc0de', ENT_QUOTES, 'UTF-8') . ';margin-right:6px;"></span>';

    $actions = '
      <div class="btn-group">
        <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
          <i class="glyphicon glyphicon-cog"></i> Acción <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
          <li><a href="#" class="edit-fin-category" data-id="' . $catId . '"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
          <li class="divider"></li>
          <li><a href="#" class="remove-fin-category" data-id="' . $catId . '"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
        </ul>
      </div>';

    $data[] = [
        $no++,
        $colorDot . '<strong>' . htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8') . '</strong>',
        $typeHtml,
        $statusHtml,
        $actions
    ];
}
$stmt->close();
echo json_encode(['data' => $data]);
