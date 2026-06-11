<?php
$Root = dirname(__DIR__);
$Errores = [];
$Revisiones = 0;
function MustContain(string $Archivo, string $Needle, array &$Errores, int &$Revisiones): void {
    global $Root; $Revisiones++; $Ruta = $Root . '/' . $Archivo;
    if (!is_file($Ruta)) { $Errores[] = 'Falta archivo: ' . $Archivo; return; }
    if (!str_contains(file_get_contents($Ruta), $Needle)) { $Errores[] = "No se encontró [$Needle] en $Archivo"; }
}
MustContain('views/admin/inicio.php', "['Maestros activos', $" . "TotalMaestrosActivos", $Errores, $Revisiones);
MustContain('views/admin/inicio.php', "['Grupos activos', $" . "TotalGruposActivos", $Errores, $Revisiones);
MustContain('views/admin/inicio.php', "['Alumnos activos', $" . "TotalAlumnosActivos", $Errores, $Revisiones);
MustContain('modules/admin/AdminDatos.php', "PageSizeAlumnos'] ?? 6, 6, 6", $Errores, $Revisiones);
MustContain('modules/PlaneacionesAdmin.php', 'planeaciones-admin-review-modal.css', $Errores, $Revisiones);
MustContain('assets/css/modules/planeaciones-admin-review-modal.css', '.PlaneacionReviewHeader', $Errores, $Revisiones);
MustContain('modules/Maestro.php', 'TodosPeriodos=1', $Errores, $Revisiones);
MustContain('modules/Maestro.php', 'Kardex Excel', $Errores, $Revisiones);
MustContain('reports/ExportarCalificaciones.php', '$TodosPeriodos', $Errores, $Revisiones);
MustContain('reports/ExportarCalificaciones.php', 'Kardex de calificaciones', $Errores, $Revisiones);
if ($Errores) { echo "SGCE FUNCTIONAL POLISH CHECKS: ERROR
" . implode("
", $Errores) . "
"; exit(1); }
echo "SGCE FUNCTIONAL POLISH CHECKS: OK
Revisiones ejecutadas: $Revisiones
";
