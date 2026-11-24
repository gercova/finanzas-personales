<?php
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$productId = isset($_POST['productId']) ? (int)$_POST['productId'] : 0;
		if($productId > 0){
			$sql = "UPDATE product SET active = 2, status = 2 WHERE product_id = ?";
			$stmt = $connect->prepare($sql);
			if($stmt){
				$stmt->bind_param("i", $productId);
				if($stmt->execute()){
					$valid['success'] = true;
					$valid['messages'] = "Producto eliminado exitosamente.";		
				}else{
					$valid['messages'] = "Error al eliminar el producto.";
				}
				$stmt->close();
			}else{
				$valid['messages'] = "Error de base de datos.";
			}
		}else{
			$valid['messages'] = "Identificador de producto inválido.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>