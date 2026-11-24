<?php 	
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$orderId 		  = isset($_POST['orderId']) ? (int)$_POST['orderId'] : 0;
		$payAmount 		  = isset($_POST['payAmount']) ? (float)$_POST['payAmount'] : 0.00; 
  		$paymentType 	  = isset($_POST['paymentType']) ? (int)$_POST['paymentType'] : 0;
  		$paymentStatus 	  = isset($_POST['paymentStatus']) ? (int)$_POST['paymentStatus'] : 0;  
  		$paidAmount       = isset($_POST['paidAmount']) ? (float)$_POST['paidAmount'] : 0.00;
  		$grandTotal       = isset($_POST['grandTotal']) ? (float)$_POST['grandTotal'] : 0.00;

  		$updatePaidAmount = $payAmount + $paidAmount;
  		$updateDue        = $grandTotal - $updatePaidAmount;

		$sql = "UPDATE orders SET paid = ?, due = ?, payment_type = ?, payment_status = ? WHERE order_id = ?";
		$stmt = $connect->prepare($sql);
		if($stmt){
			$stmt->bind_param("ddiii", $updatePaidAmount, $updateDue, $paymentType, $paymentStatus, $orderId);
			if($stmt->execute()){
				$valid['success'] = true;
				$valid['messages'] = "Pago actualizado exitosamente.";	
			}else{
				$valid['messages'] = "Error al actualizar el pago.";
			}
			$stmt->close();
		}else{
			$valid['messages'] = "Error de base de datos.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>