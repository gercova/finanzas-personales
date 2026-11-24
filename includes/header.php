<?php require_once 'php_action/core.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="<?php echo getCsrfToken(); ?>">
	<title><?php echo htmlspecialchars(env('APP_NAME', 'myFin'), ENT_QUOTES, 'UTF-8'); ?></title>
	<!-- bootstrap -->
	<link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
	<!-- bootstrap theme-->
	<link rel="stylesheet" href="assests/bootstrap/css/bootstrap-theme.min.css">
	<!-- font awesome -->
	<link rel="stylesheet" href="assests/font-awesome/css/font-awesome.min.css">
	<!-- custom css -->
	<link rel="stylesheet" href="custom/css/custom.css?v=<?php echo time(); ?>">
	<!-- DataTables -->
	<link rel="stylesheet" href="assests/plugins/datatables/jquery.dataTables.min.css">
	<!-- DataTables Responsive -->
	<link rel="stylesheet" href="assests/plugins/datatables/extensions/Responsive/css/dataTables.responsive.css">
	<!-- file input -->
	<link rel="stylesheet" href="assests/plugins/fileinput/css/fileinput.min.css">
	<!-- jquery -->
	<script src="assests/jquery/jquery.min.js"></script> 
	<!-- jquery ui -->  
	<link rel="stylesheet" href="assests/jquery-ui/jquery-ui.min.css">
	<script src="assests/jquery-ui/jquery-ui.min.js"></script>
	<!-- bootstrap js -->
	<script src="assests/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript">
		var systemCsrfToken = <?php echo json_encode(getCsrfToken()); ?>;
		$.ajaxSetup({
			headers: {
				'X-CSRF-Token': systemCsrfToken
			}
		});
	</script>
	<style type="text/css">
		/* ==============================================================
		   CLASSIC BOOTSTRAP LIGHT NAVBAR (Matching User Reference Image)
		   ============================================================== */
		.navbar-default {
			background-color: #f8f8f8;
			background-image: -webkit-linear-gradient(top, #ffffff 0%, #f8f8f8 100%) !important;
			background-image: linear-gradient(to bottom, #ffffff 0%, #f8f8f8 100%) !important;
			background-repeat: repeat-x;
			border-color: #e7e7e7 !important;
			border-radius: 4px;
			-webkit-box-shadow: inset 0 1px 0 rgba(255,255,255,.15), 0 1px 5px rgba(0,0,0,.075) !important;
			box-shadow: inset 0 1px 0 rgba(255,255,255,.15), 0 1px 5px rgba(0,0,0,.075) !important;
			margin-bottom: 20px;
		}
		.navbar-default .navbar-brand {
			color: #333333 !important;
			font-weight: 700;
			font-size: 19px;
			letter-spacing: -0.2px;
			padding: 15px 15px;
			display: inline-flex;
			align-items: center;
			gap: 6px;
			text-shadow: 0 1px 0 #fff;
		}
		.navbar-default .navbar-brand:hover {
			color: #111111 !important;
		}
		.navbar-default .navbar-nav > li > a {
			color: #555555 !important;
			font-size: 13.5px;
			font-weight: 500;
			padding: 15px 12px;
			text-shadow: 0 1px 0 rgba(255,255,255,.5);
			transition: background-color 0.15s ease, color 0.15s ease;
		}
		.navbar-default .navbar-nav > li > a:hover {
			color: #222222 !important;
			background-color: #e7e7e7 !important;
			background-image: -webkit-linear-gradient(top, #f2f2f2 0%, #e7e7e7 100%) !important;
			background-image: linear-gradient(to bottom, #f2f2f2 0%, #e7e7e7 100%) !important;
			background-repeat: repeat-x;
		}
		/* Active tab with grey gradient button styling (matching screenshot) */
		.navbar-default .navbar-nav > .active > a,
		.navbar-default .navbar-nav > .active > a:hover,
		.navbar-default .navbar-nav > .active > a:focus {
			color: #333333 !important;
			background-color: #e2e2e2 !important;
			background-image: -webkit-linear-gradient(top, #dbdbdb 0%, #e2e2e2 100%) !important;
			background-image: linear-gradient(to bottom, #dbdbdb 0%, #e2e2e2 100%) !important;
			background-repeat: repeat-x !important;
			-webkit-box-shadow: inset 0 3px 9px rgba(0,0,0,.075) !important;
			box-shadow: inset 0 3px 9px rgba(0,0,0,.075) !important;
			font-weight: 600;
		}
		/* Open dropdown */
		.navbar-default .navbar-nav > .open > a,
		.navbar-default .navbar-nav > .open > a:hover,
		.navbar-default .navbar-nav > .open > a:focus {
			background-color: #e2e2e2 !important;
			background-image: -webkit-linear-gradient(top, #dbdbdb 0%, #e2e2e2 100%) !important;
			background-image: linear-gradient(to bottom, #dbdbdb 0%, #e2e2e2 100%) !important;
			color: #333333 !important;
			box-shadow: inset 0 3px 9px rgba(0,0,0,.075) !important;
		}
		.navbar-default .navbar-nav .dropdown-menu {
			background-color: #ffffff !important;
			border: 1px solid #ccc !important;
			border-radius: 4px !important;
			box-shadow: 0 6px 12px rgba(0,0,0,.175) !important;
		}
		.navbar-default .navbar-nav .dropdown-menu > li > a {
			color: #333333 !important;
			padding: 7px 18px !important;
		}
		.navbar-default .navbar-nav .dropdown-menu > li > a:hover {
			background-color: #f5f5f5 !important;
			background-image: none !important;
			color: #262626 !important;
		}
		/* Metric badges on the left next to myFin */
		.myfin-metrics-container {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			margin-left: 10px;
			vertical-align: middle;
			height: 50px;
		}
		.metric-badge {
			display: inline-flex;
			align-items: center;
			gap: 5px;
			padding: 5px 9px;
			border-radius: 4px;
			font-size: 11.5px;
			font-weight: 600;
			line-height: 1.2;
			box-shadow: 0 1px 2px rgba(0,0,0,0.08);
			white-space: nowrap;
		}
		.metric-badge-income {
			background-color: #dff0d8;
			color: #2e7d32;
			border: 1px solid #c8e6c9;
		}
		.metric-badge-expense {
			background-color: #fce4ec;
			color: #c2185b;
			border: 1px solid #f8bbd0;
		}

		/* ==============================================================
		   PANEL HEADING WITH VERTICALLY CENTERED ACTION BUTTON (RIGHT)
		   ============================================================== */
		.panel-heading.panel-heading-flex,
		.panel-heading-flex {
			display: -webkit-box !important;
			display: -webkit-flex !important;
			display: -ms-flexbox !important;
			display: flex !important;
			-webkit-box-pack: justify !important;
			-webkit-justify-content: space-between !important;
			-ms-flex-pack: justify !important;
			justify-content: space-between !important;
			-webkit-box-align: center !important;
			-webkit-align-items: center !important;
			-ms-flex-align: center !important;
			align-items: center !important;
			min-height: 52px !important;
			padding: 8px 15px !important;
			box-sizing: border-box !important;
		}
		.panel-heading-flex .page-heading,
		.panel-heading-flex .panel-title {
			margin: 0 !important;
			padding: 0 !important;
			font-size: 16px !important;
			font-weight: 600 !important;
			line-height: 1 !important;
			display: -webkit-box !important;
			display: -webkit-flex !important;
			display: -ms-flexbox !important;
			display: flex !important;
			-webkit-box-align: center !important;
			-webkit-align-items: center !important;
			-ms-flex-align: center !important;
			align-items: center !important;
			float: none !important;
		}
		.panel-heading-flex .page-heading i,
		.panel-heading-flex .panel-title i {
			margin-right: 8px !important;
			font-size: 16px !important;
			vertical-align: middle !important;
		}
		.panel-heading-flex .btn,
		.panel-heading-flex button {
			margin: 0 !important;
			vertical-align: middle !important;
			float: none !important;
			display: -webkit-inline-box !important;
			display: -webkit-inline-flex !important;
			display: -ms-inline-flexbox !important;
			display: inline-flex !important;
			-webkit-box-align: center !important;
			-webkit-align-items: center !important;
			-ms-flex-align: center !important;
			align-items: center !important;
			gap: 5px !important;
			box-shadow: 0 1px 3px rgba(0,0,0,0.12) !important;
		}
	</style>
</head>
<body>
<?php
// ── Data calculations for header metrics ────────────────────────────────────
$userId       = (int)$_SESSION['userId'];
$currentMonth = date('Y-m');
$todayDate    = date('Y-m-d');

// 1. Ingresos del mes actual
$monthIncome = 0.0;
$stmtMI = $connect->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM incomes WHERE DATE_FORMAT(income_date, '%Y-%m') = ? AND created_by = ?");
if ($stmtMI) {
    $stmtMI->bind_param("si", $currentMonth, $userId);
    $stmtMI->execute();
    $monthIncome = (float)$stmtMI->get_result()->fetch_assoc()['total'];
    $stmtMI->close();
}

// 2. Gastos del día de hoy
$todayExpenses = 0.0;
$stmtTE = $connect->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE expense_date = ? AND created_by = ?");
if ($stmtTE) {
    $stmtTE->bind_param("si", $todayDate, $userId);
    $stmtTE->execute();
    $todayExpenses = (float)$stmtTE->get_result()->fetch_assoc()['total'];
    $stmtTE->close();
}

// Determine current page for active-state
$currentPage = basename($_SERVER['PHP_SELF']);
?>
	<nav class="navbar navbar-default navbar-static-top">
		<div class="container">
			<!-- Brand and metrics on the left -->
			<div class="navbar-header" style="display: flex; align-items: center;">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#financeNavbar" aria-expanded="false">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="dashboard.php" title="myFin - Gestión de Finanzas Personales">
					<i class="glyphicon glyphicon-piggy-bank" style="color: #27ae60;"></i> myFin
				</a>

				<!-- Left metrics: Mes income & Day expense -->
				<div class="myfin-metrics-container hidden-xs">
					<span class="metric-badge metric-badge-income" title="Ingresos del mes de <?php echo date('F Y'); ?>">
						<i class="glyphicon glyphicon-arrow-up"></i> Mes: S/ <?php echo number_format($monthIncome, 2); ?>
					</span>
					<span class="metric-badge metric-badge-expense" title="Gastos del día de hoy (<?php echo date('d/m/Y'); ?>)">
						<i class="glyphicon glyphicon-arrow-down"></i> Hoy: S/ <?php echo number_format($todayExpenses, 2); ?>
					</span>
				</div>
			</div>

			<!-- Nav links on the right (matching user's reference image) -->
			<div class="collapse navbar-collapse" id="financeNavbar">
				<ul class="nav navbar-nav navbar-right">
					<li id="navDashboard" <?php if($currentPage==='dashboard.php') echo 'class="active"'; ?>>
						<a href="dashboard.php">
							<i class="glyphicon glyphicon-home"></i> Inicio
						</a>
					</li>
					<li id="navIncome" <?php if($currentPage==='income.php') echo 'class="active"'; ?>>
						<a href="income.php">
							<i class="glyphicon glyphicon-arrow-up text-success"></i> Ingresos
						</a>
					</li>
					<li id="navExpense" <?php if($currentPage==='expense.php') echo 'class="active"'; ?>>
						<a href="expense.php">
							<i class="glyphicon glyphicon-arrow-down text-danger"></i> Gastos
						</a>
					</li>
					<li id="navLoans" <?php if($currentPage==='loans.php') echo 'class="active"'; ?>>
						<a href="loans.php">
							<i class="glyphicon glyphicon-retweet text-warning"></i> Préstamos
						</a>
					</li>
					<li id="navProjects" <?php if($currentPage==='projects.php') echo 'class="active"'; ?>>
						<a href="projects.php">
							<i class="glyphicon glyphicon-briefcase text-primary"></i> Proyectos
						</a>
					</li>
					<li id="navAssets" <?php if($currentPage==='assets.php') echo 'class="active"'; ?>>
						<a href="assets.php">
							<i class="glyphicon glyphicon-certificate" style="color:#8e44ad;"></i> Activos
						</a>
					</li>
					<li id="navReport" <?php if($currentPage==='finance_report.php') echo 'class="active"'; ?>>
						<a href="finance_report.php">
							<i class="glyphicon glyphicon-stats text-info"></i> Reportes
						</a>
					</li>
					<!-- User options dropdown (matching screenshot with icon and caret) -->
					<li class="dropdown" id="navSetting">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
							<i class="glyphicon glyphicon-user"></i> <span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li class="dropdown-header">Usuario: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'admin', ENT_QUOTES, 'UTF-8'); ?></strong></li>
							<li role="separator" class="divider"></li>
							<li id="topNavSetting">
								<a href="setting.php">
									<i class="glyphicon glyphicon-wrench"></i> Configuración
								</a>
							</li>
							<li role="separator" class="divider"></li>
							<li id="topNavLogout">
								<a href="logout.php">
									<i class="glyphicon glyphicon-log-out"></i> Salir
								</a>
							</li>
						</ul>
					</li>
				</ul>
			</div><!-- /.navbar-collapse -->
		</div><!-- /.container -->
	</nav>
	<div class="container main-content-container">
