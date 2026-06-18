<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }

$Root = dirname(__DIR__);
$Project = dirname($Root);
$Errores = [];
$Revisiones = 0;

function AssertVisual($Condicion, $Mensaje) {
    global $Errores, $Revisiones;
    $Revisiones++;
    if (!$Condicion) { $Errores[] = $Mensaje; }
}

foreach (['Produccion', 'Desarrollo'] as $RaizNombre) {
    $Raiz = $Project . DIRECTORY_SEPARATOR . $RaizNombre;
    $Layout = $Raiz . '/includes/SGCE_Layout.php';
    $LayoutContenido = is_file($Layout) ? file_get_contents($Layout) : '';
    AssertVisual(str_contains($LayoutContenido, 'SgceLayoutBaseCssList'), "$RaizNombre/SGCE_Layout.php no centraliza la lista base de CSS.");
    AssertVisual(str_contains($LayoutContenido, 'SgceLayoutResponsiveCssList'), "$RaizNombre/SGCE_Layout.php no centraliza la lista responsive.");
    AssertVisual(str_contains($LayoutContenido, 'function SgceLayoutSharedJsList'), "$RaizNombre no centraliza la lista de JS compartido.");
    AssertVisual(str_contains($LayoutContenido, 'function SgceLayoutAdminApplicationList'), "$RaizNombre no centraliza la lista de JS administrativo.");
    AssertVisual(str_contains($LayoutContenido, 'SgceLayoutCdnJsTags(true)'), "$RaizNombre no centraliza Bootstrap JS desde el layout.");
    AssertVisual(str_contains($LayoutContenido, 'SgceLayoutCsrfScript'), "$RaizNombre no centraliza la impresión de CSRF JS opcional.");
    AssertVisual(str_contains($LayoutContenido, 'assets/css/components/mobile-buttons.css'), "$RaizNombre no registra la capa única de botones móviles.");
    AssertVisual(!str_contains($LayoutContenido, 'mobile-standalone-buttons.css'), "$RaizNombre sigue cargando mobile-standalone-buttons.css.");
    AssertVisual(!str_contains($LayoutContenido, 'mobile-public-hero-order.css'), "$RaizNombre sigue cargando mobile-public-hero-order.css.");

    $MobileButtons = $Raiz . '/assets/css/components/mobile-buttons.css';
    $MobileButtonsContenido = is_file($MobileButtons) ? file_get_contents($MobileButtons) : '';
    foreach (['.SgceBtnVolverInicio', '.BtnPeriodoVerdeMetalico', '.SgceReportBtn', '.ActionBtn', '.ConsultaHeroMain', '.ConsultaHeroActions'] as $Selector) {
        AssertVisual(str_contains($MobileButtonsContenido, $Selector), "$RaizNombre/mobile-buttons.css no cubre $Selector.");
    }
    AssertVisual(str_contains($MobileButtonsContenido, 'order: 1') && str_contains($MobileButtonsContenido, 'order: 2'), "$RaizNombre/mobile-buttons.css no protege el orden móvil del hero público.");

    $PhpFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Raiz, FilesystemIterator::SKIP_DOTS));
    foreach ($PhpFiles as $File) {
        if ($File->getExtension() !== 'php') { continue; }
        $Path = $File->getPathname();
        if (str_contains($Path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR)) { continue; }
        if (str_contains($Path, DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'installer' . DIRECTORY_SEPARATOR)) { continue; }
        $Rel = str_replace($Raiz . DIRECTORY_SEPARATOR, '', $Path);
        if ($Rel === 'includes/SGCE_Layout.php' || $Rel === 'Instalar.php') { continue; }
        $Contenido = file_get_contents($Path);
        AssertVisual(!str_contains($Contenido, 'bootstrap.bundle.min.js'), "$RaizNombre/$Rel carga Bootstrap JS manualmente fuera del layout.");
        AssertVisual(!str_contains($Contenido, 'SgceLayoutCdnJsTags()'), "$RaizNombre/$Rel imprime Bootstrap JS manualmente fuera del footer central.");
    }

    $Instalar = file_get_contents($Raiz . '/Instalar.php');
    AssertVisual(str_contains($Instalar, 'InstalarFooterAssets'), "$RaizNombre/Instalar.php no centraliza los scripts del instalador.");
    AssertVisual(str_contains($Instalar, 'Prediagnóstico del servidor'), "$RaizNombre/Instalar.php no muestra prediagnóstico inicial.");
    AssertVisual(str_contains($Instalar, 'SgceInstallerCheckSummary'), "$RaizNombre/Instalar.php no muestra resumen de checks.");

    foreach (['mobile-standalone-buttons.css', 'mobile-public-hero-order.css'] as $CssObsoleto) {
        AssertVisual(!is_file($Raiz . '/assets/css/responsive/' . $CssObsoleto), "$RaizNombre/$CssObsoleto debería estar retirado.");
    }
}

if ($Errores) {
    echo "SGCE MOBILE VISUAL 1.0.185 CHECKS: ERROR\n" . implode("\n", $Errores) . "\n";
    exit(1);
}

echo "SGCE MOBILE VISUAL 1.0.185 CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
