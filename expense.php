<?php require_once 'includes/header.php'; ?>
<?php require_once 'modal/expenseModal.php'; ?>

<?php
// ── Spending Quota Alert ─────────────────────────────────────────────────────
$currentMonth = date('Y-m');
$pageSpendLimit = null;
$stmtSL = $connect->prepare(
    "SELECT monthly_limit, warning_threshold, critical_threshold FROM spending_limits WHERE month_year = ?"
);
if ($stmtSL) {
    $stmtSL->bind_param("s", $currentMonth);
    $stmtSL->execute();
    $pageSpendLimit = $stmtSL->get_result()->fetch_assoc();
    $stmtSL->close();
}
$pageMonthlySpent = 0;
$stmtMS = $connect->prepare(
    "SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m') = ? AND created_by = ?"
);
if ($stmtMS) {
    $stmtMS->bind_param("si", $currentMonth, $_SESSION['userId']);
    $stmtMS->execute();
    $rowMS = $stmtMS->get_result()->fetch_assoc();
    $pageMonthlySpent = (float)$rowMS['total'];
    $stmtMS->close();
}
$pageQuotaPct = 0;
$pageQuotaBarClass = 'success';
$pageAlertClass = '';
if ($pageSpendLimit && (float)$pageSpendLimit['monthly_limit'] > 0) {
    $pageQuotaPct = round(($pageMonthlySpent / (float)$pageSpendLimit['monthly_limit']) * 100, 1);
    $warn = isset($pageSpendLimit['warning_threshold'])  ? (float)$pageSpendLimit['warning_threshold']  : 75;
    $crit = isset($pageSpendLimit['critical_threshold']) ? (float)$pageSpendLimit['critical_threshold'] : 90;
    if ($pageQuotaPct >= $crit) {
        $pageQuotaBarClass = 'danger';
        $pageAlertClass    = 'alert-danger';
    } elseif ($pageQuotaPct >= $warn) {
        $pageQuotaBarClass = 'warning';
        $pageAlertClass    = 'alert-warning';
    } else {
        $pageQuotaBarClass = 'success';
        $pageAlertClass    = '';
    }
}
$pageQuotaPctCapped = min($pageQuotaPct, 100);
?>

<div class="row">
    <div class="col-xs-12 col-md-12">

        <ol class="breadcrumb">
            <li><a href="dashboard.php">Inicio</a></li>
            <li class="active">Gastos</li>
        </ol>

        <?php if ($pageSpendLimit): ?>
        <!-- Spending Quota Alert Banner -->
        <div class="<?php echo $pageAlertClass ? 'alert ' . $pageAlertClass : 'alert alert-info'; ?>" style="margin-bottom:15px;">
            <strong><i class="glyphicon glyphicon-stats"></i> Límite mensual de gastos</strong>
            &mdash; Has gastado <strong>$<?php echo number_format($pageMonthlySpent, 2); ?></strong>
            de <strong>$<?php echo number_format((float)$pageSpendLimit['monthly_limit'], 2); ?></strong>
            (<?php echo $pageQuotaPct; ?>%)
            <div class="progress" style="margin-top:8px; margin-bottom:0; height:10px;">
                <div class="progress-bar progress-bar-<?php echo $pageQuotaBarClass; ?>"
                     role="progressbar"
                     aria-valuenow="<?php echo $pageQuotaPctCapped; ?>"
                     aria-valuemin="0" aria-valuemax="100"
                     style="width:<?php echo $pageQuotaPctCapped; ?>%; min-width:2px;">
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div id="quota-alert-container"></div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="glyphicon glyphicon-arrow-down text-danger"></i> Gestión de Gastos
                </div>
            </div><!-- /panel-heading -->
            <div class="panel-body">
                <div class="remove-messages"></div>
                <div class="div-action pull pull-right" style="padding-bottom:20px;">
                    <button class="btn btn-danger" data-toggle="modal" id="addExpenseModalBtn" data-target="#addExpenseModal">
                        <i class="glyphicon glyphicon-plus-sign"></i> Registrar Gasto
                    </button>
                </div><!-- /div-action -->
                <div class="table-responsive">
                    <table class="table table-hover" id="manageExpenseTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th style="width:4%;">#</th>
                                <th>Concepto</th>
                                <th>Cuenta</th>
                                <th>Categoría</th>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Comprobante</th>
                                <th>Estado</th>
                                <th style="width:12%;">Acción</th>
                            </tr>
                        </thead>
                    </table>
                </div><!-- /table-responsive -->
            </div><!-- /panel-body -->
        </div><!-- /panel -->

    </div><!-- /col -->
</div><!-- /row -->

<script src="custom/js/expense.js"></script>
<?php require_once 'includes/footer.php'; ?>
