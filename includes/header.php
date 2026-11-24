<?php require_once 'php_action/core.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="<?php echo getCsrfToken(); ?>">
	<title><?php echo htmlspecialchars(env('APP_NAME', 'Finanzas Personales'), ENT_QUOTES, 'UTF-8'); ?></title>
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
		   MODERN EXECUTIVE FINTECH NAVBAR (High-contrast Dark Theme)
		   Overrides bootstrap-theme gradient cleanly with !important
		   ============================================================== */
		.navbar-default {
			background-color: #1e293b !important;
			background-image: none !important;
			border-color: #0f172a !important;
			border-radius: 0 !important;
			box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25) !important;
			margin-bottom: 22px;
		}
		.navbar-default .navbar-brand {
			color: #ffffff !important;
			font-size: 17px;
			font-weight: 700;
			letter-spacing: 0.4px;
			padding: 15px 16px;
			transition: color 0.2s ease;
		}
		.navbar-default .navbar-brand i {
			color: #10b981;
			font-size: 19px;
			margin-right: 6px;
			vertical-align: middle;
		}
		.navbar-default .navbar-brand:hover {
			color: #38bdf8 !important;
		}
		.navbar-default .navbar-nav > li > a {
			color: #cbd5e1 !important;
			font-weight: 500;
			font-size: 13.5px;
			padding: 15px 12px;
			transition: all 0.2s ease;
		}
		.navbar-default .navbar-nav > li > a:hover {
			color: #ffffff !important;
			background-color: rgba(255, 255, 255, 0.08) !important;
			background-image: none !important;
		}
		.navbar-default .navbar-nav > .active > a,
		.navbar-default .navbar-nav > .active > a:hover,
		.navbar-default .navbar-nav > .active > a:focus {
			color: #ffffff !important;
			background-color: #2563eb !important;
			background-image: none !important;
			box-shadow: inset 0 -3px 0 #60a5fa, 0 2px 4px rgba(0,0,0,0.2) !important;
			font-weight: 600;
		}
		.navbar-default .navbar-nav > .open > a,
		.navbar-default .navbar-nav > .open > a:hover,
		.navbar-default .navbar-nav > .open > a:focus {
			color: #ffffff !important;
			background-color: #0f172a !important;
			background-image: none !important;
		}
		/* Dropdown Menu */
		.navbar-default .navbar-nav .dropdown-menu {
			background-color: #1e293b !important;
			border: 1px solid #334155 !important;
			border-radius: 6px !important;
			box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.45) !important;
			padding: 6px 0;
			margin-top: 4px;
		}
		.navbar-default .navbar-nav .dropdown-menu > li > a {
			color: #e2e8f0 !important;
			padding: 9px 18px;
			font-size: 13px;
			transition: background 0.15s ease;
		}
		.navbar-default .navbar-nav .dropdown-menu > li > a:hover {
			background-color: #2563eb !important;
			background-image: none !important;
			color: #ffffff !important;
		}
		.navbar-default .navbar-nav .dropdown-menu .divider {
			background-color: #334155 !important;
			margin: 5px 0;
		}
		/* Mobile Navbar Toggle Button */
		.navbar-default .navbar-toggle {
			border-color: #475569 !important;
			background-color: rgba(255,255,255,0.06) !important;
			margin-top: 10px;
			margin-bottom: 10px;
		}
		.navbar-default .navbar-toggle:hover,
		.navbar-default .navbar-toggle:focus {
			background-color: rgba(255,255,255,0.12) !important;
		}
		.navbar-default .navbar-toggle .icon-bar {
			background-color: #f8fafc !important;
		}
		/* Azure Quota Container */
		.navbar-quota-bar {
			padding: 6px 14px;
			min-width: 195px;
			display: inline-block;
			vertical-align: middle;
			background: rgba(15, 23, 42, 0.7);
			border: 1px solid rgba(255, 255, 255, 0.14);
			border-radius: 6px;
			margin-top: 8px;
			margin-bottom: 8px;
			margin-left: 12px;
		}
		.navbar-quota-bar .quota-label {
			font-size: 10px;
			font-weight: 700;
			color: #94a3b8;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-bottom: 2px;
		}
		.navbar-quota-bar .progress {
			margin-bottom: 0;
			height: 7px;
			border-radius: 4px;
			background-color: #334155;
			overflow: hidden;
		}
		.navbar-quota-bar .quota-text {
			font-size: 10.5px;
			color: #e2e8f0;
			margin-top: 2px;
			font-weight: 500;
		}
		/* Mobile responsive adjustments */
		@media (max-width: 767px) {
			.navbar-default .navbar-nav > li > a {
				border-bottom: 1px solid #334155;
			}
			.navbar-default .navbar-collapse {
				border-color: #334155;
			}
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
// ── Spending quota data for navbar bar ──────────────────────────────────────
$currentMonth = date('Y-m');
$spendLimit   = null;
$stmt = $connect->prepare(
    "SELECT monthly_limit, warning_threshold, critical_threshold FROM spending_limits WHERE month_year = ?"
);
if ($stmt) {
    $stmt->bind_param("s", $currentMonth);
    $stmt->execute();
    $r         = $stmt->get_result();
    $spendLimit = $r->fetch_assoc();
    $stmt->close();
}
$monthlySpent = 0;
$stmt2 = $connect->prepare(
    "SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m') = ? AND created_by = ?"
);
if ($stmt2) {
    $stmt2->bind_param("si", $currentMonth, $_SESSION['userId']);
    $stmt2->execute();
    $r2           = $stmt2->get_result();
    $row2         = $r2->fetch_assoc();
    $monthlySpent = (float)$row2['total'];
    $stmt2->close();
}

// Compute quota percentage
$quotaPct       = 0;
$quotaBarClass  = 'success';
if ($spendLimit && (float)$spendLimit['monthly_limit'] > 0) {
    $quotaPct = round(($monthlySpent / (float)$spendLimit['monthly_limit']) * 100, 1);
    $warn     = isset($spendLimit['warning_threshold'])  ? (float)$spendLimit['warning_threshold']  : 75;
    $crit     = isset($spendLimit['critical_threshold']) ? (float)$spendLimit['critical_threshold'] : 90;
    if ($quotaPct >= $crit) {
        $quotaBarClass = 'danger';
    } elseif ($quotaPct >= $warn) {
        $quotaBarClass = 'warning';
    } else {
        $quotaBarClass = 'success';
    }
}
$quotaPctCapped = min($quotaPct, 100);
// Determine current page for active-state
$currentPage = basename($_SERVER['PHP_SELF']);
?>
	<nav class="navbar navbar-default navbar-static-top">
		<div class="container">
			<!-- Brand and toggle -->
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#financeNavbar" aria-expanded="false">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="dashboard.php">
					<i class="glyphicon glyphicon-piggy-bank"></i> <?php echo htmlspecialchars(env('APP_NAME', 'Finanzas Personales'), ENT_QUOTES, 'UTF-8'); ?>
				</a>
			</div>

			<?php if ($spendLimit): ?>
			<!-- Azure-style spending quota bar (desktop only) -->
			<div class="navbar-quota-bar hidden-xs hidden-sm">
				<div class="quota-label">Gasto mensual</div>
				<div class="progress">
					<div class="progress-bar progress-bar-<?php echo $quotaBarClass; ?>"
						 role="progressbar"
						 aria-valuenow="<?php echo $quotaPctCapped; ?>"
						 aria-valuemin="0" aria-valuemax="100"
						 style="width:<?php echo $quotaPctCapped; ?>%; min-width:2px;">
					</div>
				</div>
				<div class="quota-text">
					S/ <?php echo number_format($monthlySpent, 2); ?> / S/ <?php echo number_format((float)$spendLimit['monthly_limit'], 2); ?>
					&nbsp;(<?php echo $quotaPct; ?>%)
				</div>
			</div>
			<?php endif; ?>

			<!-- Nav links -->
			<div class="collapse navbar-collapse" id="financeNavbar">
				<ul class="nav navbar-nav navbar-left">
					<li id="navDashboard" <?php if($currentPage==='dashboard.php') echo 'class="active"'; ?>>
						<a href="dashboard.php">
							<i class="glyphicon glyphicon-home"></i> Inicio
						</a>
					</li>
					<li id="navIncome" <?php if($currentPage==='income.php') echo 'class="active"'; ?>>
						<a href="income.php">
							<i class="glyphicon glyphicon-arrow-up" style="color:#4ade80;"></i> Ingresos
						</a>
					</li>
					<li id="navExpense" <?php if($currentPage==='expense.php') echo 'class="active"'; ?>>
						<a href="expense.php">
							<i class="glyphicon glyphicon-arrow-down" style="color:#f87171;"></i> Gastos
						</a>
					</li>
					<li id="navLoans" <?php if($currentPage==='loans.php') echo 'class="active"'; ?>>
						<a href="loans.php">
							<i class="glyphicon glyphicon-retweet" style="color:#fbbf24;"></i> Préstamos
						</a>
					</li>
					<li id="navProjects" <?php if($currentPage==='projects.php') echo 'class="active"'; ?>>
						<a href="projects.php">
							<i class="glyphicon glyphicon-briefcase" style="color:#38bdf8;"></i> Proyectos
						</a>
					</li>
					<li id="navAssets" <?php if($currentPage==='assets.php') echo 'class="active"'; ?>>
						<a href="assets.php">
							<i class="glyphicon glyphicon-certificate" style="color:#c084fc;"></i> Activos
						</a>
					</li>
					<li id="navReport" <?php if($currentPage==='finance_report.php') echo 'class="active"'; ?>>
						<a href="finance_report.php">
							<i class="glyphicon glyphicon-stats" style="color:#22d3ee;"></i> Reportes
						</a>
					</li>
				</ul>

				<ul class="nav navbar-nav navbar-right">
					<li class="dropdown" id="navSetting">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
							<i class="glyphicon glyphicon-user" style="color:#38bdf8;"></i>
							<?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuario', ENT_QUOTES, 'UTF-8'); ?>
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
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
