<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }
$Root = dirname(__DIR__);
$Project = dirname($Root);
$Version = '1.0.185';
$Errores = [];
$Extensiones = ['php','css','js','md','txt','sql','sh','html'];
$Iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Project, FilesystemIterator::SKIP_DOTS));
foreach ($Iterador as $Archivo) {
    if (!$Archivo->isFile()) { continue; }
    $Ext = strtolower($Archivo->getExtension());
    if (!in_array($Ext, $Extensiones, true)) { continue; }
    $Rel = str_replace($Project . DIRECTORY_SEPARATOR, '', $Archivo->getPathname());
    if (str_contains($Rel, 'RunFinalVersionTraceChecks.php')) { continue; }
    $Contenido = file_get_contents($Archivo->getPathname());
    $PatronViejo = '/' . '1\\.0\\.(?:14[01]|13[0-9]|12[0-9]|11[0-9])' . '|' . '136' . 'e' . '|' . '138' . 'c' . '|' . '138' . 'b' . '/';
    if (preg_match($PatronViejo, $Contenido, $M)) {
        $Errores[] = 'Rastro de versión previa ' . $M[0] . ' en ' . $Rel;
    }
}
foreach (['Produccion','Desarrollo'] as $Raiz) {
    $VersionFile = $Project . DIRECTORY_SEPARATOR . $Raiz . DIRECTORY_SEPARATOR . 'VERSION.txt';
    if (!is_file($VersionFile) || trim(file_get_contents($VersionFile)) !== $Version) { $Errores[] = "VERSION.txt inválido en $Raiz"; }
}
if ($Errores) { echo "SGCE FINAL VERSION TRACE CHECKS: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "SGCE FINAL VERSION TRACE CHECKS: OK\n";
