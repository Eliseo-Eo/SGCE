<?php
$Root = dirname(__DIR__);
$Installer = $Root . '/Instalar.php';
$InstallerJs = $Root . '/assets/js/Instalar.js';
foreach ([$Installer, $InstallerJs] as $File) {
    if (!is_file($File)) { fwrite(STDERR, "Falta archivo: $File\n"); exit(1); }
}
$Php = file_get_contents($Installer);
$Js = file_get_contents($InstallerJs);
$ChecksPhp = [
    'Parámetros multinivel' => 'Sección visual de parámetros multinivel en instalador',
    'name="TurnosDisponibles"' => 'Campo de turnos disponibles',
    'name="CalificacionMinima"' => 'Campo de calificación mínima',
    'name="CalificacionMaxima"' => 'Campo de calificación máxima',
    'name="CalificacionAprobatoria"' => 'Campo de calificación aprobatoria',
    'Calificación aprobatoria' => 'Etiqueta completa de calificación aprobatoria',
    'name="CalificacionDecimales"' => 'Control de decimales',
    'name="MatriculaAutomatica"' => 'Control de matrícula automática',
    'name="MatriculaPrefijo"' => 'Campo de prefijo de matrícula',
    'SgceInstallerMatriculaEjemplo' => 'Ejemplo visual de matrícula generada',
    'SgceInstallerFieldLabel' => 'Alineación de etiquetas de calificación',
    'SgceMatriculaDependiente' => 'Campos dependientes de matrícula automática',
    '<div class="col-md-6"><label class="fw-bold mb-2">Organización académica</label>' => 'Organización académica alineada a media columna',
    '<div class="col-md-6"><label class="fw-bold mb-2">Cantidad de etapas académicas</label>' => 'Cantidad de etapas académicas alineada a media columna',
    'id="SgceInstallerPeriodosModo"' => 'Selector de modo de periodos con identificador para dependencia visual',
    'SgcePeriodosPersonalizadosDependiente' => 'Textarea de periodos personalizados dependiente del modo',
    'Solo se captura cuando el modo de periodos está en personalizado.' => 'Ayuda clara para periodos personalizados',
];
foreach ($ChecksPhp as $Needle => $Label) {
    if (strpos($Php, $Needle) === false) { fwrite(STDERR, "No cumple instalador: $Label\n"); exit(1); }
}

if (strpos($Php, 'Configuración editable:') !== false) { fwrite(STDERR, "El aviso redundante de configuración editable debe quedar eliminado.\n"); exit(1); }
$ChecksJs = [
    'CampoPrefijoMatricula' => 'Actualización dinámica del ejemplo de matrícula',
    'TurnosDisponibles' => 'Validación cliente de turnos',
    'CalificacionMinima' => 'Validación cliente de escala de calificaciones',
    'MatriculaPrefijo' => 'Validación cliente de prefijo de matrícula',
    'CampoMatriculaAutomatica' => 'Control dinámico para activar/desactivar prefijo de matrícula',
    'CampoPrefijoMatricula.disabled' => 'Deshabilita prefijo cuando matrícula automática está inactiva',
    'ActualizarPeriodosPersonalizados' => 'Control dinámico de periodos personalizados',
    'PeriodosPersonalizadosTextarea.disabled' => 'Deshabilita periodos personalizados en modo automático',
];
foreach ($ChecksJs as $Needle => $Label) {
    if (strpos($Js, $Needle) === false) { fwrite(STDERR, "No cumple JS instalador: $Label\n"); exit(1); }
}
echo "RunInstallerMultilevelChecks: OK\n";
