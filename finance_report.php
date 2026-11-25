<?php
if (!isset($_GET['classic'])) {
    header('Location: app.php#/reports');
    exit();
}
require_once 'includes/header.php';
?>

<?php
$userId       = (int)$_SESSION['userId'];
$currentMonth = date('Y-m');
$currentYear  = (int)date('Y');
list($monthStart, $monthEnd) = getMonthDateBounds($currentMonth);
list($yearStart, $yearEnd)   = getYearDateBounds($currentYear);
$sixMonthsAgo                = date('Y-m-01', strtotime('-5 months'));

$saldoDisponible        = 0.0;
$gastoMes               = 0.0;
$gastoAnio              = 0.0;
$totalPrestamoPendiente = 0.0;
$balanceTotal           = 0.0;
$totalActivos           = 0.0;
$patrimonioNeto         = 0.0;
$ingresosAnio           = 0.0;
$accList                = [];
$monthlyExpenses        = [];
$topCategories          = [];

// Single multi-result Stored Procedure call (replaces 9 separate queries)
$repSets = callProcedureMulti(
    $connect,
    "CALL sp_get_financial_report(?, ?, ?, ?, ?, ?)",
    "isssss",
    [$userId, $yearStart, $yearEnd, $monthStart, $monthEnd, $sixMonthsAgo]
);

if (!empty($repSets[0][0])) {
    $k = $repSets[0][0];
    $saldoDisponible        = (float)$k['saldo_disponible'];
    $gastoMes               = (float)$k['gasto_mes'];
    $gastoAnio              = (float)$k['gasto_anio'];
    $totalPrestamoPendiente = (float)$k['prestamos_pendientes'];
    $balanceTotal           = (float)$k['balance_total'];
    $totalActivos           = (float)$k['total_activos'];
    $patrimonioNeto         = (float)$k['patrimonio_neto'];
    $ingresosAnio           = (float)$k['ingresos_anio'];
}
if (!empty($repSets[1])) $accList         = $repSets[1];
if (!empty($repSets[2])) $monthlyExpenses = $repSets[2];
if (!empty($repSets[3])) $topCategories   = $repSets[3];
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
