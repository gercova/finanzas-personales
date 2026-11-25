<?php
require_once 'core.php';

$userId = (int)$_SESSION['userId'];
$sql = "SELECT order_id, order_date, client_name, client_contact, payment_status 
        FROM orders 
        WHERE order_status = 1 AND is_deleted = 0 AND (created_by = ? OR created_by IS NULL)
        ORDER BY order_id DESC";

$stmt = $connect->prepare($sql);
$output = array('data' => array());

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $x = 1;
    while ($row = $result->fetch_array()) {
        $orderId = $row[0];
        $countOrderItemSql = "SELECT count(*) FROM order_item WHERE order_id = ?";
        $stmtCount = $connect->prepare($countOrderItemSql);
        $itemCount = 0;
        if ($stmtCount) {
            $stmtCount->bind_param("i", $orderId);
            $stmtCount->execute();
            $stmtCount->bind_result($itemCount);
            $stmtCount->fetch();
            $stmtCount->close();
        }

        if ($row[4] == 1) {
            $paymentStatus = "<label class='label label-success'>Pago completo</label>";
        } elseif ($row[4] == 2) { 		
            $paymentStatus = "<label class='label label-info'>Pago por adelantado</label>";
        } else {
            $paymentStatus = "<label class='label label-warning'>No pagado</label>";
        }

        $button = '
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Acción <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="orders.php?o=editOrd&i='.$orderId.'" id="editOrderModalBtn"> <i class="glyphicon glyphicon-edit"></i> Editar</a></li>
                    <li><a type="button" data-toggle="modal" id="paymentOrderModalBtn" data-target="#paymentOrderModal" onclick="paymentOrder('.$orderId.')"> <i class="glyphicon glyphicon-save"></i> Pago</a></li>
                    <li><a type="button" onclick="printOrder('.$orderId.')"> <i class="glyphicon glyphicon-print"></i> Imprimir </a></li>
                    <li><a type="button" data-toggle="modal" data-target="#removeOrderModal" id="removeOrderModalBtn" onclick="removeOrder('.$orderId.')"> <i class="glyphicon glyphicon-trash"></i> Eliminar</a></li>       
                </ul>
            </div>
        ';

        $output['data'][] = array( 		
            $x,
            $row[1],
            htmlspecialchars($row[2], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($row[3], ENT_QUOTES, 'UTF-8'), 		 	
            $itemCount,
            $paymentStatus,
            $button 		
        ); 	
        $x++;
    }
    $stmt->close();
}

$connect->close();
echo json_encode($output);
