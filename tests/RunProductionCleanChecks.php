<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }
$Project = dirname(dirname(__DIR__));
$Prod = $Project . DIRECTORY_SEPARATOR . 'Produccion';
$Errores = [];
foreach (['tests','tools'] as $Dir) {
    if (is_dir($Prod . DIRECTORY_SEPARATOR . $Dir)) { $Errores[] = "Producción contiene carpeta interna: $Dir"; }
}
$Iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Prod, FilesystemIterator::SKIP_DOTS));
foreach ($Iterador as $Archivo) {
    if (!$Archivo->isFile()) { continue; }
    $Rel = str_replace($Prod . DIRECTORY_SEPARATOR, '', $Archivo->getPathname());
    if (preg_match('/(Run[A-Za-z0-9]+Checks\.php|visual-mobile-smoke|fixture|\.bak$|\.old$|\.orig$)/i', $Rel)) { $Errores[] = "Archivo de desarrollo en Producción: $Rel"; }
}
$Archivos = file_get_contents($Prod . '/includes/SGCE_Archivos.php');
if (!str_contains($Archivos, 'if (is_dir($ToolsDir))')) { $Errores[] = 'SGCE_Archivos.php no protege tools de forma condicional.'; }
if ($Errores) { echo "SGCE PRODUCTION CLEAN CHECKS: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "SGCE PRODUCTION CLEAN CHECKS: OK\n";
