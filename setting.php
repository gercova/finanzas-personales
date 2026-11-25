<?php
if (!isset($_GET['classic'])) {
    header('Location: app.php#/settings');
    exit();
}
require_once 'includes/header.php';
?>
<?php 
	$user_id = (int)$_SESSION['userId'];
	$stmt = $connect->prepare("SELECT * FROM users WHERE user_id = ?");
	$result = null;
	if($stmt) {
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$res = $stmt->get_result();
		$result = $res->fetch_assoc();
		$stmt->close();
	}

	// Fetch current month's spending limit (strictly per-user)
	$currentMonth = date('Y-m');
	list($monthStart, $monthEnd) = getMonthDateBounds($currentMonth);
	$currentLimit = null;
	$stmtLim = $connect->prepare("SELECT * FROM spending_limits WHERE created_by = ? AND month_year = ?");
	if ($stmtLim) {
		$stmtLim->bind_param("is", $user_id, $currentMonth);
		$stmtLim->execute();
		$currentLimit = $stmtLim->get_result()->fetch_assoc();
		$stmtLim->close();
	}

	// Fetch current month's total spent (SARGable date range)
	$spentThisMonth = 0;
	$stmtSp = $connect->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE created_by = ? AND is_deleted = 0 AND expense_date >= ? AND expense_date < ?");
	if ($stmtSp) {
		$stmtSp->bind_param("iss", $user_id, $monthStart, $monthEnd);
		$stmtSp->execute();
		$spentThisMonth = (float)$stmtSp->get_result()->fetch_assoc()['total'];
		$stmtSp->close();
	}

	$limitVal = $currentLimit ? (float)$currentLimit['monthly_limit'] : 4000.00;
	$warnVal  = $currentLimit ? (float)$currentLimit['warning_threshold'] : 75.00;
	$critVal  = $currentLimit ? (float)$currentLimit['critical_threshold'] : 90.00;
	$pctSpent = $limitVal > 0 ? round(($spentThisMonth / $limitVal) * 100, 1) : 0;
	$barType  = $pctSpent >= $critVal ? 'danger' : ($pctSpent >= $warnVal ? 'warning' : 'success');
?>
<div class="row">
	<div class="col-xs-12 col-md-12">
		<ol class="breadcrumb">
		  <li><a href="dashboard.php">Inicio</a></li>		  
		  <li class="active">Configuración</li>
		</ol>

		<!-- ── Monthly Spending Quota (Azure style) ── -->
		<div class="panel panel-default">
			<div class="panel-heading">
				<div class="page-heading">
					<i class="glyphicon glyphicon-stats text-primary"></i> <strong>Límite Mensual de Gasto (Cuota de Consumo)</strong>
				</div>
			</div>
			<div class="panel-body">
				<div class="spendingLimitMessages"></div>

				<div class="alert alert-info" style="margin-bottom:20px;">
					<i class="glyphicon glyphicon-info-sign"></i> 
					El sistema supervisa tu gasto mensual de forma similar a las <strong>cuotas de consumo de Azure</strong>.
					Cuando tu gasto alcance el umbral de advertencia (amarillo) o el umbral crítico (rojo), recibirás alertas en la barra superior y en el panel principal.
				</div>

				<!-- Visual Progress Bar -->
				<div style="background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #e3e3e3; margin-bottom:20px;">
					<div class="row">
						<div class="col-sm-6">
							<strong>Mes actual:</strong> <?php echo date('F Y'); ?> (<code><?php echo $currentMonth; ?></code>)<br>
							<strong>Gasto actual:</strong> <span class="text-<?php echo $barType; ?> font-weight-bold">S/ <?php echo number_format($spentThisMonth, 2); ?></span>
							de <strong>S/ <?php echo number_format($limitVal, 2); ?></strong>
						</div>
						<div class="col-sm-6 text-right">
							<span class="label label-<?php echo $barType; ?>" style="font-size:14px;">
								Consumido: <?php echo $pctSpent; ?>%
							</span>
						</div>
					</div>
					<div class="progress" style="margin-top:10px; margin-bottom:0; height:18px;">
						<div class="progress-bar progress-bar-<?php echo $barType; ?> progress-bar-striped active" role="progressbar"
						     aria-valuenow="<?php echo min($pctSpent, 100); ?>" aria-valuemin="0" aria-valuemax="100"
						     style="width: <?php echo min($pctSpent, 100); ?>%;">
							<?php echo $pctSpent; ?>%
						</div>
					</div>
				</div>

				<form action="php_action/updateSpendingLimit.php" method="post" class="form-horizontal" id="spendingLimitForm">
					<?php echo csrfField(); ?>
					<div class="form-group">
						<label for="month_year" class="col-sm-3 control-label">Mes / Año (YYYY-MM)</label>
						<div class="col-sm-4">
							<input type="month" class="form-control" id="month_year" name="month_year" value="<?php echo htmlspecialchars($currentMonth, ENT_QUOTES, 'UTF-8'); ?>" required>
						</div>
					</div>

					<div class="form-group">
						<label for="monthly_limit" class="col-sm-3 control-label">Límite Mensual (S/)</label>
						<div class="col-sm-4">
							<input type="number" step="0.01" min="1" class="form-control" id="monthly_limit" name="monthly_limit" placeholder="Ej: 4000.00" value="<?php echo htmlspecialchars($limitVal, ENT_QUOTES, 'UTF-8'); ?>" required>
						</div>
					</div>

					<div class="form-group">
						<label for="warning_threshold" class="col-sm-3 control-label">Alerta de Advertencia (%)</label>
						<div class="col-sm-4">
							<input type="number" step="1" min="1" max="100" class="form-control" id="warning_threshold" name="warning_threshold" placeholder="75" value="<?php echo htmlspecialchars($warnVal, ENT_QUOTES, 'UTF-8'); ?>" required>
							<small class="text-muted">Muestra aviso amarillo al alcanzar este porcentaje (defecto: 75%).</small>
						</div>
					</div>

					<div class="form-group">
						<label for="critical_threshold" class="col-sm-3 control-label">Alerta Crítica (%)</label>
						<div class="col-sm-4">
							<input type="number" step="1" min="1" max="100" class="form-control" id="critical_threshold" name="critical_threshold" placeholder="90" value="<?php echo htmlspecialchars($critVal, ENT_QUOTES, 'UTF-8'); ?>" required>
							<small class="text-muted">Muestra aviso rojo crítico al alcanzar este porcentaje (defecto: 90%).</small>
						</div>
					</div>

					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-primary" id="saveSpendingLimitBtn">
								<i class="glyphicon glyphicon-floppy-disk"></i> Guardar Cuota de Gasto
							</button>
						</div>
					</div>
				</form>
			</div>
		</div>

		<!-- ── User Profile & Password ── -->
		<div class="panel panel-default">
			<div class="panel-heading">
				<div class="page-heading"> <i class="glyphicon glyphicon-wrench"></i> Configuración de Cuenta</div>
			</div> <!-- /panel-heading -->
			<div class="panel-body">
				<form action="php_action/changeUsername.php" method="post" class="form-horizontal" id="changeUsernameForm">
					<?php echo csrfField(); ?>
					<fieldset>
						<legend>Cambiar usuario</legend>
						<div class="changeUsenrameMessages"></div>			
						<div class="form-group">
					    	<label for="username" class="col-sm-2 control-label">Usuario</label>
					    	<div class="col-sm-10">
					    		<input type="text" class="form-control" id="username" name="username" placeholder="Usuario" value="<?php echo htmlspecialchars($result['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"/>
					    	</div>
					  	</div>

					  	<div class="form-group">
					    	<div class="col-sm-offset-2 col-sm-10">
					    		<input type="hidden" name="user_id" id="user_id" value="<?php echo $result['user_id'] ?? '' ?>"> 
					      		<button type="submit" class="btn btn-success" data-loading-text="Cargando..." id="changeUsernameBtn"> <i class="glyphicon glyphicon-ok-sign"></i> Guardar cambios </button>
					    	</div>
					  	</div>
					</fieldset>
				</form>
				<form action="php_action/changePassword.php" method="post" class="form-horizontal" id="changePasswordForm">
					<?php echo csrfField(); ?>
					<fieldset>
						<legend>Cambia la contraseña</legend>

						<div class="changePasswordMessages"></div>

						<div class="form-group">
					    	<label for="password" class="col-sm-2 control-label">Contraseña actual</label>
					    	<div class="col-sm-10">
					      		<input type="password" class="form-control" id="password" name="password" placeholder="Contraseña actual">
					    	</div>
					  	</div>

						<div class="form-group">
					    	<label for="npassword" class="col-sm-2 control-label">Nueva contraseña</label>
					    	<div class="col-sm-10">
					      		<input type="password" class="form-control" id="npassword" name="npassword" placeholder="Nueva contraseña">
					    	</div>
					  	</div>

					  	<div class="form-group">
					    	<label for="cpassword" class="col-sm-2 control-label">Confirmar contraseña</label>
					    	<div class="col-sm-10">
					      		<input type="password" class="form-control" id="cpassword" name="cpassword" placeholder="Confirmar contraseña">
					    	</div>
					  	</div>

					  	<div class="form-group">
					    	<div class="col-sm-offset-2 col-sm-10">
					    		<input type="hidden" name="user_id" id="user_id" value="<?php echo $result['user_id'] ?? '' ?>"> 
					      		<button type="submit" class="btn btn-primary"> <i class="glyphicon glyphicon-ok-sign"></i> Guardar cambios </button>
					    	</div>
					  	</div>
					</fieldset>
				</form>
			</div> <!-- /panel-body -->		
		</div> <!-- /panel -->		
	</div> <!-- /col-xs-12 col-md-12 -->	
</div> <!-- /row-->
<script src="custom/js/setting.js"></script>
<?php require_once 'includes/footer.php'; ?>
