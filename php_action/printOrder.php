<?php 	
require_once 'core.php';

$orderId = isset($_POST['orderId']) ? (int)$_POST['orderId'] : 0;
$table = '';

if($orderId > 0) {
	$stmt = $connect->prepare("SELECT order_date, client_name, client_contact, sub_total, vat, total_amount, discount, grand_total, paid, due FROM orders WHERE order_id = ?");
	if($stmt) {
		$stmt->bind_param("i", $orderId);
		$stmt->execute();
		$orderResult = $stmt->get_result();
		$orderData = $orderResult->fetch_array();
		$stmt->close();

		if($orderData) {
			$orderDate = $orderData[0];
			$clientName = $orderData[1];
			$clientContact = $orderData[2]; 
			$subTotal = $orderData[3];
			$vat = $orderData[4];
			$totalAmount = $orderData[5]; 
			$discount = $orderData[6];
			$grandTotal = $orderData[7];
			$paid = $orderData[8];
			$due = $orderData[9];

			$itemStmt = $connect->prepare("SELECT order_item.product_id, order_item.rate, order_item.quantity, order_item.total, product.product_name FROM order_item INNER JOIN product ON order_item.product_id = product.product_id WHERE order_item.order_id = ?");
			if($itemStmt) {
				$itemStmt->bind_param("i", $orderId);
				$itemStmt->execute();
				$orderItemResult = $itemStmt->get_result();

				$table = '
					<table border="1" cellspacing="0" cellpadding="20" width="100%">
						<thead>
							<tr>
								<th colspan="5">
									<center>
										Fecha : '.htmlspecialchars((string)$orderDate, ENT_QUOTES, 'UTF-8').'
										<center>Cliente : '.htmlspecialchars((string)$clientName, ENT_QUOTES, 'UTF-8').'</center>
										Teléfono : '.htmlspecialchars((string)$clientContact, ENT_QUOTES, 'UTF-8').'
									</center>		
								</th>				
							</tr>		
						</thead>
					</table>
					<table border="0" width="100%;" cellpadding="5" style="border:1px solid black;border-top-style:1px solid black;border-bottom-style:1px solid black;">
						<tbody>
							<tr>
								<th>#</th>
								<th>Producto</th>
								<th>Precio</th>
								<th>Cantidad</th>
								<th>Total</th>
							</tr>';
							$x = 1;
							while($row = $orderItemResult->fetch_array()){
								$table .= '
									<tr>
										<th>'.$x.'</th>
										<th>'.htmlspecialchars((string)$row[4], ENT_QUOTES, 'UTF-8').'</th>
										<th>'.htmlspecialchars((string)$row[1], ENT_QUOTES, 'UTF-8').'</th>
										<th>'.htmlspecialchars((string)$row[2], ENT_QUOTES, 'UTF-8').'</th>
										<th>'.number_format((float)$row[3],2).'</th>
									</tr>
								';
								$x++;
							}
							$itemStmt->close();

							$table .= '
							<tr>
								<th></th>
							</tr>
							<tr>
								<th></th>
							</tr>
							<tr>
								<th>Sub total</th>
								<th>'.number_format((float)$subTotal,2).'</th>			
							</tr>
							<tr>
								<th>IVA ('.(float)env('TAX_RATE', 13).'%)</th>
								<th>'.number_format((float)$vat,2).'</th>			
							</tr>
							<tr>
								<th>Total neto</th>
								<th>'.number_format((float)$totalAmount,2).'</th>			
							</tr>
							<tr>
								<th>Descuento</th>
								<th>'.number_format((float)$discount,2).'</th>			
							</tr>
							<tr>
								<th>Total</th>
								<th>'.number_format((float)$grandTotal,2).'</th>			
							</tr>
							<tr>
								<th>Pagado</th>
								<th>'.number_format((float)$paid,2).'</th>			
							</tr>
							<tr>
								<th>Pendiente</th>
								<th>'.number_format((float)$due,2).'</th>			
							</tr>
						</tbody>
					</table>
				';
			}
		}
	}
}

$connect->close();
echo $table;
