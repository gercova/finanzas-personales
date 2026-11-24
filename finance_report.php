<?php require_once 'includes/header.php'; ?>

<?php
$userId = (int)$_SESSION['userId'];

// ── 1. Saldo Disponible ────────────────────────────────────────────────────
$stmtSaldo = $connect->prepare("SELECT COALESCE(SUM(current_balance),0) AS total FROM accounts WHERE is_active=1");
$saldoDisponible = 0;
if ($stmtSaldo) { $stmtSaldo->execute(); $saldoDisponible = (float)$stmtSaldo->get_result()->fetch_assoc()['total']; $stmtSaldo->close(); }

// ── 2. Total Gastos (mes actual y año) ───────────────────────────────────
$currentMonth = date('Y-m');
$currentYear  = date('Y');

$stmtGastoMes = $connect->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=? AND created_by=?");
$gastoMes = 0;
if ($stmtGastoMes) { $stmtGastoMes->bind_param("si",$currentMonth,$userId); $stmtGastoMes->execute(); $gastoMes=(float)$stmtGastoMes->get_result()->fetch_assoc()['total']; $stmtGastoMes->close(); }

$stmtGastoAnio = $connect->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE YEAR(expense_date)=? AND created_by=?");
$gastoAnio = 0;
if ($stmtGastoAnio) { $stmtGastoAnio->bind_param("ii",$currentYear,$userId); $stmtGastoAnio->execute(); $gastoAnio=(float)$stmtGastoAnio->get_result()->fetch_assoc()['total']; $stmtGastoAnio->close(); }

// ── 3. Total Préstamos Pendientes ─────────────────────────────────────────
$stmtPrest = $connect->prepare("SELECT COALESCE(SUM(balance_pending),0) AS total FROM loans WHERE status IN ('PENDIENTE','PARCIAL') AND created_by=?");
$totalPrestamoPendiente = 0;
if ($stmtPrest) { $stmtPrest->bind_param("i",$userId); $stmtPrest->execute(); $totalPrestamoPendiente=(float)$stmtPrest->get_result()->fetch_assoc()['total']; $stmtPrest->close(); }

// ── 4. Balance Total ─────────────────────────────────────────────────────
$balanceTotal = $saldoDisponible + $totalPrestamoPendiente;

// ── 5. Patrimonio Neto (Balance + Activos ACTIVO) ─────────────────────────
$stmtActivos = $connect->prepare("SELECT COALESCE(SUM(estimated_value),0) AS total FROM assets WHERE status='ACTIVO' AND created_by=?");
$totalActivos = 0;
if ($stmtActivos) { $stmtActivos->bind_param("i",$userId); $stmtActivos->execute(); $totalActivos=(float)$stmtActivos->get_result()->fetch_assoc()['total']; $stmtActivos->close(); }

$patrimonioNeto = $balanceTotal + $totalActivos;

// ── 6. Total Ingresos Año ────────────────────────────────────────────────
$stmtIngAnio = $connect->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM incomes WHERE YEAR(income_date)=? AND created_by=?");
$ingresosAnio = 0;
if ($stmtIngAnio) { $stmtIngAnio->bind_param("ii",$currentYear,$userId); $stmtIngAnio->execute(); $ingresosAnio=(float)$stmtIngAnio->get_result()->fetch_assoc()['total']; $stmtIngAnio->close(); }

// ── 7. Accounts summary ──────────────────────────────────────────────────
$stmtAccs = $connect->prepare("SELECT account_name, account_type, current_balance, currency FROM accounts WHERE is_active=1 ORDER BY current_balance DESC");
$accList = [];
if ($stmtAccs) { $stmtAccs->execute(); $r=$stmtAccs->get_result(); while($row=$r->fetch_assoc()) $accList[]=$row; $stmtAccs->close(); }

// ── 8. Monthly expenses — last 6 months ─────────────────────────────────
$stmtMonthly = $connect->prepare("
    SELECT DATE_FORMAT(expense_date,'%Y-%m') AS mes,
           COALESCE(SUM(amount),0) AS total
    FROM expenses
    WHERE created_by=? AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mes ORDER BY mes ASC
");
$monthlyExpenses = [];
if ($stmtMonthly) { $stmtMonthly->bind_param("i",$userId); $stmtMonthly->execute(); $r=$stmtMonthly->get_result(); while($row=$r->fetch_assoc()) $monthlyExpenses[]=$row; $stmtMonthly->close(); }

// ── 9. Top 5 expense categories this year ───────────────────────────────
$stmtTopCat = $connect->prepare("
    SELECT fc.category_name, COALESCE(SUM(e.amount),0) AS total
    FROM expenses e
    LEFT JOIN financial_categories fc ON e.category_id=fc.id
    WHERE e.created_by=? AND YEAR(e.expense_date)=?
    GROUP BY fc.category_name ORDER BY total DESC LIMIT 5
");
$topCategories = [];
if ($stmtTopCat) { $stmtTopCat->bind_param("ii",$userId,$currentYear); $stmtTopCat->execute(); $r=$stmtTopCat->get_result(); while($row=$r->fetch_assoc()) $topCategories[]=$row; $stmtTopCat->close(); }
?>

<div class="row">
  <div class="col-xs-12">
    <ol class="breadcrumb">
      <li><a href="dashboard.php">Inicio</a></li>
      <li class="active">Reporte Financiero</li>
    </ol>

    <!-- ── Main Cards Row ── -->
    <div class="row" style="margin-bottom:20px;">

      <!-- Saldo Disponible -->
      <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="panel" style="border-top:4px solid #27ae60;">
          <div class="panel-body text-center" style="padding:20px 15px;">
            <div style="font-size:13px;color:#888;margin-bottom:4px;"><i class="glyphicon glyphicon-piggy-bank"></i> SALDO DISPONIBLE</div>
            <h3 style="margin:0;font-weight:700;color:#27ae60;">S/ <?php echo number_format($saldoDisponible,2); ?></h3>
            <small class="text-muted">suma de cuentas activas</small>
          </div>
        </div>
      </div>

      <!-- Total Gastos Año -->
      <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="panel" style="border-top:4px solid #e74c3c;">
          <div class="panel-body text-center" style="padding:20px 15px;">
            <div style="font-size:13px;color:#888;margin-bottom:4px;"><i class="glyphicon glyphicon-arrow-down"></i> TOTAL GASTOS <?php echo $currentYear; ?></div>
            <h3 style="margin:0;font-weight:700;color:#e74c3c;">S/ <?php echo number_format($gastoAnio,2); ?></h3>
            <small class="text-muted">mes actual: S/ <?php echo number_format($gastoMes,2); ?></small>
          </div>
        </div>
      </div>

      <!-- Total Préstamos Pendientes -->
      <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="panel" style="border-top:4px solid #f39c12;">
          <div class="panel-body text-center" style="padding:20px 15px;">
            <div style="font-size:13px;color:#888;margin-bottom:4px;"><i class="glyphicon glyphicon-share-alt"></i> PRÉSTAMOS PENDIENTES</div>
            <h3 style="margin:0;font-weight:700;color:#f39c12;">S/ <?php echo number_format($totalPrestamoPendiente,2); ?></h3>
            <small class="text-muted">por cobrar</small>
          </div>
        </div>
      </div>

      <!-- Balance Total -->
      <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="panel" style="border-top:4px solid #2980b9;">
          <div class="panel-body text-center" style="padding:20px 15px;">
            <div style="font-size:13px;color:#888;margin-bottom:4px;"><i class="glyphicon glyphicon-equalizer"></i> BALANCE TOTAL</div>
            <h3 style="margin:0;font-weight:700;color:#2980b9;">S/ <?php echo number_format($balanceTotal,2); ?></h3>
            <small class="text-muted">saldo + préstamos</small>
          </div>
        </div>
      </div>

    </div><!-- /main cards -->

    <!-- ── Secondary Cards ── -->
    <div class="row" style="margin-bottom:20px;">
      <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="panel" style="border-top:4px solid #8e44ad;">
          <div class="panel-body text-center" style="padding:16px;">
            <div style="font-size:13px;color:#888;margin-bottom:4px;"><i class="glyphicon glyphicon-home"></i> PATRIMONIO NETO</div>
            <h3 style="margin:0;font-weight:700;color:#8e44ad;">S/ <?php echo number_format($patrimonioNeto,2); ?></h3>
            <small class="text-muted">balance + activos (S/ <?php echo number_format($totalActivos,2); ?>)</small>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="panel" style="border-top:4px solid #16a085;">
          <div class="panel-body text-center" style="padding:16px;">
            <div style="font-size:13px;color:#888;margin-bottom:4px;"><i class="glyphicon glyphicon-arrow-up"></i> INGRESOS <?php echo $currentYear; ?></div>
            <h3 style="margin:0;font-weight:700;color:#16a085;">S/ <?php echo number_format($ingresosAnio,2); ?></h3>
            <small class="text-muted">total año</small>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="panel" style="border-top:4px solid <?php echo ($ingresosAnio - $gastoAnio) >= 0 ? '#27ae60' : '#e74c3c'; ?>;">
          <div class="panel-body text-center" style="padding:16px;">
            <div style="font-size:13px;color:#888;margin-bottom:4px;"><i class="glyphicon glyphicon-stats"></i> AHORRO NETO <?php echo $currentYear; ?></div>
            <?php $ahorro = $ingresosAnio - $gastoAnio; ?>
            <h3 style="margin:0;font-weight:700;color:<?php echo $ahorro >= 0 ? '#27ae60' : '#e74c3c'; ?>;">
              S/ <?php echo number_format($ahorro,2); ?>
            </h3>
            <small class="text-muted">ingresos - gastos</small>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Accounts Summary -->
      <div class="col-xs-12 col-md-6">
        <div class="panel panel-default">
          <div class="panel-heading"><i class="glyphicon glyphicon-briefcase"></i> Resumen de Cuentas</div>
          <div class="panel-body" style="padding:0;">
            <table class="table table-hover table-condensed" style="margin:0;">
              <thead><tr><th>Cuenta</th><th>Tipo</th><th>Moneda</th><th class="text-right">Saldo</th></tr></thead>
              <tbody>
              <?php foreach ($accList as $acc): ?>
                <tr>
                  <td><?php echo htmlspecialchars($acc['account_name']); ?></td>
                  <td><?php echo htmlspecialchars($acc['account_type']); ?></td>
                  <td><?php echo htmlspecialchars($acc['currency']); ?></td>
                  <td class="text-right <?php echo (float)$acc['current_balance'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                    <strong>S/ <?php echo number_format((float)$acc['current_balance'],2); ?></strong>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr style="background:#f5f5f5;">
                  <td colspan="3"><strong>TOTAL</strong></td>
                  <td class="text-right"><strong>S/ <?php echo number_format($saldoDisponible,2); ?></strong></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- Top Categories -->
      <div class="col-xs-12 col-md-6">
        <div class="panel panel-default">
          <div class="panel-heading"><i class="glyphicon glyphicon-tags"></i> Top 5 Categorías de Gasto (<?php echo $currentYear; ?>)</div>
          <div class="panel-body" style="padding:0;">
            <table class="table table-hover table-condensed" style="margin:0;">
              <thead><tr><th>#</th><th>Categoría</th><th class="text-right">Total</th></tr></thead>
              <tbody>
              <?php $i=1; foreach ($topCategories as $cat): ?>
                <tr>
                  <td><?php echo $i++; ?></td>
                  <td><?php echo htmlspecialchars($cat['category_name'] ?? 'Sin categoría'); ?></td>
                  <td class="text-right text-danger"><strong>S/ <?php echo number_format((float)$cat['total'],2); ?></strong></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div><!-- /row -->

    <!-- Monthly Expense Trend -->
    <div class="panel panel-default">
      <div class="panel-heading"><i class="glyphicon glyphicon-stats"></i> Tendencia de Gastos — Últimos 6 Meses</div>
      <div class="panel-body" style="padding:0;">
        <table class="table table-hover table-condensed" style="margin:0;">
          <thead><tr><th>Mes</th><th class="text-right">Total Gastos</th><th>Barra</th></tr></thead>
          <tbody>
          <?php
          $maxMonthly = !empty($monthlyExpenses) ? max(array_column($monthlyExpenses,'total')) : 1;
          foreach ($monthlyExpenses as $m):
              $pct = $maxMonthly > 0 ? round(((float)$m['total'] / $maxMonthly) * 100) : 0;
              $barClass = $pct >= 90 ? 'danger' : ($pct >= 60 ? 'warning' : 'success');
          ?>
            <tr>
              <td><?php echo date('F Y', mktime(0,0,0,substr($m['mes'],5,2),1,substr($m['mes'],0,4))); ?></td>
              <td class="text-right"><strong>S/ <?php echo number_format((float)$m['total'],2); ?></strong></td>
              <td style="min-width:200px;">
                <div class="progress" style="margin:0;height:12px;">
                  <div class="progress-bar progress-bar-<?php echo $barClass; ?>" style="width:<?php echo $pct; ?>%;min-width:2px;"></div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Print Button -->
    <div class="text-right" style="margin-bottom:20px;">
      <button onclick="window.print();" class="btn btn-default">
        <i class="glyphicon glyphicon-print"></i> Imprimir / Exportar PDF
      </button>
    </div>

  </div><!-- /col -->
</div><!-- /row -->

<?php require_once 'includes/footer.php'; ?>
