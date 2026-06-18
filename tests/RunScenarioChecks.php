<?php
$Root = dirname(__DIR__);
$Errores = [];
$Revisiones = 0;
$Sql = file_get_contents($Root . '/install/SGCE.sql');
foreach (['Alumnos','Grupos','MateriasGrupo','Asignaciones','Calificaciones','Asistencias','BitacoraMovimientos','MigracionesCiclo'] as $Tabla) {
    $Revisiones++;
    if (!preg_match('/CREATE TABLE\s+`?' . preg_quote($Tabla, '/') . '`?/i', $Sql)) { $Errores[] = "No se encontró tabla requerida: $Tabla"; }
}
foreach (['reports/ExportarCalificaciones.php','reports/ExportarAsistencia.php','reports/ExportarHistorialAlumno.php','reports/ExportarBoletaPublica.php','reports/RespaldoBD.php','modules/RestaurarBD.php'] as $Archivo) {
    $Revisiones++;
    if (!is_file($Root . '/' . $Archivo)) { $Errores[] = "No se encontró archivo de escenario: $Archivo"; }
}
$AlumnosLargos = file_get_contents($Root . '/tests/fixtures/alumnos_nombres_largos.csv');
$MateriasLargas = file_get_contents($Root . '/tests/fixtures/materias_nombres_largos.csv');
$Revisiones += 2;
if (strlen($AlumnosLargos) < 120) { $Errores[] = 'Fixture de alumnos largos insuficiente.'; }
if (strlen($MateriasLargas) < 120) { $Errores[] = 'Fixture de materias largas insuficiente.'; }
if ($Errores) { echo "SGCE SCENARIO CHECKS: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "SGCE SCENARIO CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
