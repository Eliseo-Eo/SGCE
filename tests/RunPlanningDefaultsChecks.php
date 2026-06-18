<?php
$Root = dirname(__DIR__);
$Errores = [];
$Revisiones = 0;

function CheckContains(string $Root, string $Archivo, array $Needles, array &$Errores, int &$Revisiones): void {
    $Ruta = $Root . '/' . $Archivo;
    $Revisiones++;
    if (!is_file($Ruta)) { $Errores[] = 'Falta archivo: ' . $Archivo; return; }
    $Contenido = file_get_contents($Ruta);
    foreach ($Needles as $Needle) {
        $Revisiones++;
        if (!str_contains($Contenido, $Needle)) { $Errores[] = "No se encontró [$Needle] en $Archivo"; }
    }
}

function CheckNotContains(string $Root, string $Archivo, array $Needles, array &$Errores, int &$Revisiones): void {
    $Ruta = $Root . '/' . $Archivo;
    $Revisiones++;
    if (!is_file($Ruta)) { $Errores[] = 'Falta archivo: ' . $Archivo; return; }
    $Contenido = file_get_contents($Ruta);
    foreach ($Needles as $Needle) {
        $Revisiones++;
        if (str_contains($Contenido, $Needle)) { $Errores[] = "No debe existir [$Needle] en $Archivo"; }
    }
}

CheckContains($Root, 'Instalar.php', [
    "'TipoPlaneacion' => \$_POST['TipoPlaneacion'] ?? 'CICLO'",
    "'PlaneacionesCantidad' => \$_POST['PlaneacionesCantidad'] ?? ''",
    'Por ciclo',
    'Se solicitará la cantidad configurada de planeaciones por materia durante el ciclo escolar.'
], $Errores, $Revisiones);
CheckNotContains($Root, 'Instalar.php', [
    "TipoPlaneacion === 'CICLO') { \$PlaneacionesCantidad = 1;",
    "PlaneacionesCantidadTexto = '1';"
], $Errores, $Revisiones);

CheckContains($Root, 'assets/js/Instalar.js', [
    "TipoPlaneacion === 'CICLO'",
    "PlaneacionesCantidadInput.placeholder = 'Ej. 6'",
    'Se solicitará la cantidad configurada de planeaciones por materia durante el ciclo escolar.',
    "PlaneacionesCantidadInput.required = true"
], $Errores, $Revisiones);
CheckNotContains($Root, 'assets/js/Instalar.js', [
    "PlaneacionesCantidadInput.value = '1'",
    "CantidadPeriodos > 0 ? CantidadPeriodos : 3",
    "PlaneacionesCantidadInput.required = false"
], $Errores, $Revisiones);

CheckContains($Root, 'assets/js/ConfiguracionAdmin.js', [
    "TipoPlaneacion === 'CICLO'",
    "PlaneacionesCantidadInput.placeholder = 'Ej. 6'",
    "PlaneacionesCantidadInput.required = true"
], $Errores, $Revisiones);
CheckNotContains($Root, 'assets/js/ConfiguracionAdmin.js', [
    "PlaneacionesCantidadInput.value = '1'",
    "CantidadPeriodos > 0 ? CantidadPeriodos : 3",
    "PlaneacionesCantidadInput.required = false"
], $Errores, $Revisiones);

CheckContains($Root, 'includes/SGCE_Configuracion.php', [
    "'TipoPlaneacion' => 'CICLO'",
    "'PlaneacionesCantidad' => '1'",
    "return in_array(\$Tipo, ['CICLO','PERIODO','UNIDAD','SEMANA'], true) ? \$Tipo : 'CICLO';"
], $Errores, $Revisiones);
CheckNotContains($Root, 'includes/SGCE_Configuracion.php', [
    "TipoPlaneacion === 'CICLO') { \$PlaneacionesCantidad = 1;"
], $Errores, $Revisiones);

CheckContains($Root, 'install/SGCE.sql', [
    "TipoPlaneacion ENUM('CICLO','PERIODO','UNIDAD','SEMANA') NOT NULL DEFAULT 'CICLO'",
    'PlaneacionesCantidad INT UNSIGNED NOT NULL DEFAULT 1'
], $Errores, $Revisiones);

if ($Errores) {
    echo "SGCE PLANNING DEFAULTS CHECKS: ERROR\n" . implode("\n", $Errores) . "\n";
    exit(1);
}

echo "SGCE PLANNING DEFAULTS CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
