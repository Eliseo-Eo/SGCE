<?php
$Root = dirname(__DIR__);
$Required = [
    'assets/css/base/variables.css',
    'assets/css/base/layout.css',
    'assets/css/components/buttons.css',
    'assets/css/components/tables.css',
    'assets/css/components/filters.css',
    'assets/js/admin/AdminCore.js',
    'assets/js/admin/AdminServerFilters.js',
    'repositories/AsistenciaRepository.php',
    'repositories/CalificacionRepository.php',
    'views/asistencia/PaseLista.php',
    'views/asistencia/partials/Formulario.php',
    'views/calificaciones/CalificarGrupo.php',
    'views/calificaciones/partials/Formulario.php',
];
$Missing = [];
foreach ($Required as $File) { if (!is_file($Root . '/' . $File)) { $Missing[] = $File; } }
if ($Missing) { fwrite(STDERR, "Faltan archivos requeridos:
- " . implode("
- ", $Missing) . "
"); exit(1); }
$Layout = file_get_contents($Root . '/includes/SGCE_Layout.php');
foreach (['assets/js/admin/AdminCore.js','assets/css/components/buttons.css','assets/css/components/tables.css'] as $Needle) {
    if (strpos($Layout, $Needle) === false) { fwrite(STDERR, "Layout no carga: $Needle
"); exit(1); }
}
echo "RunExtremeFragmentationChecks.php: OK
";
