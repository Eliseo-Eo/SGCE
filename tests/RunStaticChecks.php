<?php
$Root = dirname(__DIR__);
$Errores = [];
$Revisiones = 0;
$Iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Root, FilesystemIterator::SKIP_DOTS));
foreach ($Iterador as $Archivo) {
    if (!$Archivo->isFile()) { continue; }
    $Ruta = $Archivo->getPathname();
    $Rel = str_replace($Root . DIRECTORY_SEPARATOR, '', $Ruta);
    $Revisiones++;
    if (preg_match('/\.(bak|old|tmp|orig|zip)$/i', $Rel)) { $Errores[] = "Archivo residual no permitido: $Rel"; }
    if (str_contains($Rel, 'storage/backups/') && !preg_match('/\.htaccess$|index\.html$/', $Rel)) { $Errores[] = "Respaldo real dentro del paquete: $Rel"; }
    if (str_contains($Rel, 'storage/logs/') && !preg_match('/\.htaccess$|index\.html$/', $Rel)) { $Errores[] = "Log real dentro del paquete: $Rel"; }
    if (!str_starts_with(str_replace('\\', '/', $Rel), 'tests/') && preg_match('/\.(php|css|js|md|txt|sql)$/i', $Rel)) {
        $Contenido = file_get_contents($Ruta);
        foreach (array_merge(array_map(static fn($N) => '1.0.' . $N, range(90, 134))) as $VersionVieja) {
            if (str_contains($Contenido, $VersionVieja)) { $Errores[] = "Referencia antigua $VersionVieja en $Rel"; break; }
        }
    }
    if (preg_match('/\.css$/i', $Rel)) {
        $Contenido = file_get_contents($Ruta);
        if (substr_count($Contenido, '{') !== substr_count($Contenido, '}')) { $Errores[] = "Llaves CSS desbalanceadas: $Rel"; }
    }
}
foreach (['assets/css/avisos-botones-metalicos.css','includes/SGCE_Layout.php','docs/GUIA_PRUEBAS_REALES.md','tests/RunMySQLChecks.php','tests/RunBackupRestoreChecks.php','tests/RunImportChecks.php','tests/RunMigrationChecks.php','tests/RunAdminActionChecks.php','tests/RunApiEndpointChecks.php','tests/RunLoginMotionChecks.php','tests/RunCssJsCleanChecks.php','tests/RunArchitectureChecks.php','tests/RunInterfaceComponentChecks.php','tests/RunFunctionalPolishChecks.php','tests/RunSecurityHardeningChecks.php'] as $Necesario) {
    $Revisiones++;
    if (!is_file($Root . '/' . $Necesario)) { $Errores[] = "Falta archivo requerido: $Necesario"; }
}

foreach (['DescargarPlantilla.php','modules/DescargarPlantilla.php','storage/templates','assets/js/admin/AdminConfirmaciones.js'] as $Retirado) {
    $Revisiones++;
    if (file_exists($Root . '/' . $Retirado)) { $Errores[] = "Elemento retirado todavía presente: $Retirado"; }
}

if ($Errores) {
    echo "SGCE STATIC CHECKS: ERROR\n" . implode("\n", $Errores) . "\n";
    exit(1);
}
echo "SGCE STATIC CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
