<?php 	
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$categoriesId = isset($_POST['categoriesId']) ? (int)$_POST['categoriesId'] : 0;
		if($categoriesId > 0){ 
			$sql = "UPDATE categories SET categories_status = 2, is_deleted = 1, deleted_at = NOW() WHERE categories_id = ?";
			$stmt = $connect->prepare($sql);
			if($stmt){
				$stmt->bind_param("i", $categoriesId);
				if($stmt->execute()){
					$valid['success'] = true;
					$valid['messages'] = "Categoría eliminada exitosamente.";		
				}else{
					$valid['messages'] = "Error al eliminar la categoría.";
				}
				$stmt->close();
			}else{
				$valid['messages'] = "Error de base de datos.";
			}
		}else{
			$valid['messages'] = "Identificador de categoría inválido.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>