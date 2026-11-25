<?php
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$brandName    = isset($_POST['editCategoriesName']) ? trim($_POST['editCategoriesName']) : '';
		$brandStatus  = isset($_POST['editCategoriesStatus']) ? (int)$_POST['editCategoriesStatus'] : 0; 
		$categoriesId = isset($_POST['editCategoriesId']) ? (int)$_POST['editCategoriesId'] : 0;

		if(empty($brandName) || empty($brandStatus) || empty($categoriesId)){
			$valid['messages'] = "Todos los campos son requeridos.";
			echo json_encode($valid);
			exit();
		}

		$userId = (int)$_SESSION['userId'];
		$sql = "UPDATE categories SET categories_name = ?, categories_active = ? WHERE categories_id = ? AND (created_by = ? OR created_by IS NULL) AND is_deleted = 0";
		$stmt = $connect->prepare($sql);
		if($stmt){
			$stmt->bind_param("siii", $brandName, $brandStatus, $categoriesId, $userId);
			if($stmt->execute()){
				$valid['success'] = true;
				$valid['messages'] = "Categoría actualizada exitosamente.";	
			}else{
				$valid['messages'] = "Error al actualizar la categoría.";
			}
			$stmt->close();
		}else{
			$valid['messages'] = "Error de base de datos.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>