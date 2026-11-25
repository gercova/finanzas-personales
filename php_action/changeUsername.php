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

		if(empty($username) || strlen($username) < 3){
			$valid['messages'] = "El nombre de usuario debe tener al menos 3 caracteres.";
			echo json_encode($valid);
			exit();
		}

		// Prevent username collision with another user
		$chk = $connect->prepare("SELECT user_id FROM users WHERE username = ? AND user_id <> ? LIMIT 1");
		if ($chk) {
			$chk->bind_param("si", $username, $userId);
			$chk->execute();
			$chkRes = $chk->get_result();
			if ($chkRes && $chkRes->num_rows > 0) {
				$chk->close();
				$valid['messages'] = "Ese nombre de usuario ya está en uso por otro usuario.";
				echo json_encode($valid);
				exit();
			}
			$chk->close();
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