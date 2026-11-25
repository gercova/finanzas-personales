<?php require_once 'includes/header.php'; ?>
<?php
// ── Summary stats via optimized View v_user_financial_summary (1 query instead of 3) ──
$userId      = (int)$_SESSION['userId'];
$rowTotal    = ['cnt' => 0, 'total_val' => 0];
$rowGarantia = ['cnt' => 0];
$rowAll      = ['cnt' => 0];

$stSum = $connect->prepare("
    SELECT activos_activos_cnt, activos_valor, activos_garantia_cnt, activos_total_cnt
    FROM v_user_financial_summary
    WHERE user_id = ?
");
if ($stSum) {
    $stSum->bind_param("i", $userId);
    $stSum->execute();
    if ($r = $stSum->get_result()->fetch_assoc()) {
        $rowTotal    = ['cnt' => (int)$r['activos_activos_cnt'], 'total_val' => (float)$r['activos_valor']];
        $rowGarantia = ['cnt' => (int)$r['activos_garantia_cnt']];
        $rowAll      = ['cnt' => (int)$r['activos_total_cnt']];
    }
    $stSum->close();
}
?>

<!-- Page header -->
<div class="row">
    <div class="col-xs-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Inicio</a></li>
            <li class="active">Activos Valiosos</li>
        </ol>
    </div>
</div>

<!-- Stat cards -->
<div class="row">
    <div class="col-xs-12 col-sm-4">
        <div class="panel panel-default text-center">
            <div class="panel-body">
                <h2 class="text-primary"><?php echo (int)$rowAll['cnt']; ?></h2>
                <p><i class="glyphicon glyphicon-certificate"></i> Total de Activos</p>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-sm-4">
        <div class="panel panel-default text-center">
            <div class="panel-body">
                <h2 class="text-success">S/ <?php echo number_format((float)$rowTotal['total_val'], 2); ?></h2>
                <p><i class="glyphicon glyphicon-usd"></i> Valor Total Estimado (Activos)</p>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-sm-4">
        <div class="panel panel-default text-center">
            <div class="panel-body">
                <h2 class="text-warning"><?php echo (int)$rowGarantia['cnt']; ?></h2>
                <p><i class="glyphicon glyphicon-lock"></i> Activos en Garantía</p>
            </div>
        </div>
    </div>
</div>

<!-- Table panel -->
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading panel-heading-flex" style="display: flex; justify-content: space-between; align-items: center; min-height: 52px; padding: 8px 15px;">
                <div class="page-heading" style="margin: 0; font-size: 16px; font-weight: 600; display: flex; align-items: center;">
                    <i class="glyphicon glyphicon-certificate" style="color:#8e44ad; margin-right: 8px;"></i> Registro de Activos Valiosos
                </div>
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addAssetModal" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="glyphicon glyphicon-plus-sign"></i> Registrar Activo
                </button>
            </div>
            <div class="panel-body table-responsive">
                <table id="assetsTable" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Valor Estimado</th>
                            <th>Precio Compra</th>
                            <th>Fecha Compra</th>
                            <th>N° Documento</th>
                            <th>Estado</th>
                            <th>Imagen</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'modal/assetModal.php'; ?>

<!-- DataTables + FilInput JS -->
<script src="assests/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assests/plugins/datatables/extensions/Responsive/js/dataTables.responsive.min.js"></script>
<script src="assests/plugins/fileinput/js/fileinput.min.js"></script>
<script src="custom/js/assets.js"></script>
<script>$('#navAssets').addClass('active');</script>

<?php require_once 'includes/footer.php'; ?>
