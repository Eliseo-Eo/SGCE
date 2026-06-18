<?php
$Root = dirname(__DIR__);
$Errores = [];
$Revisiones = 0;

$Endpoints = [
    'api/admin/alumnos.php' => "SgceAdminApiResponder('alumnos'",
    'api/admin/materias.php' => "SgceAdminApiResponder('materias'",
    'api/admin/asignaciones.php' => "SgceAdminApiResponder('asignaciones'",
    'api/admin/bitacora.php' => "SgceAdminApiResponder('bitacora'",
];

foreach ($Endpoints as $Archivo => $Needle) {
    $Ruta = $Root . '/' . $Archivo;
    $Revisiones++;
    if (!is_file($Ruta)) { $Errores[] = "Falta endpoint interno: $Archivo"; continue; }
    $Contenido = file_get_contents($Ruta);
    if (!str_contains($Contenido, $Needle)) { $Errores[] = "Endpoint sin tab esperado: $Archivo"; }
}

$Partial = $Root . '/api/admin/_partial.php';
$Revisiones++;
if (!is_file($Partial)) { $Errores[] = 'Falta api/admin/_partial.php'; }
else {
    $Contenido = file_get_contents($Partial);
    foreach (["Content-Type: application/json", 'VerificarSesionCookie', 'SgcePuedePanelAdmin', 'views/admin/partials', "'tbody'", "'pager'", "'modals'", "'count'"] as $Needle) {
        $Revisiones++;
        if (!str_contains($Contenido, $Needle)) { $Errores[] = "No se encontró [$Needle] en api/admin/_partial.php"; }
    }
}

$Js = $Root . '/assets/js/admin/AdminServerFilters.js';
$Revisiones++;
if (!is_file($Js)) { $Errores[] = 'Falta assets/js/admin/AdminServerFilters.js'; }
else {
    $Contenido = file_get_contents($Js);
    foreach (['api/admin/alumnos.php','api/admin/materias.php','api/admin/asignaciones.php','api/admin/bitacora.php','fetch('] as $Needle) {
        $Revisiones++;
        if (!str_contains($Contenido, $Needle)) { $Errores[] = "AdminServerFilters.js no referencia [$Needle]"; }
    }
}

if ($Errores) {
    echo "SGCE API ENDPOINT CHECKS: ERROR\n" . implode("\n", $Errores) . "\n";
    exit(1);
}

echo "SGCE API ENDPOINT CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
