<?php 	
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$orderId = isset($_POST['orderId']) ? (int)$_POST['orderId'] : 0;
		if($orderId > 0){ 
			$sql = "UPDATE orders SET order_status = 2 WHERE order_id = ?";
			$orderItem = "UPDATE order_item SET order_item_status = 2 WHERE order_id = ?";
			
			$stmt1 = $connect->prepare($sql);
			$stmt2 = $connect->prepare($orderItem);

			if($stmt1 && $stmt2){
				$stmt1->bind_param("i", $orderId);
				$stmt2->bind_param("i", $orderId);
				$res1 = $stmt1->execute();
				$res2 = $stmt2->execute();

				if($res1 && $res2) {
					$valid['success'] = true;
					$valid['messages'] = "Orden eliminada exitosamente.";		
				}else{
					$valid['messages'] = "Error al eliminar la orden.";
				}
				$stmt1->close();
				$stmt2->close();
			}else{
				$valid['messages'] = "Error de base de datos.";
			}
		}else{
			$valid['messages'] = "Identificador de orden inválido.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>