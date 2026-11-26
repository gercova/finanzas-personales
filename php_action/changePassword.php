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

		$currentPassword = isset($_POST['password']) ? trim($_POST['password']) : '';
		$newPassword     = isset($_POST['npassword']) ? trim($_POST['npassword']) : '';
		$conformPassword = isset($_POST['cpassword']) ? trim($_POST['cpassword']) : '';

		if(empty($currentPassword) || empty($newPassword) || empty($conformPassword)){
			$valid['messages'] = "Todos los campos de contraseña son requeridos.";
			echo json_encode($valid);
			exit();
		}

		if(strlen($newPassword) < 8){
			$valid['messages'] = "La nueva contraseña debe tener al menos 8 caracteres.";
			echo json_encode($valid);
			exit();
		}

		if($newPassword !== $conformPassword){
			$valid['messages'] = "La nueva contraseña no coincide con la confirmación.";
			echo json_encode($valid);
			exit();
		}

		// 3. Verify Current Password using Prepared Statement
		$sql = "SELECT password FROM users WHERE user_id = ?";
		$stmt = $connect->prepare($sql);
		if($stmt){
			$stmt->bind_param("i", $userId);
			$stmt->execute();
			$result = $stmt->get_result();
			$user = $result->fetch_assoc();
			$stmt->close();

			if($user && verifyPassword($currentPassword, $user['password'])){
				// 4. Encrypt using SHA-256
				$newHash = hashPassword($newPassword);
				$updateSql = "UPDATE users SET password = ? WHERE user_id = ?";
				$updateStmt = $connect->prepare($updateSql);
				if($updateStmt){
					$updateStmt->bind_param("si", $newHash, $userId);
					if($updateStmt->execute()){
						$valid['success'] = true;
						$valid['messages'] = "Contraseña actualizada exitosamente con cifrado SHA-256.";
					}else{
						$valid['messages'] = "No se pudo actualizar la contraseña.";
					}
					$updateStmt->close();
				}else{
					$valid['messages'] = "Error interno de base de datos.";
				}
			}else{
				$valid['messages'] = "La contraseña actual es incorrecta.";
			}
		}else{
			$valid['messages'] = "Error interno de base de datos.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>