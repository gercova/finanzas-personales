<?php
/**
 * app.php — SPA shell page.
 * Serves the Vue 3 SPA inside the existing Bootstrap 3 layout.
 * Powered by Vue 3, Vite, Pinia, and Axios.
 */
require_once 'php_action/core.php';
require_once 'php_action/security.php';

$userId       = (int)$_SESSION['userId'];
$currentMonth = date('Y-m');
list($monthStart, $monthEnd) = getMonthDateBounds($currentMonth);

// Header metrics (same stored procedure the header uses)
$monthIncome   = 0.0;
$todayExpenses = 0.0;
$todayDate     = date('Y-m-d');
$hmSets = callProcedureMulti($connect, "CALL sp_get_header_metrics(?, ?, ?, ?)", "isss", [$userId, $monthStart, $monthEnd, $todayDate]);
if (!empty($hmSets[0][0])) {
    $monthIncome   = (float)$hmSets[0][0]['month_income'];
    $todayExpenses = (float)$hmSets[0][0]['today_expenses'];
}

// Lists for Vue forms (injected as window globals)
$accounts = $finCategories = $projects = [];
$stmtAcc = $connect->prepare("SELECT account_id AS id, account_name, account_type FROM accounts WHERE created_by=? AND status=1 AND is_deleted=0 ORDER BY account_name");
if ($stmtAcc) { $stmtAcc->bind_param("i", $userId); $stmtAcc->execute(); $r=$stmtAcc->get_result(); while($row=$r->fetch_assoc()) $accounts[]=$row; $stmtAcc->close(); }

$stmtCatI = $connect->prepare("SELECT category_id AS id, category_name FROM financial_categories WHERE created_by=? AND category_type='INCOME' AND status=1 AND is_deleted=0 ORDER BY category_name");
$incomeCategories = [];
if ($stmtCatI) { $stmtCatI->bind_param("i", $userId); $stmtCatI->execute(); $r=$stmtCatI->get_result(); while($row=$r->fetch_assoc()) $incomeCategories[]=$row; $stmtCatI->close(); }

$stmtCatE = $connect->prepare("SELECT category_id AS id, category_name FROM financial_categories WHERE created_by=? AND category_type='EXPENSE' AND status=1 AND is_deleted=0 ORDER BY category_name");
$expenseCategories = [];
if ($stmtCatE) { $stmtCatE->bind_param("i", $userId); $stmtCatE->execute(); $r=$stmtCatE->get_result(); while($row=$r->fetch_assoc()) $expenseCategories[]=$row; $stmtCatE->close(); }

$stmtProj = $connect->prepare("SELECT project_id AS id, project_name FROM projects WHERE created_by=? AND status='ACTIVE' AND is_deleted=0 ORDER BY project_name");
if ($stmtProj) { $stmtProj->bind_param("i", $userId); $stmtProj->execute(); $r=$stmtProj->get_result(); while($row=$r->fetch_assoc()) $projects[]=$row; $stmtProj->close(); }

$appBaseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), "/\\") . "/";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo getCsrfToken(); ?>">
    <title><?php echo htmlspecialchars(env('APP_NAME', 'myFin'), ENT_QUOTES, 'UTF-8'); ?></title>

    <!-- Bootstrap + legacy assets (same as header.php) -->
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="assests/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="custom/css/custom.css?v=<?php echo time(); ?>">

    <!-- jQuery + jQuery UI + Bootstrap JS (required by dropdowns, datepicker, etc.) -->
    <script src="assests/jquery/jquery.min.js"></script>
    <script src="assests/jquery-ui/jquery-ui.min.js"></script>
    <link  rel="stylesheet" href="assests/jquery-ui/jquery-ui.min.css">
    <script src="assests/bootstrap/js/bootstrap.min.js"></script>

    <style>
        /* ── Navbar (identical to header.php) ───────────────── */
        .navbar-default {
            background-color: #f8f8f8;
            background-image: linear-gradient(to bottom, #ffffff 0%, #f8f8f8 100%);
            border-color: #e7e7e7 !important;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.15), 0 1px 5px rgba(0,0,0,.075) !important;
            margin-bottom: 20px;
        }
        .navbar-default .navbar-brand { color:#333!important; font-weight:700; font-size:19px; display:inline-flex; align-items:center; gap:6px; }
        .navbar-default .navbar-nav > li > a { color:#555!important; font-size:13.5px; font-weight:500; padding:15px 12px; transition:background-color .15s ease,color .15s ease; }
        .navbar-default .navbar-nav > li > a:hover { color:#222!important; background:#e7e7e7!important; }
        .navbar-default .navbar-nav > .active > a,
        .navbar-default .navbar-nav > .active > a:hover { color:#333!important; background:#e2e2e2!important; font-weight:600; box-shadow:inset 0 3px 9px rgba(0,0,0,.075)!important; }
        .navbar-default .navbar-nav > .open > a { background:#e2e2e2!important; color:#333!important; }
        .navbar-default .navbar-nav .dropdown-menu { background:#fff!important; border:1px solid #ccc!important; border-radius:4px!important; box-shadow:0 6px 12px rgba(0,0,0,.175)!important; }
        .navbar-default .navbar-nav .dropdown-menu > li > a { color:#333!important; padding:7px 18px!important; }
        .navbar-default .navbar-nav .dropdown-menu > li > a:hover { background:#f5f5f5!important; }
        .myfin-metrics-container { display:inline-flex; align-items:center; gap:8px; margin-left:10px; height:50px; }
        .metric-badge { display:inline-flex; align-items:center; gap:5px; padding:5px 9px; border-radius:4px; font-size:11.5px; font-weight:600; white-space:nowrap; }
        .metric-badge-income  { background:#dff0d8; color:#2e7d32; border:1px solid #c8e6c9; }
        .metric-badge-expense { background:#fce4ec; color:#c2185b; border:1px solid #f8bbd0; }
        .panel-heading-flex { display:flex!important; justify-content:space-between!important; align-items:center!important; min-height:52px!important; padding:8px 15px!important; }
        .panel-heading-flex .page-heading { margin:0!important; font-size:16px!important; font-weight:600!important; display:flex!important; align-items:center!important; }
        .panel-heading-flex .btn { margin:0!important; display:inline-flex!important; align-items:center!important; gap:5px!important; }
        /* SPA container */
        #app { min-height:300px; }
    </style>
</head>
<body>
<!-- ── Navbar ─────────────────────────────────────────────────────────── -->
<nav class="navbar navbar-default navbar-static-top">
    <div class="container">
        <div class="navbar-header" style="display:flex;align-items:center;">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#spaNavbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#" onclick="return spaNav('/dashboard')" title="myFin">
                <i class="glyphicon glyphicon-piggy-bank" style="color:#27ae60;"></i> myFin
            </a>
            <div class="myfin-metrics-container hidden-xs">
                <span class="metric-badge metric-badge-income" title="Ingresos del mes">
                    <i class="glyphicon glyphicon-arrow-up"></i> Mes: S/ <?php echo number_format($monthIncome, 2); ?>
                </span>
                <span class="metric-badge metric-badge-expense" title="Gastos de hoy">
                    <i class="glyphicon glyphicon-arrow-down"></i> Hoy: S/ <?php echo number_format($todayExpenses, 2); ?>
                </span>
            </div>
        </div>
        <div class="collapse navbar-collapse" id="spaNavbar">
            <ul class="nav navbar-nav navbar-right">
                <li id="navDashboard" class="active"><a href="#" onclick="return spaNav('/dashboard')"><i class="glyphicon glyphicon-home"></i> Inicio</a></li>
                <li id="navIncome">   <a href="#" onclick="return spaNav('/income')">   <i class="glyphicon glyphicon-arrow-up text-success"></i> Ingresos</a></li>
                <li id="navExpense">  <a href="#" onclick="return spaNav('/expense')">  <i class="glyphicon glyphicon-arrow-down text-danger"></i> Gastos</a></li>
                <li id="navLoans">    <a href="#" onclick="return spaNav('/loans')">    <i class="glyphicon glyphicon-retweet text-warning"></i> Préstamos</a></li>
                <li id="navProjects"> <a href="#" onclick="return spaNav('/projects')"> <i class="glyphicon glyphicon-briefcase text-primary"></i> Proyectos</a></li>
                <li id="navAssets">   <a href="#" onclick="return spaNav('/assets')">   <i class="glyphicon glyphicon-certificate" style="color:#8e44ad;"></i> Activos</a></li>
                <li id="navReport">   <a href="#" onclick="return spaNav('/reports')">  <i class="glyphicon glyphicon-stats text-info"></i> Reportes</a></li>
                <li class="dropdown" id="navDropdownUser">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="glyphicon glyphicon-user"></i> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Usuario: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'admin', ENT_QUOTES, 'UTF-8'); ?></strong></li>
                        <li role="separator" class="divider"></li>
                        <li id="navAccounts"><a href="#" onclick="return spaNav('/accounts')"><i class="glyphicon glyphicon-credit-card"></i> Cuentas y Categorías</a></li>
                        <li id="navSetting"><a href="#" onclick="return spaNav('/settings')"><i class="glyphicon glyphicon-wrench"></i> Configuración</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="logout.php"><i class="glyphicon glyphicon-log-out"></i> Salir</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ── SPA mount point ───────────────────────────────────────────────── -->
<div class="container main-content-container">
    <div id="app"></div>
</div>

<!-- Global JS data for Vue components -->
<script>
window.systemCsrfToken   = <?php echo json_encode(getCsrfToken()); ?>;
window.__APP_NAME__       = <?php echo json_encode(env('APP_NAME', 'myFin')); ?>;
window.__BASE_URL__       = <?php echo json_encode($appBaseUrl); ?>;
window.incomeAccounts     = <?php echo json_encode($accounts); ?>;
window.incomeCategories   = <?php echo json_encode($incomeCategories); ?>;
window.incomeProjects     = <?php echo json_encode($projects); ?>;
window.expenseAccounts    = <?php echo json_encode($accounts); ?>;
window.expenseCategories  = <?php echo json_encode($expenseCategories); ?>;
window.expenseProjects    = <?php echo json_encode($projects); ?>;
window.loanAccounts       = <?php echo json_encode($accounts); ?>;

// Update CSRF token header on all jQuery AJAX too
$.ajaxSetup({ headers: { 'X-CSRF-Token': window.systemCsrfToken } });

// Prevent Bootstrap 3 modals from closing inadvertently via backdrop click or Escape
if ($.fn.modal && $.fn.modal.Constructor && $.fn.modal.Constructor.DEFAULTS) {
    $.fn.modal.Constructor.DEFAULTS.backdrop = 'static';
    $.fn.modal.Constructor.DEFAULTS.keyboard = false;
}

// Table Dropdowns: Fix z-index stacking and smart auto-dropup across all tables
$(document).on('show.bs.dropdown', '.table-responsive .btn-group, .table .btn-group, .spa-table-row .btn-group', function() {
    var $btnGroup = $(this);
    var $row = $btnGroup.closest('tr');
    var $tableResp = $btnGroup.closest('.table-responsive');

    $row.addClass('has-open-dropdown row-dropdown-open');
    $tableResp.addClass('has-open-dropdown');

    // Auto-detect available space below: if not enough room, flip to dropup
    var $menu = $btnGroup.find('.dropdown-menu');
    var btnOffset = $btnGroup.offset();
    var btnHeight = $btnGroup.outerHeight() || 24;
    var menuHeight = $menu.outerHeight() || 190;
    var windowBottom = $(window).scrollTop() + $(window).height();
    var spaceBelow = windowBottom - (btnOffset.top + btnHeight);
    var spaceAbove = btnOffset.top - $(window).scrollTop();

    if (spaceBelow < menuHeight + 10 && spaceAbove > spaceBelow) {
        $btnGroup.addClass('dropup');
    } else {
        $btnGroup.removeClass('dropup');
    }
});

$(document).on('hidden.bs.dropdown', '.table-responsive .btn-group, .table .btn-group, .spa-table-row .btn-group', function() {
    var $btnGroup = $(this);
    var $row = $btnGroup.closest('tr');
    var $tableResp = $btnGroup.closest('.table-responsive');

    $row.removeClass('has-open-dropdown row-dropdown-open');
    $tableResp.removeClass('has-open-dropdown');
    $btnGroup.removeClass('dropup');
});

// Nav helper — called by navbar links
function spaNav(path) {
    if (window.__vueRouter) {
        window.__vueRouter.push(path);
    } else {
        window.location.hash = '#' + path;
    }
    return false;
}
</script>

<!-- Vue 3 SPA bundle — asset paths injected from Vite's index.html -->
<?php
$spaIndexPath = __DIR__ . '/spa/dist/index.html';
if (file_exists($spaIndexPath)) {
    $spaHtml = file_get_contents($spaIndexPath);
    // Extract <script type="module"> src
    if (preg_match('/<script[^>]+type=["\']module["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $spaHtml, $m)) {
        $cleanSrc = preg_replace('/^\.\//', '', $m[1]);
        $cleanSrc = ltrim($cleanSrc, '/');
        $jsPath   = 'spa/dist/' . $cleanSrc;
        echo "<script type=\"module\" crossorigin src=\"{$jsPath}\"></script>\n    ";
    }
    // Extract modulepreload links
    preg_match_all('/<link[^>]+rel=["\']modulepreload["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $spaHtml, $preloads);
    foreach ($preloads[1] as $href) {
        $cleanHref = preg_replace('/^\.\//', '', $href);
        $cleanHref = ltrim($cleanHref, '/');
        $hrefPath  = 'spa/dist/' . $cleanHref;
        echo "    <link rel=\"modulepreload\" crossorigin href=\"{$hrefPath}\">\n";
    }
    // Extract CSS
    preg_match_all('/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $spaHtml, $styles);
    foreach ($styles[1] as $href) {
        $cleanHref = preg_replace('/^\.\//', '', $href);
        $cleanHref = ltrim($cleanHref, '/');
        $hrefPath  = 'spa/dist/' . $cleanHref;
        echo "    <link rel=\"stylesheet\" crossorigin href=\"{$hrefPath}\">\n";
    }
} else {
    echo "<!-- SPA bundle not found. Run: cd spa && npm run build -->\n";
    echo "<div class='container'><div class='alert alert-warning' style='margin-top:20px'>";
    echo "<strong>SPA no compilada.</strong> Ejecute <code>cd spa &amp;&amp; npm run build</code>.</div></div>";
}
?>

</body>
</html>
