<?php
$Root = dirname(__DIR__);
$Fixtures = [
    'plantilla_docentes.csv' => ['NOMBRE COMPLETO','USUARIO','CONTRASEÑA'],
    'plantilla_grupos.csv' => ['AÑO','GRUPO','TURNO'],
    'plantilla_materias.csv' => ['MATERIA','AÑO','GRUPO','TURNO','HORAS'],
    'plantilla_alumnos.csv' => ['NOMBRE COMPLETO','AÑO','GRUPO','TURNO'],
    'alumnos_nombres_largos.csv' => ['NOMBRE COMPLETO','AÑO','GRUPO','TURNO'],
    'materias_nombres_largos.csv' => ['MATERIA','AÑO','GRUPO','TURNO','HORAS'],
];
$Errores = [];
$Revisiones = 0;
foreach ($Fixtures as $Archivo => $EncabezadosEsperados) {
    $Ruta = $Root . '/tests/fixtures/' . $Archivo;
    $Revisiones++;
    if (!is_file($Ruta)) { $Errores[] = "No existe fixture: $Archivo"; continue; }
    $Handle = fopen($Ruta, 'r');
    $Header = fgetcsv($Handle);
    $Fila = fgetcsv($Handle);
    fclose($Handle);
    if ($Header !== $EncabezadosEsperados) { $Errores[] = "Encabezado incorrecto en $Archivo"; }
    if (!$Fila || count(array_filter($Fila, static fn($Valor) => trim((string)$Valor) !== '')) === 0) { $Errores[] = "Fixture sin datos: $Archivo"; }
}


if ($Errores) { echo "SGCE IMPORT FIXTURE CHECKS: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "SGCE IMPORT FIXTURE CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
