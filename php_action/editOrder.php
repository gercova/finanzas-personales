<?php
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$orderId          = isset($_POST['orderId']) ? (int)$_POST['orderId'] : 0;
		$orderDateRaw     = isset($_POST['orderDate']) ? $_POST['orderDate'] : '';
		$orderDate        = !empty($orderDateRaw) ? date('Y-m-d', strtotime($orderDateRaw)) : date('Y-m-d');
  		$clientName 	  = isset($_POST['clientName']) ? trim($_POST['clientName']) : '';
  		$clientContact 	  = isset($_POST['clientContact']) ? trim($_POST['clientContact']) : '';
  		$subTotalValue    = isset($_POST['subTotalValue']) ? (float)$_POST['subTotalValue'] : 0.00;
  		$vatValue 		  = isset($_POST['vatValue']) ? (float)$_POST['vatValue'] : 0.00;
  		$totalAmountValue = isset($_POST['totalAmountValue']) ? (float)$_POST['totalAmountValue'] : 0.00;
  		$discount 		  = isset($_POST['discount']) ? (float)$_POST['discount'] : 0.00;
  		$grandTotalValue  = isset($_POST['grandTotalValue']) ? (float)$_POST['grandTotalValue'] : 0.00;
  		$paid 			  = isset($_POST['paid']) ? (float)$_POST['paid'] : 0.00;
  		$dueValue 		  = isset($_POST['dueValue']) ? (float)$_POST['dueValue'] : 0.00;
  		$paymentType 	  = isset($_POST['paymentType']) ? (int)$_POST['paymentType'] : 0;
  		$paymentStatus 	  = isset($_POST['paymentStatus']) ? (int)$_POST['paymentStatus'] : 0;		

		$sql = "UPDATE orders SET order_date = ?, client_name = ?, client_contact = ?, sub_total = ?, vat = ?, total_amount = ?, discount = ?, grand_total = ?, paid = ?, due = ?, payment_type = ?, payment_status = ?, order_status = 1 WHERE order_id = ?";	
		$stmt = $connect->prepare($sql);
		if($stmt){
			$stmt->bind_param("sssddddddiiii", $orderDate, $clientName, $clientContact, $subTotalValue, $vatValue, $totalAmountValue, $discount, $grandTotalValue, $paid, $dueValue, $paymentType, $paymentStatus, $orderId);
			$stmt->execute();
			$stmt->close();
		}

		// Restore original stock from existing order items before applying changes
		$restoreItemSql = "SELECT product_id, quantity FROM order_item WHERE order_id = ?";
		$rStmt = $connect->prepare($restoreItemSql);
		if($rStmt){
			$rStmt->bind_param("i", $orderId);
			$rStmt->execute();
			$res = $rStmt->get_result();
			while($item = $res->fetch_assoc()){
				$restQty = (int)$item['quantity'];
				$restProdId = (int)$item['product_id'];
				$connect->query("UPDATE product SET quantity = quantity + {$restQty} WHERE product_id = {$restProdId}");
			}
			$rStmt->close();
		}

		// Delete existing order items
		$delStmt = $connect->prepare("DELETE FROM order_item WHERE order_id = ?");
		if($delStmt){
			$delStmt->bind_param("i", $orderId);
			$delStmt->execute();
			$delStmt->close();
		}

		// Re-insert updated order items and adjust stock
		if(isset($_POST['productName']) && is_array($_POST['productName'])){
			for($x = 0; $x < count($_POST['productName']); $x++) {		
				$prodId = (int)$_POST['productName'][$x];
				$qty    = (int)$_POST['quantity'][$x];
				$rate   = (float)$_POST['rateValue'][$x];
				$tot    = (float)$_POST['totalValue'][$x];

				if($prodId > 0 && $qty > 0){
					// Deduct newly specified quantity
					$updStmt = $connect->prepare("UPDATE product SET quantity = quantity - ? WHERE product_id = ?");
					if($updStmt){
						$updStmt->bind_param("ii", $qty, $prodId);
						$updStmt->execute();
						$updStmt->close();
					}

					// Insert order item
					$insStmt = $connect->prepare("INSERT INTO order_item (order_id, product_id, quantity, rate, total, order_item_status) VALUES (?, ?, ?, ?, ?, 1)");
					if($insStmt){
						$insStmt->bind_param("iiidd", $orderId, $prodId, $qty, $rate, $tot);
						$insStmt->execute();
						$insStmt->close();
					}
				}
			}
		}

		$valid['success'] = true;
		$valid['messages'] = "Orden actualizada exitosamente.";
		$connect->close();
		echo json_encode($valid);
	}
?>