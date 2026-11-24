<?php
require_once 'includes/header.php';

// Load loans data for stat cards
$stmtTotalPrestado = $connect->prepare(
    "SELECT COALESCE(SUM(principal_amount),0) AS total FROM loans WHERE status IN ('PENDIENTE','PARCIAL') AND created_by=?"
);
$totalPrestado = 0;
if ($stmtTotalPrestado) {
    $stmtTotalPrestado->bind_param("i", $_SESSION['userId']);
    $stmtTotalPrestado->execute();
    $totalPrestado = (float)$stmtTotalPrestado->get_result()->fetch_assoc()['total'];
    $stmtTotalPrestado->close();
}

$stmtTotalCobrado = $connect->prepare(
    "SELECT COALESCE(SUM(amount_repaid),0) AS total FROM loans WHERE created_by=?"
);
$totalCobrado = 0;
if ($stmtTotalCobrado) {
    $stmtTotalCobrado->bind_param("i", $_SESSION['userId']);
    $stmtTotalCobrado->execute();
    $totalCobrado = (float)$stmtTotalCobrado->get_result()->fetch_assoc()['total'];
    $stmtTotalCobrado->close();
}

$stmtTotalPendiente = $connect->prepare(
    "SELECT COALESCE(SUM(balance_pending),0) AS total FROM loans WHERE status IN ('PENDIENTE','PARCIAL') AND created_by=?"
);
$totalPendiente = 0;
if ($stmtTotalPendiente) {
    $stmtTotalPendiente->bind_param("i", $_SESSION['userId']);
    $stmtTotalPendiente->execute();
    $totalPendiente = (float)$stmtTotalPendiente->get_result()->fetch_assoc()['total'];
    $stmtTotalPendiente->close();
}

// Accounts
$loanAccounts = [];
$stmtLA = $connect->prepare("SELECT account_id AS id, account_name, account_type FROM accounts WHERE status=1 ORDER BY account_name");
if ($stmtLA) { $stmtLA->execute(); $r=$stmtLA->get_result(); while($row=$r->fetch_assoc()) $loanAccounts[]=$row; $stmtLA->close(); }
?>
<?php require_once 'modal/loanModal.php'; ?>

<div class="row">
  <div class="col-xs-12">
    <ol class="breadcrumb">
      <li><a href="dashboard.php">Inicio</a></li>
      <li class="active">Préstamos</li>
    </ol>

    <!-- Stat Cards -->
    <div class="row" style="margin-bottom:20px;">
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-danger">
          <div class="panel-heading"><i class="glyphicon glyphicon-share-alt"></i> Total Prestado</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-danger">S/ <?php echo number_format($totalPrestado, 2); ?></h3>
            <small class="text-muted">préstamos activos</small>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-success">
          <div class="panel-heading"><i class="glyphicon glyphicon-ok-sign"></i> Total Cobrado</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-success">S/ <?php echo number_format($totalCobrado, 2); ?></h3>
            <small class="text-muted">recuperado</small>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-warning">
          <div class="panel-heading"><i class="glyphicon glyphicon-time"></i> Total Pendiente</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-warning">S/ <?php echo number_format($totalPendiente, 2); ?></h3>
            <small class="text-muted">por cobrar</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div style="margin-bottom:12px;">
      <span class="label" style="background:#e74c3c;padding:4px 10px;">PENDIENTE</span>&nbsp;
      <span class="label" style="background:#f39c12;padding:4px 10px;">PARCIAL</span>&nbsp;
      <span class="label" style="background:#8e44ad;padding:4px 10px;">PAGADO</span>
    </div>

    <div class="panel panel-default">
      <div class="panel-heading panel-heading-flex" style="display: flex; justify-content: space-between; align-items: center; min-height: 52px; padding: 8px 15px;">
        <div class="page-heading" style="margin: 0; font-size: 16px; font-weight: 600; display: flex; align-items: center;">
          <i class="glyphicon glyphicon-share-alt text-danger" style="margin-right: 8px;"></i> Gestión de Préstamos
        </div>
        <button class="btn btn-danger" data-toggle="modal" data-target="#addLoanModal" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
          <i class="glyphicon glyphicon-plus-sign"></i> Registrar Préstamo
        </button>
      </div>
      <div class="panel-body">
        <div class="remove-messages"></div>
        <div class="table-responsive">
          <table class="table table-hover" id="manageLoanTable" style="width:100%;">
            <thead>
              <tr>
                <th style="width:4%;">#</th>
                <th>Prestatario</th>
                <th>Concepto</th>
                <th>Cuenta</th>
                <th>Fecha Préstamo</th>
                <th>Vencimiento</th>
                <th>Capital</th>
                <th>Interés</th>
                <th>Total a Cobrar</th>
                <th>Cobrado</th>
                <th>Pendiente</th>
                <th>Estado</th>
                <th style="width:12%;">Acción</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
var loanAccounts = <?php echo json_encode($loanAccounts); ?>;
</script>
<script src="custom/js/loans.js"></script>
<?php require_once 'includes/footer.php'; ?>
