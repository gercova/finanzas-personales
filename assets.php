<?php require_once 'includes/header.php'; ?>
<?php
// ── Summary stats ────────────────────────────────────────────────────────────
$userId = (int)$_SESSION['userId'];

$stTotal = $connect->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(estimated_value),0) as total_val FROM assets WHERE created_by = ? AND status = 'ACTIVO'");
$stTotal->bind_param("i", $userId); $stTotal->execute();
$rowTotal = $stTotal->get_result()->fetch_assoc(); $stTotal->close();

$stGarantia = $connect->prepare("SELECT COUNT(*) as cnt FROM assets WHERE created_by = ? AND status = 'EN_GARANTIA'");
$stGarantia->bind_param("i", $userId); $stGarantia->execute();
$rowGarantia = $stGarantia->get_result()->fetch_assoc(); $stGarantia->close();

$stAll = $connect->prepare("SELECT COUNT(*) as cnt FROM assets WHERE created_by = ?");
$stAll->bind_param("i", $userId); $stAll->execute();
$rowAll = $stAll->get_result()->fetch_assoc(); $stAll->close();
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
            <div class="panel-heading">
                <h3 class="panel-title"><i class="glyphicon glyphicon-list"></i> Registro de Activos Valiosos</h3>
                <div class="pull-right" style="margin-top:-5px;">
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addAssetModal">
                        <i class="glyphicon glyphicon-plus"></i> Registrar Activo
                    </button>
                </div>
                <div class="clearfix"></div>
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
