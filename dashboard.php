<?php require_once 'includes/header.php'; ?>

<?php
// ── Dashboard data queries ───────────────────────────────────────────────────
$currentMonth = date('Y-m');
$userId       = (int)$_SESSION['userId'];

// 1. Saldo Disponible: sum of active account balances
$saldoDisponible = 0.0;
$stmtSaldo = $connect->prepare(
    "SELECT COALESCE(SUM(current_balance), 0) AS total FROM accounts WHERE status = 1"
);
if ($stmtSaldo) {
    $stmtSaldo->execute();
    $rSaldo          = $stmtSaldo->get_result();
    $rowSaldo        = $rSaldo->fetch_assoc();
    $saldoDisponible = (float)$rowSaldo['total'];
    $stmtSaldo->close();
}

// 2. Total Gastos del Mes y Total Gastos General
$totalGastosMes = 0.0;
$stmtGastos = $connect->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m') = ? AND created_by = ?"
);
if ($stmtGastos) {
    $stmtGastos->bind_param("si", $currentMonth, $userId);
    $stmtGastos->execute();
    $rGastos        = $stmtGastos->get_result();
    $rowGastos      = $rGastos->fetch_assoc();
    $totalGastosMes = (float)$rowGastos['total'];
    $stmtGastos->close();
}

// 3. Total Préstamos Pendientes (por cobrar)
$totalPrestamosPendientes = 0.0;
$stmtLoans = $connect->prepare(
    "SELECT COALESCE(SUM(balance_pending), 0) AS total FROM loans WHERE created_by = ? AND status IN ('PENDIENTE', 'PARCIAL')"
);
if ($stmtLoans) {
    $stmtLoans->bind_param("i", $userId);
    $stmtLoans->execute();
    $rLoans                   = $stmtLoans->get_result();
    $rowLoans                 = $rLoans->fetch_assoc();
    $totalPrestamosPendientes = (float)$rowLoans['total'];
    $stmtLoans->close();
}

// 4. Balance Total = Saldo Disponible + Préstamos Pendientes (ver Imagen 4: Saldo 9,420.65 + Préstamo 3,713.00 = 13,133.65)
$balanceTotal = $saldoDisponible + $totalPrestamosPendientes;

// 5. Quota check for alert banner
$spendLimit  = null;
$stmtLimit = $connect->prepare(
    "SELECT monthly_limit, warning_threshold, critical_threshold FROM spending_limits WHERE month_year = ?"
);
if ($stmtLimit) {
    $stmtLimit->bind_param("s", $currentMonth);
    $stmtLimit->execute();
    $rLimit    = $stmtLimit->get_result();
    $spendLimit = $rLimit->fetch_assoc();
    $stmtLimit->close();
}
$quotaPct       = 0;
$quotaAlertClass = '';
$showAlert      = false;
if ($spendLimit && (float)$spendLimit['monthly_limit'] > 0) {
    $quotaPct = round(($totalGastosMes / (float)$spendLimit['monthly_limit']) * 100, 1);
    $warn     = isset($spendLimit['warning_threshold'])  ? (float)$spendLimit['warning_threshold']  : 75;
    $crit     = isset($spendLimit['critical_threshold']) ? (float)$spendLimit['critical_threshold'] : 90;
    if ($quotaPct >= $crit) {
        $quotaAlertClass = 'danger';
        $showAlert       = true;
    } elseif ($quotaPct >= $warn) {
        $quotaAlertClass = 'warning';
        $showAlert       = true;
    }
}

// 6. Accounts summary table
$accounts = [];
$stmtAccounts = $connect->prepare(
    "SELECT account_name AS name, account_type, current_balance AS balance, currency FROM accounts WHERE status = 1 ORDER BY current_balance DESC"
);
if ($stmtAccounts) {
    $stmtAccounts->execute();
    $rAccounts = $stmtAccounts->get_result();
    while ($row = $rAccounts->fetch_assoc()) {
        $accounts[] = $row;
    }
    $stmtAccounts->close();
}

// 7. Recent incomes (last 5)
$recentIncomes = [];
$stmtInc = $connect->prepare(
    "SELECT concept AS description, amount, income_date AS txn_date, 'ingreso' AS txn_type FROM incomes WHERE created_by = ? ORDER BY income_date DESC, income_id DESC LIMIT 5"
);
if ($stmtInc) {
    $stmtInc->bind_param("i", $userId);
    $stmtInc->execute();
    $rInc = $stmtInc->get_result();
    while ($row = $rInc->fetch_assoc()) {
        $recentIncomes[] = $row;
    }
    $stmtInc->close();
}

// 8. Recent expenses (last 5)
$recentExpenses = [];
$stmtExp = $connect->prepare(
    "SELECT concept AS description, amount, expense_date AS txn_date, 'gasto' AS txn_type FROM expenses WHERE created_by = ? ORDER BY expense_date DESC, expense_id DESC LIMIT 5"
);
if ($stmtExp) {
    $stmtExp->bind_param("i", $userId);
    $stmtExp->execute();
    $rExp = $stmtExp->get_result();
    while ($row = $rExp->fetch_assoc()) {
        $recentExpenses[] = $row;
    }
    $stmtExp->close();
}

// Merge and sort by date desc (keep up to 10 combined)
$recentTxns = array_merge($recentIncomes, $recentExpenses);
usort($recentTxns, function ($a, $b) {
    return strcmp($b['txn_date'], $a['txn_date']);
});
$recentTxns = array_slice($recentTxns, 0, 10);
?>

<style type="text/css">
	.ui-datepicker-calendar {
		display: none;
	}
	/* Stat cards */
	.stat-card {
		border-radius: 6px;
		padding: 18px 20px 14px;
		color: #fff;
		margin-bottom: 15px;
		position: relative;
		overflow: hidden;
		box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	}
	.stat-card .stat-icon {
		position: absolute;
		right: 16px;
		top: 14px;
		font-size: 36px;
		opacity: 0.25;
	}
	.stat-card .stat-label {
		font-size: 12px;
		text-transform: uppercase;
		letter-spacing: 1px;
		opacity: 0.85;
		margin-bottom: 6px;
	}
	.stat-card .stat-value {
		font-size: 26px;
		font-weight: 700;
		line-height: 1.1;
	}
	.stat-card-green  { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
	.stat-card-amber  { background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%); }
	.stat-card-blue   { background: linear-gradient(135deg, #17a2b8 0%, #007bff 100%); }
	.stat-card-purple { background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); }
	/* Transaction row colors */
	.txn-ingreso { background-color: #f0fff4; }
	.txn-gasto   { background-color: #fff5f5; }

	/* FullCalendar event styling */
	.fc-event {
		cursor: pointer;
		font-size: 11px;
		line-height: 1.3;
		padding: 2px 4px;
		margin-bottom: 2px;
		border-radius: 3px;
		font-weight: 600;
		box-shadow: 0 1px 2px rgba(0,0,0,0.12);
		border: none !important;
	}
	.fc-event:hover {
		opacity: 0.92;
		filter: brightness(1.05);
	}
	.fc-day-number {
		font-weight: 700;
		color: #555;
	}
	.fc-today {
		background-color: #fcf8e3 !important;
	}
	.calendar-legend {
		margin-top: 10px;
		padding-top: 10px;
		border-top: 1px solid #eee;
		display: flex;
		justify-content: center;
		flex-wrap: wrap;
		gap: 8px;
	}
	.calendar-legend .label {
		font-size: 11px;
		padding: 4px 8px;
		font-weight: 600;
		display: inline-flex;
		align-items: center;
		gap: 4px;
	}
</style>

<!-- fullCalendar -->
<link rel="stylesheet" href="assests/plugins/fullcalendar/fullcalendar.min.css">
<link rel="stylesheet" href="assests/plugins/fullcalendar/fullcalendar.print.css" media="print">

<!-- ── Quota alert banner ───────────────────────────────────────────────── -->
<?php if ($showAlert): ?>
<div class="alert alert-<?php echo $quotaAlertClass; ?> alert-dismissible" role="alert">
	<button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
	<strong><i class="glyphicon glyphicon-warning-sign"></i> Atención:</strong>
	Has alcanzado el <strong><?php echo $quotaPct; ?>%</strong> de tu límite mensual de gasto
	(S/ <?php echo number_format((float)$spendLimit['monthly_limit'], 2); ?>).
	<?php if ($quotaAlertClass === 'danger'): ?>
		Tu gasto está en zona crítica.
	<?php else: ?>
		Considera revisar tus gastos.
	<?php endif; ?>
</div>
<?php endif; ?>

<!-- ── 4 Stat cards ─────────────────────────────────────────────────────── -->
<div class="row">
	<div class="col-xs-12 col-sm-6 col-md-3">
		<div class="stat-card stat-card-green">
			<i class="glyphicon glyphicon-usd stat-icon"></i>
			<div class="stat-label">Saldo Disponible</div>
			<div class="stat-value">S/ <?php echo number_format($saldoDisponible, 2); ?></div>
		</div>
	</div>
	<div class="col-xs-12 col-sm-6 col-md-3">
		<div class="stat-card stat-card-amber">
			<i class="glyphicon glyphicon-arrow-down stat-icon"></i>
			<div class="stat-label">Gastos del Mes</div>
			<div class="stat-value">S/ <?php echo number_format($totalGastosMes, 2); ?></div>
		</div>
	</div>
	<div class="col-xs-12 col-sm-6 col-md-3">
		<div class="stat-card stat-card-blue">
			<i class="glyphicon glyphicon-retweet stat-icon"></i>
			<div class="stat-label">Préstamos Pendientes</div>
			<div class="stat-value">S/ <?php echo number_format($totalPrestamosPendientes, 2); ?></div>
		</div>
	</div>
	<div class="col-xs-12 col-sm-6 col-md-3">
		<div class="stat-card stat-card-purple">
			<i class="glyphicon glyphicon-equalizer stat-icon"></i>
			<div class="stat-label">Balance Total</div>
			<div class="stat-value">
				S/ <?php echo number_format($balanceTotal, 2); ?>
			</div>
		</div>
	</div>
</div><!-- /.row stat cards -->

<!-- ── Main content row ─────────────────────────────────────────────────── -->
<div class="row">

	<!-- Left column: tables -->
	<div class="col-xs-12 col-md-6">

		<!-- Resumen de cuentas -->
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="glyphicon glyphicon-list-alt"></i> <strong>Resumen de Cuentas</strong>
			</div>
			<div class="panel-body" style="padding:0;">
				<?php if (empty($accounts)): ?>
					<p class="text-muted text-center" style="padding:15px;">No hay cuentas registradas.</p>
				<?php else: ?>
				<table class="table table-hover table-condensed" style="margin-bottom:0;">
					<thead>
						<tr>
							<th>Cuenta</th>
							<th>Tipo</th>
							<th class="text-right">Saldo</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($accounts as $acc): ?>
						<tr>
							<td><?php echo htmlspecialchars($acc['name'], ENT_QUOTES, 'UTF-8'); ?></td>
							<td><span class="label label-default"><?php echo htmlspecialchars($acc['account_type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
							<td class="text-right <?php echo (float)$acc['balance'] >= 0 ? 'text-success' : 'text-danger'; ?>">
								S/ <?php echo number_format((float)$acc['balance'], 2); ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
		</div><!-- /.panel accounts -->

		<!-- Movimientos recientes -->
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="glyphicon glyphicon-transfer"></i> <strong>Movimientos Recientes</strong>
				<small class="pull-right text-muted">Últimos 10</small>
			</div>
			<div class="panel-body" style="padding:0;">
				<?php if (empty($recentTxns)): ?>
					<p class="text-muted text-center" style="padding:15px;">No hay movimientos recientes.</p>
				<?php else: ?>
				<table class="table table-condensed" style="margin-bottom:0;">
					<thead>
						<tr>
							<th>Fecha</th>
							<th>Descripción</th>
							<th>Tipo</th>
							<th class="text-right">Monto</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($recentTxns as $txn): ?>
						<tr class="txn-<?php echo htmlspecialchars($txn['txn_type'], ENT_QUOTES, 'UTF-8'); ?>">
							<td><?php echo htmlspecialchars(date('d/m/Y', strtotime($txn['txn_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
							<td><?php echo htmlspecialchars($txn['description'], ENT_QUOTES, 'UTF-8'); ?></td>
							<td>
								<?php if ($txn['txn_type'] === 'ingreso'): ?>
									<span class="label label-success"><i class="glyphicon glyphicon-arrow-up"></i> Ingreso</span>
								<?php else: ?>
									<span class="label label-danger"><i class="glyphicon glyphicon-arrow-down"></i> Gasto</span>
								<?php endif; ?>
							</td>
							<td class="text-right <?php echo $txn['txn_type'] === 'ingreso' ? 'text-success' : 'text-danger'; ?>">
								<?php echo $txn['txn_type'] === 'ingreso' ? '+' : '-'; ?>S/ <?php echo number_format((float)$txn['amount'], 2); ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
		</div><!-- /.panel transactions -->

	</div><!-- /.col left -->

	<!-- Right column: calendar -->
	<div class="col-xs-12 col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="glyphicon glyphicon-calendar"></i> <strong>Calendario de Movimientos</strong>
			</div>
			<div class="panel-body">
				<div id="calendar"></div>
				<!-- Legend -->
				<div class="calendar-legend">
					<span class="label" style="background-color: #27ae60; color: #fff;"><i class="glyphicon glyphicon-arrow-up"></i> Ingresos</span>
					<span class="label" style="background-color: #d9534f; color: #fff;"><i class="glyphicon glyphicon-arrow-down"></i> Gastos</span>
					<span class="label" style="background-color: #f0ad4e; color: #fff;"><i class="glyphicon glyphicon-transfer"></i> Préstamos</span>
				</div>
			</div>
		</div>
	</div><!-- /.col calendar -->

</div><!-- /.row main -->

<!-- Modal Detalle de Evento del Calendario -->
<div class="modal fade" id="calendarEventModal" tabindex="-1" role="dialog" aria-labelledby="calModalTitle">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header" id="calModalHeader" style="border-top-left-radius: 4px; border-top-right-radius: 4px; padding: 12px 15px;">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true" style="color:#fff; opacity:0.8;">&times;</span></button>
				<h4 class="modal-title" id="calModalTitle" style="color: #fff; font-weight: 600; font-size: 16px;">
					<i id="calModalIcon" class="glyphicon"></i> <span id="calModalType">Detalle</span>
				</h4>
			</div>
			<div class="modal-body" style="font-size: 13px; padding: 15px;">
				<div class="text-center" style="margin-bottom: 12px;">
					<div id="calModalAmount" style="font-size: 24px; font-weight: 700; letter-spacing: -0.5px;"></div>
					<div id="calModalStatus" style="margin-top: 5px;"></div>
				</div>
				<table class="table table-condensed" style="margin-bottom: 0;">
					<tbody>
						<tr>
							<th style="width: 38%; color: #666; border-top: none;">Concepto:</th>
							<td id="calModalConcept" style="font-weight: 600; border-top: none;"></td>
						</tr>
						<tr>
							<th style="color: #666;">Fecha:</th>
							<td id="calModalDate"></td>
						</tr>
						<tr id="calModalAccountRow">
							<th style="color: #666;">Cuenta:</th>
							<td id="calModalAccount"></td>
						</tr>
						<tr id="calModalExtraRow">
							<th id="calModalExtraLabel" style="color: #666;">Detalle:</th>
							<td id="calModalExtraValue"></td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="modal-footer" style="padding: 10px 15px;">
				<a id="calModalActionBtn" href="#" class="btn btn-sm btn-primary">
					<i class="glyphicon glyphicon-share-alt"></i> Ver en módulo
				</a>
				<button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cerrar</button>
			</div>
		</div>
	</div>
</div>

<!-- fullCalendar scripts -->
<script src="assests/plugins/moment/moment.min.js"></script>
<script src="assests/plugins/fullcalendar/fullcalendar.js"></script>
<script type="text/javascript">
	$(function(){
		// Activate dashboard nav item
		$('#navDashboard').addClass('active');

		// Initialize FullCalendar with live financial events
		$('#calendar').fullCalendar({
			monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
			dayNamesShort: ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'],
			header: {
				left: '',
				center: 'title',
				right: 'prev,next today'
			},
			buttonText: {
				today: 'Hoy',
				month: 'Mes'
			},
			contentHeight: 460,
			eventLimit: 3,
			eventLimitText: 'más',
			events: 'php_action/fetchCalendarEvents.php',
			eventRender: function(event, element) {
				// Tooltip with complete transaction info
				var prefix = (event.type === 'ingreso' ? 'Ingreso: +' : (event.type === 'gasto' ? 'Gasto: -' : 'Préstamo: '));
				var tip = prefix + 'S/ ' + event.amount + ' - ' + event.concept;
				if (event.account && event.account !== 'No especificada') tip += ' [' + event.account + ']';
				if (event.borrower) tip += ' (Prestatario: ' + event.borrower + ')';

				element.attr('title', tip);
			},
			eventClick: function(event, jsEvent, view) {
				// Populate and show the details modal
				var headerBg = event.backgroundColor || '#337ab7';
				$('#calModalHeader').css('background-color', headerBg);
				$('#calModalType').text(event.typeLabel || 'Detalle');
				$('#calModalAmount')
					.text((event.type === 'ingreso' ? '+' : (event.type === 'gasto' ? '-' : '⇄ ')) + 'S/ ' + event.amount)
					.css('color', headerBg);

				var iconClass = 'glyphicon-usd';
				if (event.type === 'ingreso') iconClass = 'glyphicon-arrow-up';
				else if (event.type === 'gasto') iconClass = 'glyphicon-arrow-down';
				else if (event.type === 'prestamo') iconClass = 'glyphicon-transfer';
				$('#calModalIcon').attr('class', 'glyphicon ' + iconClass);

				var statusBadge = '<span class="label ' + (event.badgeClass || 'label-default') + '">' + (event.status || 'REGISTRADO') + '</span>';
				if (event.type === 'prestamo' && event.balancePending) {
					statusBadge += ' <span class="label label-danger">Pendiente: S/ ' + event.balancePending + '</span>';
				}
				$('#calModalStatus').html(statusBadge);

				$('#calModalConcept').text(event.concept || '-');
				$('#calModalDate').text(event.start ? event.start.format('YYYY-MM-DD') : '-');
				$('#calModalAccount').text(event.account || 'No especificada');

				if (event.type === 'prestamo') {
					$('#calModalExtraLabel').text('Prestatario:');
					$('#calModalExtraValue').text(event.borrower || '-');
					$('#calModalExtraRow').show();
				} else if (event.category && event.category !== 'Sin categoría') {
					$('#calModalExtraLabel').text('Categoría:');
					$('#calModalExtraValue').text(event.category + (event.project ? ' (' + event.project + ')' : ''));
					$('#calModalExtraRow').show();
				} else {
					$('#calModalExtraRow').hide();
				}

				$('#calModalActionBtn').attr('href', event.urlView || '#');
				$('#calendarEventModal').modal('show');
			},
			windowResize: function() {
				$('#calendar').fullCalendar('render');
			}
		});
	});
</script>

<?php require_once 'includes/footer.php'; ?>
