<?php
$Root = dirname(__DIR__);
$Errores = [];
$Sql = file_get_contents($Root . '/install/SGCE.sql');
$Checks = [
    'Calificacion DECIMAL(5,2) NOT NULL' => 'Calificación debe soportar 100.00',
    'TurnoSnapshot VARCHAR(40) NOT NULL' => 'Kardex debe conservar turnos largos',
    'idx_asistencias_rango_reporte' => 'Índice de asistencia para reportes por rango',
    'idx_asistencias_alumno_fecha_asignacion' => 'Índice para expediente/historial de alumno',
    'idx_bitacora_fecha_id' => 'Índice para bitácora histórica',
    'idx_inscripciones_ciclo_grupo_estado' => 'Índice para alumnos por ciclo/grupo/estado',
    'idx_calificaciones_alumno_periodo' => 'Índice para calificaciones históricas por alumno/periodo',
];
foreach ($Checks as $Needle => $Label) {
    if (strpos($Sql, $Needle) === false) { $Errores[] = 'No cumple crecimiento 10 ciclos: ' . $Label; }
}
$Texto = file_get_contents($Root . '/includes/SGCE_Texto.php');
if (strpos($Texto, "{1,10}") === false || strpos($Texto, "str_replace(' ', '',") === false) {
    $Errores[] = 'La validación de grupo debe aceptar formatos flexibles y compactar espacios.';
}
$Cal = file_get_contents($Root . '/reports/ExportarCalificaciones.php');
if (strpos($Cal, 'SgceCalificacionesValidarPdfMasivo') === false) { $Errores[] = 'Falta límite de PDF en calificaciones.'; }
$Asis = file_get_contents($Root . '/reports/ExportarAsistencia.php');
if (strpos($Asis, 'SgceAsistenciaValidarPdfMasivo') === false) { $Errores[] = 'Falta límite de PDF en asistencia.'; }
if (!is_file($Root . '/docs/RENDIMIENTO_10_ANIOS.md')) { $Errores[] = 'Falta documentación de rendimiento a 10 años.'; }
if ($Errores) { echo "RunGrowth10CyclesChecks: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "RunGrowth10CyclesChecks: OK\n";
