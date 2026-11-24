<?php 
	require_once 'php_action/db_connect.php';
	require_once 'php_action/security.php';

	initSecureSession();

	if(isset($_SESSION['userId'])){
		header('location: dashboard.php');	
		exit();
	}

	$errors = array();

	// Session timeout or security notices
	if (isset($_GET['error'])) {
		if ($_GET['error'] === 'timeout') {
			$errors[] = "Su sesión ha expirado por inactividad. Por favor ingrese nuevamente.";
		} elseif ($_GET['error'] === 'hijack') {
			$errors[] = "Su sesión se cerró por motivos de seguridad.";
		}
	}

	if($_POST) {		
		$username = isset($_POST['username']) ? trim($_POST['username']) : '';
		$password = isset($_POST['password']) ? trim($_POST['password']) : '';

		// 1. Verify CSRF Token
		if (!verifyCsrfToken()) {
			$errors[] = "Error de validación de seguridad (CSRF inválido). Por favor recargue la página.";
		} elseif(empty($username) || empty($password)) {
			if($username == ""){
				$errors[] = "Se requiere nombre de usuario";
			} 
			if($password == ""){
				$errors[] = "Se requiere contraseña";
			}
		} else {
			// 2. Check Rate Limiter
			$rateLimit = checkRateLimit($connect, $username);
			if (!$rateLimit['allowed']) {
				$minutes = ceil($rateLimit['remaining_seconds'] / 60);
				$errors[] = "Demasiados intentos fallidos. Su acceso está bloqueado temporalmente. Intente nuevamente en {$minutes} minuto(s).";
			} else {
				// 3. User Authentication using Prepared Statement & SHA-256
				$sql = "SELECT user_id, username, password FROM users WHERE username = ?";
				$stmt = $connect->prepare($sql);
				if ($stmt) {
					$stmt->bind_param("s", $username);
					$stmt->execute();
					$result = $stmt->get_result();

					if($result && $result->num_rows === 1) {
						$user = $result->fetch_assoc();
						$passwordCheck = verifyPassword($password, $user['password']);

						if($passwordCheck === true || $passwordCheck === 'rehash_needed') {
							// If legacy hash was verified, upgrade hash to SHA-256 seamlessly
							if ($passwordCheck === 'rehash_needed') {
								$newSha256 = hashPassword($password);
								$upgradeSql = "UPDATE users SET password = ? WHERE user_id = ?";
								$upgradeStmt = $connect->prepare($upgradeSql);
								if ($upgradeStmt) {
									$upgradeStmt->bind_param("si", $newSha256, $user['user_id']);
									$upgradeStmt->execute();
									$upgradeStmt->close();
								}
							}

							// 4. Record Successful Login
							recordLoginAttempt($connect, $username, true);

							// 5. Prevent Session Fixation
							session_regenerate_id(true);

							// 6. Establish Secure Session
							$_SESSION['userId'] = (int)$user['user_id'];
							$_SESSION['username'] = $user['username'];
							$_SESSION['last_activity'] = time();
							$_SESSION['user_agent'] = hash('sha256', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
							$_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Rotate CSRF token

							header('location: dashboard.php');	
							exit();
						} else {
							recordLoginAttempt($connect, $username, false);
							$errors[] = "Combinación incorrecta de nombre de usuario y/o contraseña.";
						}
					} else {
						recordLoginAttempt($connect, $username, false);
						// Generic message to prevent username enumeration
						$errors[] = "Combinación incorrecta de nombre de usuario y/o contraseña.";
					}
					$stmt->close();
				} else {
					$errors[] = "Error interno del servidor. Por favor intente más tarde.";
				}
			} // /rate limiter allowed
		} // /valid credentials
	} // /if $_POST
?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo htmlspecialchars(env('APP_NAME', 'Sistema de Gestión de Inventario')); ?> - Ingreso</title>
	<!-- bootstrap -->
	<link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
	<!-- bootstrap theme-->
	<link rel="stylesheet" href="assests/bootstrap/css/bootstrap-theme.min.css">
	<!-- font awesome -->
	<link rel="stylesheet" href="assests/font-awesome/css/font-awesome.min.css">
	<!-- custom css -->
	<link rel="stylesheet" href="custom/css/custom.css">	
	<!-- jquery -->
	<script src="assests/jquery/jquery.min.js"></script>
	<!-- jquery ui -->  
	<link rel="stylesheet" href="assests/jquery-ui/jquery-ui.min.css">
	<script src="assests/jquery-ui/jquery-ui.min.js"></script>
	<!-- bootstrap js -->
	<script src="assests/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
	<div class="container">
		<div class="row vertical">
			<div class="col-xs-12 col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3 col-lg-4 col-lg-offset-4">
				<div class="panel panel-info">
					<div class="panel-heading">
						<h3 class="panel-title"><i class="glyphicon glyphicon-lock"></i> Inicio de sesión</h3>
					</div>
					<div class="panel-body">
						<div class="messages">
							<?php if($errors) {
								foreach ($errors as $key => $value){
									echo '<div class="alert alert-warning" role="alert">
									<i class="glyphicon glyphicon-exclamation-sign"></i>
									'.$value.'</div>';										
									}
								} 
							?>
						</div>

						<form class="form-horizontal" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" id="loginForm">
							<?php echo csrfField(); ?>
							<fieldset>
							  <div class="form-group">
									<label for="username" class="col-sm-3 control-label">Usuario</label>
									<div class="col-sm-9">
									  <input type="text" class="form-control" id="username" name="username" placeholder="Nombre de usuario" autocomplete="off" required />
									</div>
								</div>
								<div class="form-group">
									<label for="password" class="col-sm-3 control-label">Contraseña</label>
									<div class="col-sm-9">
									  <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" autocomplete="off" required />
									</div>
								</div>								
								<div class="form-group">
									<div class="col-sm-offset-3 col-sm-9">
									  <button type="submit" class="btn btn-primary btn-block"> <i class="glyphicon glyphicon-log-in"></i> Ingresar</button>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<!-- panel-body -->
				</div>
				<!-- /panel -->
			</div>
			<!-- /col -->
		</div>
		<!-- /row -->
	</div>
	<!-- container -->	
</body>
</html>	