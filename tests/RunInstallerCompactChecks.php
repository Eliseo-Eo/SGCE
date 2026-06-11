<?php
$Root = dirname(__DIR__);
$Errores = [];
$Revisiones = 0;

$Instalador = file_get_contents($Root . '/Instalar.php');
$Js = file_get_contents($Root . '/assets/js/Instalar.js');
$Css = file_get_contents($Root . '/assets/css/installer.css');

$Checks = [
    ['Instalar.php', $Instalador, 'id="SgceInstallerDetailsBtn"', 'Botón Ver detalles no encontrado.'],
    ['Instalar.php', $Instalador, 'aria-expanded="false"', 'El botón Ver detalles debe iniciar contraído.'],
    ['Instalar.php', $Instalador, 'id="SgceInstallerCheckResults" hidden', 'Los detalles del prediagnóstico deben iniciar ocultos.'],
    ['Instalar.js', $Js, 'function ActualizarEstadoDetalles', 'No existe controlador para alternar detalles del prediagnóstico.'],
    ['Instalar.js', $Js, 'ActualizarEstadoDetalles(false)', 'El estado inicial compacto no se inicializa desde JS.'],
    ['Instalar.js', $Js, 'ActualizarEstadoDetalles(true)', 'La verificación debe mostrar detalles al terminar.'],
    ['installer.css', $Css, '.SgceInstallerCheckResults[hidden]', 'No existe regla CSS para ocultar resultados del prediagnóstico.'],
    ['installer.css', $Css, '.SgceInstallerCheckActions', 'No existe estilo para acciones del prediagnóstico compacto.'],
];

foreach ($Checks as [$Archivo, $Contenido, $Patron, $Mensaje]) {
    $Revisiones++;
    if (strpos($Contenido, $Patron) === false) {
        $Errores[] = $Archivo . ': ' . $Mensaje;
    }
}

if ($Errores) {
    echo "SGCE INSTALLER COMPACT 1.0.140 CHECKS: ERROR\n" . implode("\n", $Errores) . "\n";
    exit(1);
}

echo "SGCE INSTALLER COMPACT 1.0.140 CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
