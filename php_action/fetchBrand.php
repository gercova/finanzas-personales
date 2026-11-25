<?php
require_once 'core.php';

$userId = (int)$_SESSION['userId'];
$sql = "SELECT brand_id, brand_name, brand_active, brand_status FROM brands WHERE brand_status = 1 AND is_deleted = 0 AND (created_by = ? OR created_by IS NULL) ORDER BY brand_id DESC";
$stmt = $connect->prepare($sql);
$output = array('data' => array());

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_array()) {
        $brandId = $row[0];
        if ($row[2] == 1) {
            $activeBrands = "<label class='label label-success'>Disponible</label>";
        } else {
            $activeBrands = "<label class='label label-danger'>No disponible</label>";
        }
        $button = '
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Acción <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a type="button" data-toggle="modal" data-target="#editBrandModel" onclick="editBrands('.$brandId.')"> <i class="glyphicon glyphicon-edit"></i> Editar</a></li>
                    <li><a type="button" data-toggle="modal" data-target="#removeMemberModal" onclick="removeBrands('.$brandId.')"> <i class="glyphicon glyphicon-trash"></i> Eliminar</a></li>       
                </ul>
            </div>
        ';
        $output['data'][] = array( 		
            htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8'), 		
            $activeBrands,
            $button
        ); 	
    }
    $stmt->close();
}

$connect->close();
echo json_encode($output);
