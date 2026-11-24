<?php 
	require_once 'core.php';

	if($_POST){
		if (!verifyCsrfToken()) {
			die("Error de validación de seguridad (CSRF inválido). Recargue la página.");
		}

		$startDate = isset($_POST['startDate']) ? trim($_POST['startDate']) : '';
		$endDate   = isset($_POST['endDate']) ? trim($_POST['endDate']) : '';

		$date = DateTime::createFromFormat('m/d/Y', $startDate);
		$start_date = $date ? $date->format("Y-m-d") : '1970-01-01';

		$format = DateTime::createFromFormat('m/d/Y', $endDate);
		$end_date = $format ? $format->format("Y-m-d") : date('Y-m-d');

		$sql = "SELECT order_date, client_name, client_contact, grand_total FROM orders WHERE order_date >= ? AND order_date <= ? AND order_status = 1";
		$stmt = $connect->prepare($sql);

		$table = '
			<table border="1" cellspacing="0" cellpadding="5" style="width:100%; border-collapse: collapse;">
				<tr style="background-color: #f2f2f2;">
					<th>Fecha</th>
					<th>Cliente</th>
					<th>Teléfono</th>
					<th>Total</th>
				</tr>
		';

		$totalAmount = 0.00;

		if($stmt){
			$stmt->bind_param("ss", $start_date, $end_date);
			$stmt->execute();
			$query = $stmt->get_result();

			while ($result = $query->fetch_assoc()){
				$table .= '
					<tr>
						<td><center>'.htmlspecialchars($result['order_date'], ENT_QUOTES, 'UTF-8').'</center></td>
						<td><center>'.htmlspecialchars($result['client_name'], ENT_QUOTES, 'UTF-8').'</center></td>
						<td><center>'.htmlspecialchars($result['client_contact'], ENT_QUOTES, 'UTF-8').'</center></td>
						<td><center>'.number_format((float)$result['grand_total'], 2).'</center></td>
					</tr>
				';	
				$totalAmount += (float)$result['grand_total'];
			}
			$stmt->close();
		}

		$table .= '
				<tr style="font-weight: bold; background-color: #f9f9f9;">
					<td colspan="3"><center>Total</center></td>
					<td><center>'.number_format($totalAmount, 2).'</center></td>
				</tr>
			</table>
		';

		$connect->close();
		echo $table;
	}
?>