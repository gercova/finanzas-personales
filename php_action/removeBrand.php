<?php 	
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$brandId = isset($_POST['brandId']) ? (int)$_POST['brandId'] : 0;
		if($brandId > 0){ 
			$sql = "UPDATE brands SET brand_status = 2, is_deleted = 1, deleted_at = NOW() WHERE brand_id = ?";
			$stmt = $connect->prepare($sql);
			if($stmt){
				$stmt->bind_param("i", $brandId);
				if($stmt->execute()){
					$valid['success'] = true;
					$valid['messages'] = "Fabricante eliminado exitosamente.";		
				}else{
					$valid['messages'] = "Error al eliminar el fabricante.";
				}
				$stmt->close();
			}else{
				$valid['messages'] = "Error de base de datos.";
			}
		}else{
			$valid['messages'] = "Identificador de fabricante inválido.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>