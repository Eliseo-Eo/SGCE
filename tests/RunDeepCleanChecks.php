<?php
$Root = dirname(__DIR__);
$Errores = [];
$VersionActual = '1.0.140';
foreach (glob($Root . '/CAMBIOS_1.0.*.md') ?: [] as $Archivo) { if (!str_ends_with($Archivo, 'CAMBIOS_' . $VersionActual . '.md')) { $Errores[] = 'Archivo de cambios antiguo detectado: ' . basename($Archivo); } }
foreach (glob($Root . '/AUDITORIA_1.0.*.md') ?: [] as $Archivo) { if (!str_ends_with($Archivo, 'AUDITORIA_' . $VersionActual . '.md')) { $Errores[] = 'Archivo de auditoría antiguo detectado: ' . basename($Archivo); } }
$Produccion = dirname($Root) . DIRECTORY_SEPARATOR . 'Produccion';
if (is_dir($Produccion)) {
    $Iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Produccion, FilesystemIterator::SKIP_DOTS));
    foreach ($Iter as $Archivo) { if ($Archivo->isFile() && preg_match('/DATOS_DEMO|DEMO/i', $Archivo->getFilename())) { $Errores[] = 'Producción contiene archivo demo: ' . $Archivo->getPathname(); } }
}
foreach (['assets/js/admin/AdminUtils.js','assets/js/admin/AdminEditModals.js','assets/js/admin/AdminInputs.js','assets/js/admin/AdminCore.js','assets/js/admin/AdminSearchableSelects.js','assets/js/admin/AdminClientPagination.js','assets/js/admin/AdminServerFilters.js'] as $Relativo) {
    if (!is_file($Root . '/' . $Relativo)) { $Errores[] = 'Falta módulo JS separado: ' . $Relativo; }
}
foreach (['views/asistencia/PaseLista.php','views/calificaciones/CalificarGrupo.php','repositories/AsistenciaRepository.php','repositories/CalificacionRepository.php'] as $Relativo) {
    if (!is_file($Root . '/' . $Relativo)) { $Errores[] = 'Falta archivo de fragmentación: ' . $Relativo; }
}
if ($Errores) { echo "RunDeepCleanChecks: FAIL
" . implode("
", $Errores) . "
"; exit(1); }
echo "RunDeepCleanChecks: OK
";
