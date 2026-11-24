<?php
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => array(), 'order_id' => '');

	if($_POST){	
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$orderDateRaw = isset($_POST['orderDate']) ? $_POST['orderDate'] : '';
		$orderDate    = !empty($orderDateRaw) ? date('Y-m-d', strtotime($orderDateRaw)) : date('Y-m-d');
  		$clientName 	  = isset($_POST['clientName']) ? trim($_POST['clientName']) : '';
  		$clientContact 	  = isset($_POST['clientContact']) ? trim($_POST['clientContact']) : '';
  		$subTotalValue    = isset($_POST['subTotalValue']) ? (float)$_POST['subTotalValue'] : 0.00;
  		$vatValue		  = isset($_POST['vatValue']) ? (float)$_POST['vatValue'] : 0.00;
  		$totalAmountValue = isset($_POST['totalAmountValue']) ? (float)$_POST['totalAmountValue'] : 0.00;
  		$discount 		  = isset($_POST['discount']) ? (float)$_POST['discount'] : 0.00;
  		$grandTotalValue  = isset($_POST['grandTotalValue']) ? (float)$_POST['grandTotalValue'] : 0.00;
  		$paid 			  = isset($_POST['paid']) ? (float)$_POST['paid'] : 0.00;
  		$dueValue 		  = isset($_POST['dueValue']) ? (float)$_POST['dueValue'] : 0.00;
  		$paymentType 	  = isset($_POST['paymentType']) ? (int)$_POST['paymentType'] : 0;
  		$paymentStatus 	  = isset($_POST['paymentStatus']) ? (int)$_POST['paymentStatus'] : 0;		

		$sql = "INSERT INTO orders (order_date, client_name, client_contact, sub_total, vat, total_amount, discount, grand_total, paid, due, payment_type, payment_status, order_status) 
		        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
		$stmt = $connect->prepare($sql);
		if($stmt){
			$stmt->bind_param("sssddddddiii", $orderDate, $clientName, $clientContact, $subTotalValue, $vatValue, $totalAmountValue, $discount, $grandTotalValue, $paid, $dueValue, $paymentType, $paymentStatus);
			if($stmt->execute()){
				$order_id = $connect->insert_id;
				$valid['order_id'] = $order_id;	
				$stmt->close();

				if(isset($_POST['productName']) && is_array($_POST['productName'])){
					for($x = 0; $x < count($_POST['productName']); $x++){
						$prodId = (int)$_POST['productName'][$x];
						$qty    = (int)$_POST['quantity'][$x];
						$rate   = (float)$_POST['rateValue'][$x];
						$tot    = (float)$_POST['totalValue'][$x];

						if($prodId > 0 && $qty > 0){
							// Deduct stock safely with prepared statement
							$updateProductTable = "UPDATE product SET quantity = quantity - ? WHERE product_id = ?";
							$pStmt = $connect->prepare($updateProductTable);
							if($pStmt){
								$pStmt->bind_param("ii", $qty, $prodId);
								$pStmt->execute();
								$pStmt->close();
							}

							// Insert into order_item
							$orderItemSql = "INSERT INTO order_item (order_id, product_id, quantity, rate, total, order_item_status) VALUES (?, ?, ?, ?, ?, 1)";
							$itemStmt = $connect->prepare($orderItemSql);
							if($itemStmt){
								$itemStmt->bind_param("iiidd", $order_id, $prodId, $qty, $rate, $tot);
								$itemStmt->execute();
								$itemStmt->close();
							}
						}
					}
				}

				$valid['success'] = true;
				$valid['messages'] = "Orden creada exitosamente.";
			} else {
				$valid['messages'] = "Error al crear la orden.";
				$stmt->close();
			}
		} else {
			$valid['messages'] = "Error de base de datos.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>