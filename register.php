<?php
require_once 'php_action/db_connect.php';
require_once 'php_action/security.php';

setSecurityHeaders();
initSecureSession();

if (isset($_SESSION['userId'])) {
    header('location: dashboard.php');
    exit();
}

$errors = [];
$formValues = [
    'full_name' => '',
    'username'  => '',
    'email'     => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName        = trim($_POST['full_name'] ?? '');
    $username        = trim($_POST['username'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    $formValues['full_name'] = $fullName;
    $formValues['username']  = $username;
    $formValues['email']     = $email;

    if (!verifyCsrfToken()) {
        $errors[] = "Error de validación de seguridad (CSRF inválido). Recargue la página.";
    } else {
        if (empty($fullName) || mb_strlen($fullName) < 2) {
            $errors[] = "El nombre completo debe tener al menos 2 caracteres.";
        }
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,40}$/', $username)) {
            $errors[] = "El usuario debe tener entre 3 y 40 caracteres alfanuméricos (puede incluir '.', '_' o '-').";
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Ingrese un correo electrónico válido.";
        }
        if (strlen($password) < 6) {
            $errors[] = "La contraseña debe tener al menos 6 caracteres.";
        } elseif ($password !== $confirmPassword) {
            $errors[] = "Las contraseñas no coinciden.";
        }

        if (empty($errors)) {
            $emailParam = ($email !== '') ? $email : null;

            // Check duplicate username or email
            $chkSql = "SELECT user_id, username, email FROM users WHERE username = ? OR (? IS NOT NULL AND email = ?) LIMIT 1";
            $chkStmt = $connect->prepare($chkSql);
            if ($chkStmt) {
                $chkStmt->bind_param("sss", $username, $emailParam, $emailParam);
                $chkStmt->execute();
                $dup = $chkStmt->get_result()->fetch_assoc();
                $chkStmt->close();

                if ($dup) {
                    if (strcasecmp($dup['username'], $username) === 0) {
                        $errors[] = "El nombre de usuario ya está registrado. Elija otro.";
                    } else {
                        $errors[] = "El correo electrónico ya está asociado a otra cuenta.";
                    }
                }
            } else {
                $errors[] = "Error interno verificando disponibilidad de usuario.";
            }
        }

        if (empty($errors)) {
            $hashed = hashPassword($password);
            $emailParam = ($email !== '') ? $email : null;

            // INSERT fires trg_users_after_insert -> sp_init_user_defaults(NEW.user_id)
            // which automatically provisions isolated accounts, categories & monthly quota.
            $insStmt = $connect->prepare(
                "INSERT INTO users (username, password, email, full_name, role, is_active) VALUES (?, ?, ?, ?, 'USER', 1)"
            );
            if ($insStmt) {
                $insStmt->bind_param("ssss", $username, $hashed, $emailParam, $fullName);
                if ($insStmt->execute()) {
                    $insStmt->close();
                    header('location: index.php?registered=1');
                    exit();
                } else {
                    $errors[] = "No se pudo completar el registro. Intente nuevamente.";
                }
                $insStmt->close();
            } else {
                $errors[] = "Error preparando el registro de usuario.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo htmlspecialchars(env('APP_NAME', 'myFin'), ENT_QUOTES, 'UTF-8'); ?> - Registro</title>
	<link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="assests/bootstrap/css/bootstrap-theme.min.css">
	<link rel="stylesheet" href="assests/font-awesome/css/font-awesome.min.css">
	<link rel="stylesheet" href="custom/css/custom.css">
	<script src="assests/jquery/jquery.min.js"></script>
	<script src="assests/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
	<div class="container">
		<div class="row vertical">
			<div class="col-xs-12 col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3 col-lg-5 col-lg-offset-3">
				<div class="panel panel-success">
					<div class="panel-heading">
						<h3 class="panel-title"><i class="glyphicon glyphicon-user"></i> Crear Cuenta en <?php echo htmlspecialchars(env('APP_NAME', 'myFin'), ENT_QUOTES, 'UTF-8'); ?></h3>
					</div>
					<div class="panel-body">
						<div class="messages">
							<?php if (!empty($errors)): ?>
								<?php foreach ($errors as $err): ?>
									<div class="alert alert-warning" role="alert">
										<i class="glyphicon glyphicon-exclamation-sign"></i>
										<?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>

						<form class="form-horizontal" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post" id="registerForm">
							<?php echo csrfField(); ?>
							<div class="form-group">
								<label for="full_name" class="col-sm-4 control-label">Nombre completo</label>
								<div class="col-sm-8">
									<input type="text" class="form-control" id="full_name" name="full_name" placeholder="Ej: María Pérez" value="<?php echo htmlspecialchars($formValues['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required />
								</div>
							</div>
							<div class="form-group">
								<label for="username" class="col-sm-4 control-label">Usuario</label>
								<div class="col-sm-8">
									<input type="text" class="form-control" id="username" name="username" placeholder="usuario_123" value="<?php echo htmlspecialchars($formValues['username'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" required />
								</div>
							</div>
							<div class="form-group">
								<label for="email" class="col-sm-4 control-label">Correo (opcional)</label>
								<div class="col-sm-8">
									<input type="email" class="form-control" id="email" name="email" placeholder="correo@ejemplo.com" value="<?php echo htmlspecialchars($formValues['email'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="email" />
								</div>
							</div>
							<div class="form-group">
								<label for="password" class="col-sm-4 control-label">Contraseña</label>
								<div class="col-sm-8">
									<input type="password" class="form-control" id="password" name="password" placeholder="Mínimo 6 caracteres" autocomplete="new-password" required />
								</div>
							</div>
							<div class="form-group">
								<label for="confirm_password" class="col-sm-4 control-label">Confirmar</label>
								<div class="col-sm-8">
									<input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repetir contraseña" autocomplete="new-password" required />
								</div>
							</div>
							<div class="form-group">
								<div class="col-sm-offset-4 col-sm-8">
									<button type="submit" class="btn btn-success btn-block">
										<i class="glyphicon glyphicon-ok-sign"></i> Registrarme
									</button>
								</div>
							</div>
						</form>
					</div>
					<div class="panel-footer text-center" style="font-size: 13px;">
						¿Ya tienes cuenta? <a href="index.php"><strong>Inicia sesión</strong></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
