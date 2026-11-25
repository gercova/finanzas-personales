<?php 	
	require_once 'core.php';
	header('Content-Type: application/json; charset=utf-8');

	$valid = array('success' => false, 'messages' => '');

	if($_POST){
		if (!verifyCsrfToken()) {
			$valid['messages'] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
			echo json_encode($valid);
			exit();
		}

		$productName   = isset($_POST['productName']) ? trim($_POST['productName']) : '';
		$quantity      = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
		$rate          = isset($_POST['rate']) ? (float)$_POST['rate'] : 0.00;
		$brandName     = isset($_POST['brandName']) ? (int)$_POST['brandName'] : 0;
		$categoryName  = isset($_POST['categoryName']) ? (int)$_POST['categoryName'] : 0;
		$productStatus = isset($_POST['productStatus']) ? (int)$_POST['productStatus'] : 0;

		if(empty($productName) || empty($brandName) || empty($categoryName) || empty($productStatus)){
			$valid['messages'] = "Todos los campos del producto son requeridos.";
			echo json_encode($valid);
			exit();
		}

		$targetPath = '../assests/images/photo_default.png';

		if(isset($_FILES['productImage']) && $_FILES['productImage']['error'] !== UPLOAD_ERR_NO_FILE){
			if($_FILES['productImage']['error'] !== UPLOAD_ERR_OK){
				$valid['messages'] = "Error al recibir la imagen del producto.";
				echo json_encode($valid);
				exit();
			}

			$tmpFile = $_FILES['productImage']['tmp_name'];
			$originalName = $_FILES['productImage']['name'];
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
			$uploadTarget = $uploadDir . $newFileName;

			if(!is_writable($uploadDir) || !is_uploaded_file($tmpFile) || !@move_uploaded_file($tmpFile, $uploadTarget)){
				$valid['messages'] = "No se pudo guardar la imagen en el servidor (verifique permisos en assests/images/stock).";
				echo json_encode($valid);
				exit();
			}
			$targetPath = $uploadTarget;
		}

		$userId = (int)$_SESSION['userId'];
		$sql = "INSERT INTO product (product_name, product_image, brand_id, categories_id, quantity, rate, active, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)";
		$stmt = $connect->prepare($sql);
		if($stmt){
			$stmt->bind_param("ssiiddii", $productName, $targetPath, $brandName, $categoryName, $quantity, $rate, $productStatus, $userId);
			if($stmt->execute()){
				$valid['success'] = true;
				$valid['messages'] = "Producto creado exitosamente.";	
			}else{
				$valid['messages'] = "Error al registrar el producto.";
			}
			$stmt->close();
		}else{
			$valid['messages'] = "Error de base de datos.";
		}

		$connect->close();
		echo json_encode($valid);
	}
