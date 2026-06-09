<?php
$Root = dirname(__DIR__);
$Project = dirname($Root);
$Errores = [];
$Archivos = [
    $Root . '/includes/SGCE_Mantenimiento.php',
    $Root . '/cron/mantenimiento_diario.php',
];
foreach ($Archivos as $Archivo) {
    if (!is_file($Archivo)) { $Errores[] = 'Falta archivo: ' . $Archivo; }
}
$Mant = is_file($Root . '/includes/SGCE_Mantenimiento.php') ? file_get_contents($Root . '/includes/SGCE_Mantenimiento.php') : '';
foreach (['SgceMantenimientoDiario', 'SgceMantenimientoLimpiarSesionesExpiradas', 'SgceMantenimientoLimpiarIntentosSeguridad', 'SgceMantenimientoLimpiarRespaldosTemporales'] as $Fn) {
    if (strpos($Mant, 'function ' . $Fn) === false) { $Errores[] = 'Falta función de mantenimiento: ' . $Fn; }
}
$Cron = is_file($Root . '/cron/mantenimiento_diario.php') ? file_get_contents($Root . '/cron/mantenimiento_diario.php') : '';
foreach (['--self-check', 'mantenimiento_diario.lock', 'SgceMantenimientoDiario'] as $Needle) {
    if (strpos($Cron, $Needle) === false) { $Errores[] = 'Cron de mantenimiento incompleto: ' . $Needle; }
}
$Cmd = PHP_BINARY . ' ' . escapeshellarg($Root . '/cron/mantenimiento_diario.php') . ' --self-check 2>&1';
exec($Cmd, $Out, $Code);
if ($Code !== 0 || !str_contains(implode("\n", $Out), 'self-check OK')) {
    $Errores[] = 'No se pudo ejecutar self-check del cron de mantenimiento: ' . implode("\n", $Out);
}
if ($Errores) { echo "RunMaintenanceChecks: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "RunMaintenanceChecks: OK\n";
