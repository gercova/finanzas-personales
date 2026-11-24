<?php
require_once 'core.php';

$userId = (int)$_SESSION['userId'];
$stmt = $connect->prepare("
    SELECT asset_id, asset_name, category, estimated_value, purchase_price, purchase_date,
           document_number, location_or_custody, status, image_path, notes
    FROM assets
    WHERE created_by = ? AND is_deleted = 0
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$categoryLabels = [
    'VEHICULO'            => '🚗 Vehículo',
    'INMUEBLE'            => '🏠 Inmueble',
    'DOCUMENTO_FIDUCIARIO'=> '📄 Doc. Fiduciario',
    'EQUIPO_TECNOLOGICO'  => '💻 Equipo Tecno.',
    'OTRO'                => '📦 Otro',
];
$statusClass = [
    'ACTIVO'      => 'success',
    'EN_GARANTIA' => 'warning',
    'VENDIDO'     => 'info',
    'DADO_DE_BAJA'=> 'danger',
];

$output = ['data' => []];
$x = 1;
while ($row = $result->fetch_assoc()) {
    $catLabel = $categoryLabels[$row['category']] ?? htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8');
    $stClass  = $statusClass[$row['status']] ?? 'default';
    $stLabel  = htmlspecialchars(str_replace('_', ' ', $row['status']), ENT_QUOTES, 'UTF-8');

    $imgHtml = '—';
    if (!empty($row['image_path']) && file_exists('../' . $row['image_path'])) {
        $safePath = htmlspecialchars($row['image_path'], ENT_QUOTES, 'UTF-8');
        $imgHtml = '<img src="../' . $safePath . '" alt="Imagen" style="height:40px;border-radius:4px;cursor:pointer;" onclick="window.open(\'../' . $safePath . '\',\'_blank\')">';
    }

    $actionBtn = '
    <div class="btn-group">
        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
            Acción <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
            <li><a href="#" onclick="editAsset(' . (int)$row['asset_id'] . ')"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
            <li><a href="#" onclick="removeAsset(' . (int)$row['asset_id'] . ')"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
        </ul>
    </div>';

    $output['data'][] = [
        $x++,
        htmlspecialchars($row['asset_name'], ENT_QUOTES, 'UTF-8'),
        $catLabel,
        'S/ ' . number_format((float)$row['estimated_value'], 2),
        $row['purchase_price'] ? 'S/ ' . number_format((float)$row['purchase_price'], 2) : '—',
        $row['purchase_date'] ? htmlspecialchars($row['purchase_date'], ENT_QUOTES, 'UTF-8') : '—',
        htmlspecialchars($row['document_number'] ?? '—', ENT_QUOTES, 'UTF-8'),
        '<span class="label label-' . $stClass . '">' . $stLabel . '</span>',
        $imgHtml,
        $actionBtn,
    ];
}
$stmt->close();
$connect->close();
header('Content-Type: application/json');
echo json_encode($output);
