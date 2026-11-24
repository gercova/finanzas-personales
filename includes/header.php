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
	<link rel="stylesheet" href="custom/css/custom.css">
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
		/* Finance navbar enhancements */
		.navbar-quota-bar {
			padding: 6px 15px;
			min-width: 180px;
			display: inline-block;
			vertical-align: middle;
		}
		.navbar-quota-bar .quota-label {
			font-size: 10px;
			color: #aaa;
			margin-bottom: 2px;
		}
		.navbar-quota-bar .progress {
			margin-bottom: 0;
			height: 8px;
			border-radius: 4px;
			background-color: #2a2a2a;
		}
		.navbar-quota-bar .quota-text {
			font-size: 10px;
			color: #bbb;
			margin-top: 2px;
		}
		.navbar-default {
			background-color: #1a1a2e;
			border-color: #16213e;
		}
		.navbar-default .navbar-brand,
		.navbar-default .navbar-nav > li > a {
			color: #e0e0e0;
		}
		.navbar-default .navbar-brand:hover,
		.navbar-default .navbar-nav > li > a:hover {
			color: #ffffff;
			background-color: #16213e;
		}
		.navbar-default .navbar-nav > .active > a,
		.navbar-default .navbar-nav > .active > a:hover,
		.navbar-default .navbar-nav > .active > a:focus {
			background-color: #0f3460;
			color: #ffffff;
		}
		.navbar-default .navbar-toggle {
			border-color: #444;
		}
		.navbar-default .navbar-toggle .icon-bar {
			background-color: #e0e0e0;
		}
		.navbar-default .navbar-nav > .open > a,
		.navbar-default .navbar-nav > .open > a:hover,
		.navbar-default .navbar-nav > .open > a:focus {
			background-color: #0f3460;
			color: #fff;
		}
		.navbar-default .navbar-nav > .open .dropdown-menu {
			background-color: #1a1a2e;
			border-color: #0f3460;
		}
		.navbar-default .navbar-nav > .open .dropdown-menu > li > a {
			color: #e0e0e0;
		}
		.navbar-default .navbar-nav > .open .dropdown-menu > li > a:hover {
			background-color: #0f3460;
			color: #fff;
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
					<i class="glyphicon glyphicon-piggy-bank"></i> <?php echo htmlspecialchars(env('APP_NAME', 'Finanzas'), ENT_QUOTES, 'UTF-8'); ?>
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
					$<?php echo number_format($monthlySpent, 2); ?> / $<?php echo number_format((float)$spendLimit['monthly_limit'], 2); ?>
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
							<i class="glyphicon glyphicon-briefcase"></i> Proyectos
						</a>
					</li>
					<li id="navAssets" <?php if($currentPage==='assets.php') echo 'class="active"'; ?>>
						<a href="assets.php">
							<i class="glyphicon glyphicon-certificate"></i> Activos
						</a>
					</li>
					<li id="navReport" <?php if($currentPage==='finance_report.php') echo 'class="active"'; ?>>
						<a href="finance_report.php">
							<i class="glyphicon glyphicon-stats"></i> Reportes
						</a>
					</li>
				</ul>

				<ul class="nav navbar-nav navbar-right">
					<li class="dropdown" id="navSetting">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
							<i class="glyphicon glyphicon-user"></i>
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
