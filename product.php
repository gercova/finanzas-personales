<?php
if (!isset($_GET['classic'])) {
    header('Location: app.php#/products');
    exit();
}
require_once 'php_action/db_connect.php';
require_once 'includes/header.php';
?>
<?php require_once 'modal/productModal.php'; ?>
<div class="row">
	<div class="col-xs-12 col-md-12">
		<ol class="breadcrumb">
			<li><a href="dashboard.php">Inicio</a></li>		  
			<li class="active">Productos</li>
		</ol>
		<div class="panel panel-default">
			<div class="panel-heading panel-heading-flex" style="display: flex; justify-content: space-between; align-items: center; min-height: 52px; padding: 8px 15px;">
				<div class="page-heading" style="margin: 0; font-size: 16px; font-weight: 600; display: flex; align-items: center;">
					<i class="glyphicon glyphicon-edit" style="margin-right: 8px;"></i> Listado de productos
				</div>
				<button class="btn btn-default button1" data-toggle="modal" id="addProductModalBtn" data-target="#addProductModal" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
					<i class="glyphicon glyphicon-plus-sign"></i> Agregar producto
				</button>
			</div> <!-- /panel-heading -->
			<div class="panel-body">
				<div class="remove-messages"></div>
				<div class="table-responsive">
					<table class="table" id="manageProductTable" style="width:100%;">
						<thead>
							<tr>
								<th style="width:10%;">Imagen</th>							
								<th>Nombre del producto</th>
								<th>Precio</th>							
								<th>Stock</th>
								<th>Fabricante</th>
								<th>Categoría</th>
								<th>Estado</th>
								<th style="width:15%;">Opciones</th>
							</tr>
						</thead>
					</table>
				</div>
				<!-- /table -->
			</div> <!-- /panel-body -->
		</div> <!-- /panel -->		
	</div> <!-- /col-xs-12 col-md-12 -->
</div> <!-- /row -->
<script src="custom/js/product.js"></script>
<?php require_once 'includes/footer.php'; ?>