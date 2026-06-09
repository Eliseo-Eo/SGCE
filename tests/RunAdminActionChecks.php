<?php
$Root = dirname(__DIR__);
$Errores = [];
$Revisiones = 0;

function CheckContainsFile(string $Root, string $Archivo, array $Needles, array &$Errores, int &$Revisiones): void {
    $Ruta = $Root . '/' . $Archivo;
    $Revisiones++;
    if (!is_file($Ruta)) { $Errores[] = "Falta archivo: $Archivo"; return; }
    $Contenido = file_get_contents($Ruta);
    foreach ($Needles as $Needle) {
        $Revisiones++;
        if (!str_contains($Contenido, $Needle)) { $Errores[] = "No se encontró [$Needle] en $Archivo"; }
    }
}

CheckContainsFile($Root, 'modules/admin/AdminAcciones.php', [
    '$StmtAlumnoMismoNombre->execute([$Nombre, $GrupoId]);',
    "UPDATE Alumnos SET Activo = 1, NombreBusqueda = ?, Matricula = COALESCE(NULLIF(?, ''), Matricula) WHERE Id = ?",
    '$Pdo->beginTransaction();',
    '$Pdo->commit();',
    '$Pdo->rollBack();',
    "throw new RuntimeException('No se pudo crear la inscripción del alumno en el ciclo activo.');"
], $Errores, $Revisiones);

$Contenido = file_get_contents($Root . '/modules/admin/AdminAcciones.php');
$Revisiones++;
if (str_contains($Contenido, '$StmtAlumnoMismoNombre->execute([$Nombre, SgceTextoBusquedaNormalizado($Nombre), $GrupoId]);')) {
    $Errores[] = 'AltaAlumno conserva el bug de tres parámetros en una consulta de dos placeholders.';
}
$Revisiones++;
if (str_contains($Contenido, 'NombreBusqueda = NombreCompleto')) {
    $Errores[] = 'AltaAlumno sigue copiando NombreBusqueda sin normalizar.';
}

if ($Errores) {
    echo "SGCE ADMIN ACTION CHECKS: ERROR\n" . implode("\n", $Errores) . "\n";
    exit(1);
}

echo "SGCE ADMIN ACTION CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
