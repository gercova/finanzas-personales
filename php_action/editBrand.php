<?php
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$brandName   = isset($_POST['editBrandName']) ? trim($_POST['editBrandName']) : '';
		$brandStatus = isset($_POST['editBrandStatus']) ? (int)$_POST['editBrandStatus'] : 0; 
		$brandId     = isset($_POST['brandId']) ? (int)$_POST['brandId'] : 0;

		if(empty($brandName) || empty($brandStatus) || empty($brandId)){
			$valid['messages'] = "Todos los campos son requeridos.";
			echo json_encode($valid);
			exit();
		}

		$sql = "UPDATE brands SET brand_name = ?, brand_active = ? WHERE brand_id = ?";
		$stmt = $connect->prepare($sql);
		if($stmt){
			$stmt->bind_param("sii", $brandName, $brandStatus, $brandId);
			if($stmt->execute()){
				$valid['success'] = true;
				$valid['messages'] = "Fabricante actualizado exitosamente.";	
			}else{
				$valid['messages'] = "Error al actualizar el fabricante.";
			}
			$stmt->close();
		}else{
			$valid['messages'] = "Error de base de datos.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>