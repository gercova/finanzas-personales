<?php 	
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$brandName   = isset($_POST['brandName']) ? trim($_POST['brandName']) : '';
		$brandStatus = isset($_POST['brandStatus']) ? (int)$_POST['brandStatus'] : 0; 

		if(empty($brandName) || empty($brandStatus)){
			$valid['messages'] = "Todos los campos son requeridos.";
			echo json_encode($valid);
			exit();
		}

		$userId = (int)$_SESSION['userId'];
		$sql = "INSERT INTO brands (brand_name, brand_active, brand_status, created_by) VALUES (?, ?, 1, ?)";
		$stmt = $connect->prepare($sql);
		if($stmt){
			$stmt->bind_param("sii", $brandName, $brandStatus, $userId);
			if($stmt->execute()){
				$valid['success'] = true;
				$valid['messages'] = "Fabricante agregado exitosamente.";	
			}else{
				$valid['messages'] = "Error al guardar el fabricante.";
			}
			$stmt->close();
		}else{
			$valid['messages'] = "Error de base de datos.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>