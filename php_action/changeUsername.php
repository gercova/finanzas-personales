<?php 
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		// 1. Verify CSRF Token
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		// 2. Prevent IDOR: Always bind to authenticated session userId
		$userId = (int)$_SESSION['userId'];
		$username = isset($_POST['username']) ? trim($_POST['username']) : '';

		if(empty($username)){
			$valid['messages'] = "El nombre de usuario no puede estar vacío.";
			echo json_encode($valid);
			exit();
		}

		// 3. Update Username using Prepared Statement
		$sql = "UPDATE users SET username = ? WHERE user_id = ?";
		$stmt = $connect->prepare($sql);
		if($stmt){
			$stmt->bind_param("si", $username, $userId);
			if($stmt->execute()){
				$_SESSION['username'] = $username;
				$valid['success'] = true;
				$valid['messages'] = "Usuario actualizado exitosamente.";	
			}else{
				$valid['messages'] = "Error no se pudo actualizar el usuario.";
			}
			$stmt->close();
		}else{
			$valid['messages'] = "Error interno de base de datos.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>