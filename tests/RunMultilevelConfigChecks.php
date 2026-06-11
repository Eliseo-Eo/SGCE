<?php
$Root = dirname(__DIR__);
$Files = [
    $Root . '/install/SGCE.sql',
    $Root . '/includes/SGCE_Configuracion.php',
    $Root . '/includes/SGCE_Texto.php',
    $Root . '/modules/Calificar.php',
    $Root . '/assets/js/Calificar.js',
    $Root . '/modules/ConfiguracionAdmin.php',
    $Root . '/views/admin/configuracion/Index.php',
    $Root . '/assets/js/ConfiguracionAdmin.js',
];
foreach ($Files as $File) {
    if (!is_file($File)) { fwrite(STDERR, "Falta archivo: $File
"); exit(1); }
}
$Sql = file_get_contents($Root . '/install/SGCE.sql');
$Checks = [
    'Turno VARCHAR(40)' => 'Turnos configurables en Grupos',
    'Matricula VARCHAR(40)' => 'Matrícula en Alumnos',
    'chk_calificaciones_rango CHECK (Calificacion >= 0 AND Calificacion <= 100)' => 'Base permite escala configurable 0-100',
];
foreach ($Checks as $Needle => $Label) {
    if (strpos($Sql, $Needle) === false) { fwrite(STDERR, "No cumple: $Label
"); exit(1); }
}
$Cfg = file_get_contents($Root . '/includes/SGCE_Configuracion.php');
foreach (['SgceTurnosDisponibles', 'SgceCalificacionConfig', 'SgceAsignarMatriculaSiAplica'] as $Fn) {
    if (strpos($Cfg, 'function ' . $Fn) === false) { fwrite(STDERR, "Falta función: $Fn
"); exit(1); }
}

$ConfigAdmin = file_get_contents($Root . '/modules/ConfiguracionAdmin.php') . "\n" . file_get_contents($Root . '/views/admin/configuracion/Index.php');
foreach (['SgceConfigMatriculaAutomatica', 'SgceConfigMatriculaEjemplo', 'SgceMatriculaDependiente', 'SgceConfigPeriodosModo', 'SgcePeriodosPersonalizadosDependiente'] as $Needle) {
    if (strpos($ConfigAdmin, $Needle) === false) { fwrite(STDERR, "Falta control de matrícula en Configuración: $Needle
"); exit(1); }
}

if (strpos($ConfigAdmin, 'Calificación aprobatoria') === false) {
    fwrite(STDERR, "Configuración debe mostrar la etiqueta completa Calificación aprobatoria.
");
    exit(1);
}

$ConfigJs = file_get_contents($Root . '/assets/js/ConfiguracionAdmin.js');
foreach (['ActualizarMatriculaAutomatica', 'MatriculaPrefijoInput.disabled', 'SgceMatriculaHelp', 'ActualizarPeriodosPersonalizados', 'PeriodosPersonalizadosTextarea.disabled'] as $Needle) {
    if (strpos($ConfigJs, $Needle) === false) { fwrite(STDERR, "Falta JS de matrícula en Configuración: $Needle
"); exit(1); }
}

if (strpos($ConfigAdmin, 'col-md-6 SgceAcademicOrgField') === false || strpos($ConfigAdmin, 'col-md-6 SgceAcademicStagesField') === false) {
    fwrite(STDERR, "La estructura académica de Configuración debe quedar en columnas 50/50.
");
    exit(1);
}
$ConfigCss = file_get_contents($Root . '/assets/css/configuracion-botones-metalicos.css');
if (strpos($ConfigCss, 'SgceAcademicOrgField{width:50%!important;}') === false || strpos($ConfigCss, 'SgceAcademicStagesField{width:50%!important;}') === false) {
    fwrite(STDERR, "El CSS de estructura académica debe conservar columnas 50/50.
");
    exit(1);
}
echo "RunMultilevelConfigChecks: OK
";
