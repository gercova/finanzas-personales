<?php
/**
 * php_action/fetchBorrowers.php
 * Returns borrower records for current user formatted for DataTable.
 */
require_once 'core.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['data' => []]);
    exit;
}

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT borrower_id AS id, document_type, document_number, full_name,
           phone, email, address, notes, status, created_at
    FROM borrowers
    WHERE created_by = ? AND is_deleted = 0
    ORDER BY full_name ASC
");
if (!$stmt) {
    echo json_encode(['data' => []]);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$raw  = [];
$no = 1;

while ($row = $result->fetch_assoc()) {
    $raw[] = $row;
    $id = (int)$row['id'];
    $status = (int)$row['status'];

    if ($status === 1) {
        $statusBadge = '<span class="label label-success">Disponible</span>';
    } else {
        $statusBadge = '<span class="label label-danger">No disponible</span>';
    }

    $docBadge = '<span class="label label-info" style="font-size:10px;padding:2px 5px;margin-right:4px;">' . htmlspecialchars($row['document_type']) . '</span> ' . htmlspecialchars($row['document_number']);

    $actions = '
      <div class="btn-group">
        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          Acción <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
          <li><a href="#" class="edit-borrower" data-id="' . $id . '"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
          <li class="divider"></li>
          <li><a href="#" class="remove-borrower" data-id="' . $id . '"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
        </ul>
      </div>';

    $data[] = [
        $no++,
        $docBadge,
        htmlspecialchars($row['full_name']),
        htmlspecialchars($row['phone'] ?: '-'),
        htmlspecialchars($row['address'] ?: '-'),
        $statusBadge,
        $actions
    ];
}
$stmt->close();

echo json_encode(['data' => $data, 'raw' => $raw], JSON_UNESCAPED_UNICODE);
