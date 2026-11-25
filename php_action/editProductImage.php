<?php 	
	require_once 'core.php';

	$valid = array('success' => false, 'messages' => '');

	if($_POST){		
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$productId = isset($_POST['productId']) ? (int)$_POST['productId'] : 0;
		if($productId <= 0){
			$valid['messages'] = "Identificador de producto inválido.";
			echo json_encode($valid);
			exit();
		}

		if(!isset($_FILES['editProductImage']) || $_FILES['editProductImage']['error'] !== UPLOAD_ERR_OK){
			$valid['messages'] = "Se requiere una imagen válida.";
			echo json_encode($valid);
			exit();
		}

		$tmpFile = $_FILES['editProductImage']['tmp_name'];
		$originalName = $_FILES['editProductImage']['name'];
		$fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

		$allowedExts = array('gif', 'jpg', 'jpeg', 'png');
		$allowedMimes = array('image/jpeg', 'image/png', 'image/gif');

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $tmpFile);
		finfo_close($finfo);

		if(!in_array($fileExt, $allowedExts) || !in_array($mimeType, $allowedMimes)){
			$valid['messages'] = "Formato de imagen inválido. Solo se permiten archivos JPG, PNG y GIF.";
			echo json_encode($valid);
			exit();
		}

		$uploadDir = '../assests/images/stock/';
		if(!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
		$newFileName = bin2hex(random_bytes(16)) . '.' . $fileExt;
		$targetPath = $uploadDir . $newFileName;

		$userId = (int)$_SESSION['userId'];
		if(is_writable($uploadDir) && is_uploaded_file($tmpFile) && @move_uploaded_file($tmpFile, $targetPath)){
			$sql = "UPDATE product SET product_image = ? WHERE product_id = ? AND (created_by = ? OR created_by IS NULL) AND is_deleted = 0";
			$stmt = $connect->prepare($sql);
			if($stmt){
				$stmt->bind_param("sii", $targetPath, $productId, $userId);
				if($stmt->execute()){
					$valid['success'] = true;
					$valid['messages'] = "Imagen del producto actualizada exitosamente.";	
				}else{
					$valid['messages'] = "Error al actualizar la imagen en base de datos.";
				}
				$stmt->close();
			}else{
				$valid['messages'] = "Error de base de datos.";
			}
		}else{
			$valid['messages'] = "Error al guardar la imagen en el servidor.";
		}

		$connect->close();
		echo json_encode($valid);
	}
?>