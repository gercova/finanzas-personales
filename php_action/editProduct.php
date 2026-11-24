<?php 	
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$productId     = isset($_POST['productId']) ? (int)$_POST['productId'] : 0;
		$productName   = isset($_POST['editProductName']) ? trim($_POST['editProductName']) : ''; 
		$quantity      = isset($_POST['editQuantity']) ? (int)$_POST['editQuantity'] : 0;
		$rate          = isset($_POST['editRate']) ? (float)$_POST['editRate'] : 0.00;
		$brandName     = isset($_POST['editBrandName']) ? (int)$_POST['editBrandName'] : 0;
		$categoryName  = isset($_POST['editCategoryName']) ? (int)$_POST['editCategoryName'] : 0;
		$productStatus = isset($_POST['editProductStatus']) ? (int)$_POST['editProductStatus'] : 0;		

		if(empty($productId) || empty($productName) || empty($brandName) || empty($categoryName) || empty($productStatus)){
			$valid['messages'] = "Todos los campos son requeridos.";
			echo json_encode($valid);
			exit();
		}

		$sql = "UPDATE product SET product_name = ?, brand_id = ?, categories_id = ?, quantity = ?, rate = ?, active = ?, status = 1 WHERE product_id = ?";
		$stmt = $connect->prepare($sql);
		if($stmt){
			$stmt->bind_param("siiddii", $productName, $brandName, $categoryName, $quantity, $rate, $productStatus, $productId);
			if($stmt->execute()){
				$valid['success'] = true;
				$valid['messages'] = "Producto actualizado exitosamente.";	
			}else{
				$valid['messages'] = "Error al actualizar el producto.";
			}
			$stmt->close();
		}else{
			$valid['messages'] = "Error de base de datos.";
		}
	}

	$connect->close();
	echo json_encode($valid);
?>