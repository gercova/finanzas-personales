<?php
require_once 'core.php';

$userId = (int)$_SESSION['userId'];
$sql = "SELECT product.product_id, product.product_name, product.product_image, product.brand_id, product.categories_id, product.quantity, product.rate, product.active, product.status, brands.brand_name, categories.categories_name 
        FROM product 
        INNER JOIN brands ON product.brand_id = brands.brand_id 
        INNER JOIN categories ON product.categories_id = categories.categories_id 
        WHERE product.status = 1 AND product.is_deleted = 0 AND (product.created_by = ? OR product.created_by IS NULL)
        ORDER BY product.product_id DESC";

$stmt = $connect->prepare($sql);
$output = array('data' => array());

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_array()) {
        $productId = $row[0];
        if ($row[7] == 1) {
            $active = "<label class='label label-success'>Disponible</label>";
        } else {
            $active = "<label class='label label-danger'>No disponible</label>";
        }

        $button = '
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Acción <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a type="button" data-toggle="modal" id="editProductModalBtn" data-target="#editProductModal" onclick="editProduct('.$productId.')"> <i class="glyphicon glyphicon-edit"></i> Editar</a></li>
                    <li><a type="button" data-toggle="modal" data-target="#removeProductModal" id="removeProductModalBtn" onclick="removeProduct('.$productId.')"> <i class="glyphicon glyphicon-trash"></i> Eliminar</a></li>       
                </ul>
            </div>
        ';

        $imageUrl = substr($row[2], 3);
        $productImage = "<img class='img-round' src='".$imageUrl."' style='height:30px; width:50px;'  />";

        $output['data'][] = array( 		
            $productImage,
            htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8'), 
            $row[6],
            $row[5], 		 	
            htmlspecialchars($row[9], ENT_QUOTES, 'UTF-8'), 		 	
            htmlspecialchars($row[10], ENT_QUOTES, 'UTF-8'), 	
            $active,
            $button 		
        ); 	
    }
    $stmt->close();
}

$connect->close();
echo json_encode($output);
