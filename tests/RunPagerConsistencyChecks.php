<?php
$Root = dirname(__DIR__);
$Errores = [];
$Ui = file_get_contents($Root . '/includes/SGCE_UI.php');
$Js = file_get_contents($Root . '/assets/js/admin/AdminClientPagination.js');
$Views = [
    'views/admin/alumnos.php',
    'views/admin/materias.php',
    'views/admin/asignaciones.php',
    'modules/PlaneacionesAdmin.php',
    'modules/PeriodosAdmin.php',
    'views/admin/usuarios/Index.php',
];
foreach (['Primera página', 'Página anterior', 'Página siguiente', 'Última página', '&lsaquo;', '&rsaquo;', '&laquo;', '&raquo;', 'SgcePagerDots'] as $Token) {
    if (!str_contains($Ui, $Token)) { $Errores[] = 'Falta token de paginador servidor: ' . $Token; }
}
foreach (["CrearBoton('«'", "CrearBoton('‹'", "CrearBoton('›'", "CrearBoton('»'"] as $Token) {
    if (!str_contains($Js, $Token)) { $Errores[] = 'Falta token de paginador cliente: ' . $Token; }
}
foreach ($Views as $Rel) {
    $Path = $Root . '/' . $Rel;
    if (!is_file($Path)) { $Errores[] = 'No existe archivo revisado: ' . $Rel; continue; }
    $Contenido = file_get_contents($Path);
    if (!str_contains($Contenido, 'SgceRenderPager(')) { $Errores[] = 'Vista sin paginador homologado: ' . $Rel; }
}
if ($Errores) { echo "RunPagerConsistencyChecks: ERROR
" . implode("
", $Errores) . "
"; exit(1); }
echo "RunPagerConsistencyChecks: OK
";
