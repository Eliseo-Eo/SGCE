<?php
$Root = dirname(__DIR__);
$Errores = [];
$Archivos = [
    'assets/css/base/variables.css',
    'assets/css/components/buttons.css',
    'assets/css/components/tables.css',
    'assets/css/components/filters.css',
    'assets/css/components/badges-alerts.css',
    'assets/css/components/confirm-modal.css',
    'assets/css/components/pagination.css',
    'assets/css/components/filter-bars.css',
    'includes/SGCE_Componentes.php',
    'assets/js/admin/AdminEditModals.js',
    'assets/js/admin/AdminInputs.js',
];
foreach ($Archivos as $Archivo) { if (!is_file($Root . '/' . $Archivo)) { $Errores[] = 'Falta archivo de homologación: ' . $Archivo; } }
$Layout = file_get_contents($Root . '/includes/SGCE_Layout.php');
foreach (['assets/css/components/buttons.css','assets/css/components/tables.css','assets/css/components/filters.css','assets/css/components/confirm-modal.css',
    'assets/css/components/pagination.css',
    'assets/css/components/filter-bars.css','assets/js/admin/AdminEditModals.js','assets/js/admin/AdminInputs.js'] as $Token) {
    if (!str_contains($Layout, $Token)) { $Errores[] = 'SGCE_Layout.php no carga ' . $Token; }
}
$Componentes = file_get_contents($Root . '/includes/SGCE_Componentes.php');
foreach (['SgceComponenteAlerta','SgceComponenteTablaVacia'] as $Fn) {
    if (!str_contains($Componentes, 'function ' . $Fn)) { $Errores[] = 'Falta helper reutilizable: ' . $Fn; }
}
$Css = file_get_contents($Root . '/assets/css/components/buttons.css') . file_get_contents($Root . '/assets/css/components/tables.css') . file_get_contents($Root . '/assets/css/components/filters.css') . file_get_contents($Root . '/assets/css/components/badges-alerts.css') . file_get_contents($Root . '/assets/css/components/confirm-modal.css');
foreach (['.SgceBtn','.SgceTable','.SgceFilterBar','.SgceBadge','.SgceAlert','.SgceConfirmModal'] as $Clase) {
    if (!str_contains($Css, $Clase)) { $Errores[] = 'Falta clase global UI: ' . $Clase; }
}
if ($Errores) { echo "RunInterfaceComponentChecks: FAIL
" . implode("
", $Errores) . "
"; exit(1); }
echo "RunInterfaceComponentChecks: OK
";
