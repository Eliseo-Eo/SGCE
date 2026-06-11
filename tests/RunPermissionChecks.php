<?php
$Root = dirname(__DIR__);
$Errores = [];
$Revisiones = 0;

$Seguridad = file_get_contents($Root . '/includes/SGCE_Seguridad.php');
$Calificar = file_get_contents($Root . '/modules/Calificar.php') . "\n" . file_get_contents($Root . '/services/CalificacionService.php') . "\n" . file_get_contents($Root . '/repositories/CalificacionRepository.php');
$Asistencia = file_get_contents($Root . '/modules/Asistencia.php');
$ReportesCal = file_get_contents($Root . '/reports/ExportarCalificaciones.php');
$ReportesAsis = file_get_contents($Root . '/reports/ExportarAsistencia.php');

$Revisiones++;
foreach (['admin','administrativo','maestro'] as $Rol) {
    if (!preg_match("/'" . preg_quote($Rol, '/') . "'\s*=>/", $Seguridad)) {
        $Errores[] = "No se encontró el rol requerido en SGCE_Seguridad.php: $Rol";
    }
}

$Revisiones++;
foreach (['usuarios','respaldos','bitacora','configuracion','asistencia','calificaciones','planeaciones'] as $Permiso) {
    if (strpos($Seguridad, "'" . $Permiso . "'") === false) {
        $Errores[] = "No se encontró permiso esperado: $Permiso";
    }
}

$Revisiones++;
if (strpos($Calificar, 'A.MaestroId = ?') === false || strpos($Calificar, '$UserSession[\'Id\']') === false) {
    $Errores[] = 'Calificar.php no conserva la validación de asignación por docente.';
}

$Revisiones++;
if (strpos($Asistencia, 'SgceTieneRol($UserSession, [\'maestro\'])') === false || strpos($Asistencia, 'Acceso denegado') === false) {
    $Errores[] = 'Asistencia.php no conserva la validación de docente dueño de asignación.';
}

$Revisiones++;
if (strpos($ReportesCal, 'SgcePuedeAdministrarReportes') === false || strpos($ReportesCal, 'SgceTieneRol($UserSession, [\'maestro\'])') === false) {
    $Errores[] = 'ExportarCalificaciones.php no conserva validación diferenciada de maestro/reportes.';
}

$Revisiones++;
if (strpos($ReportesAsis, 'SgcePuedeAdministrarReportes') === false || strpos($ReportesAsis, 'SgceTieneRol($UserSession, [\'maestro\'])') === false) {
    $Errores[] = 'ExportarAsistencia.php no conserva validación diferenciada de maestro/reportes.';
}

if ($Errores) { echo "SGCE PERMISSION CHECKS: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "SGCE PERMISSION CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
