<?php require_once 'includes/header.php'; ?>

<?php 
	$sql = "SELECT * FROM product WHERE status = 1";
	$query = $connect->query($sql);
	$countProduct = $query ? $query->num_rows : 0;

	$orderSql = "SELECT * FROM orders WHERE order_status = 1";
	$orderQuery = $connect->query($orderSql);
	$countOrder = $orderQuery ? $orderQuery->num_rows : 0;

	$totalRevenue = 0;
	if($orderQuery){
		while($orderResult = $orderQuery->fetch_assoc()){
			$totalRevenue += (float)$orderResult['paid'];
		}
	}

	$lowStockThreshold = (int)env('LOW_STOCK_THRESHOLD', 3);
	$lowStockSql = "SELECT * FROM product WHERE quantity <= {$lowStockThreshold} AND status = 1";
	$lowStockQuery = $connect->query($lowStockSql);
	$countLowStock = $lowStockQuery ? $lowStockQuery->num_rows : 0;
	$connect->close();
?>

<style type="text/css">
	.ui-datepicker-calendar {
		display: none;
	}
</style>
<!-- fullCalendar 2.2.5-->
    <link rel="stylesheet" href="assests/plugins/fullcalendar/fullcalendar.min.css">
    <link rel="stylesheet" href="assests/plugins/fullcalendar/fullcalendar.print.css" media="print">
<div class="row">
	<div class="col-xs-12 col-sm-4 col-md-4">
		<div class="panel panel-success dashboard-widget">
			<div class="panel-heading">
				<a href="product.php" style="text-decoration:none;color:black;">
					Total de productos
					<span class="badge pull pull-right"><?php echo $countProduct; ?></span>	
				</a>
			</div> <!--/panel-heading-->
		</div> <!--/panel-->
	</div> <!--/col-->
	<div class="col-xs-12 col-sm-4 col-md-4">
		<div class="panel panel-info dashboard-widget">
			<div class="panel-heading">
				<a href="orders.php?o=manord" style="text-decoration:none;color:black;">
					Total ordenes
					<span class="badge pull pull-right"><?php echo $countOrder; ?></span>
				</a>
			</div><!--/panel-heading-->
		</div><!--/panel-->
	</div><!--/col-->
	<div class="col-xs-12 col-sm-4 col-md-4">
		<div class="panel panel-danger dashboard-widget">
			<div class="panel-heading">
				<a href="product.php" style="text-decoration:none;color:black;">
					Inventario bajo (≤<?php echo $lowStockThreshold; ?>)
					<span class="badge pull pull-right"><?php echo $countLowStock; ?></span>	
				</a>
			</div><!--/panel-heading-->
		</div><!--/panel-->
	</div><!--/col-->
	<div class="col-xs-12 col-sm-4 col-md-4">
		<div class="card">
			<div class="cardHeader">
		    	<h1><?php echo date('d'); ?></h1>
		  	</div>

		  	<div class="cardContainer">
		    	<p><?php echo date('l') .' '.date('d').', '.date('Y'); ?></p>
		  	</div>
		</div> 
		<br>
		<div class="card">
		  	<div class="cardHeader" style="background-color:#245580;">
		    	<h1>
		    		<?php 
		    			if($totalRevenue){echo number_format($totalRevenue,2);}
		    			else{echo '0.00';} 
		    		?>		
		    	</h1>
		  	</div>
		  	<div class="cardContainer">
		    	<p><i class="glyphicon glyphicon-usd"></i> Ingresos totales</p>
		  	</div>
		</div> 
	</div>
	<div class="col-xs-12 col-sm-8 col-md-8">
		<div class="panel panel-default">
			<div class="panel-heading"><i class="glyphicon glyphicon-calendar"></i> Calendario</div>
			<div class="panel-body">
				<div id="calendar"></div>
			</div>	
		</div>
	</div>
</div> <!--/row-->
<!-- fullCalendar 2.2.5 -->
<script src="assests/plugins/moment/moment.min.js"></script>
<script src="assests/plugins/fullcalendar/fullcalendar.js"></script>
<script type="text/javascript">
	$(function(){
		// top bar active
		$('#navDashboard').addClass('active');
      	//Date for the calendar events (dummy data)
      	var date = new Date();
      	var d = date.getDate(),
      	m = date.getMonth(),
      	y = date.getFullYear();

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
        	windowResize: function(view) {
        		$('#calendar').fullCalendar('render');
        	}      
    	});
    });
</script>
<?php require_once 'includes/footer.php'; ?>