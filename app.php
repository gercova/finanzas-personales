<?php
/**
 * app.php — Pure, decoupled SPA entrypoint.
 * Contains zero database queries, zero business logic, and zero PHP UI rendering.
 * Serves the Bootstrap 3 stylesheet shell and injects the Vite-built Vue 3 SPA assets.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>myFin</title>
</head>
<body>
    <div id="app"></div>

<?php
$spaIndexPath = __DIR__ . '/spa/dist/index.html';
if (file_exists($spaIndexPath)) {
    $spaHtml = file_get_contents($spaIndexPath);

    if (preg_match('/<script[^>]+type=["\']module["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $spaHtml, $m)) {
        $cleanSrc = ltrim(preg_replace('/^\.\//', '', $m[1]), '/');
        echo '    <script type="module" crossorigin src="spa/dist/' . htmlspecialchars($cleanSrc, ENT_QUOTES, 'UTF-8') . "\"></script>\n";
    }

    preg_match_all('/<link[^>]+rel=["\']modulepreload["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $spaHtml, $preloads);
    foreach ($preloads[1] as $href) {
        $cleanHref = ltrim(preg_replace('/^\.\//', '', $href), '/');
        echo '    <link rel="modulepreload" crossorigin href="spa/dist/' . htmlspecialchars($cleanHref, ENT_QUOTES, 'UTF-8') . "\">\n";
    }

    preg_match_all('/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $spaHtml, $styles);
    foreach ($styles[1] as $href) {
        $cleanHref = ltrim(preg_replace('/^\.\//', '', $href), '/');
        echo '    <link rel="stylesheet" crossorigin href="spa/dist/' . htmlspecialchars($cleanHref, ENT_QUOTES, 'UTF-8') . "\">\n";
    }
} else {
    echo "    <div class='container' style='margin-top:30px'><div class='alert alert-warning'>";
    echo "<strong>SPA no compilada.</strong> Ejecute <code>npm run build --prefix spa</code>.</div></div>\n";
}
?>
</body>
</html>
