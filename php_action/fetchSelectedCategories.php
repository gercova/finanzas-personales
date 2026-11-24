<?php 	
require_once 'core.php';

$row = null;
if(isset($_POST['categoriesId'])) {
	$categoriesId = (int)$_POST['categoriesId'];
	$stmt = $connect->prepare("SELECT categories_id, categories_name, categories_active, categories_status FROM categories WHERE categories_id = ?");
	if($stmt) {
		$stmt->bind_param("i", $categoriesId);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){ 
			$row = $result->fetch_array();
		}
		$stmt->close();
	}
}

$connect->close();
echo json_encode($row);
