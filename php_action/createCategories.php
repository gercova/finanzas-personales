<?php
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$categoriesName   = isset($_POST['categoriesName']) ? trim($_POST['categoriesName']) : '';
		$categoriesStatus = isset($_POST['categoriesStatus']) ? (int)$_POST['categoriesStatus'] : 0;

		if(empty($categoriesName) || empty($categoriesStatus)){
			$valid['messages'] = "Todos los campos son requeridos.";
			echo json_encode($valid);
			exit();
		}

		$sql = "INSERT INTO categories (categories_name, categories_active, categories_status) VALUES (?, ?, 1)";
		$stmt = $connect->prepare($sql);
		if($stmt){
			$stmt->bind_param("si", $categoriesName, $categoriesStatus);
			if($stmt->execute()){
				$valid['success'] = true;
				$valid['messages'] = "Categoría creada exitosamente.";	
			}else{
				$valid['messages'] = "Error al guardar la categoría.";
			}
			$stmt->close();
		}else{
			$valid['messages'] = "Error de base de datos.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>