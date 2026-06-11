<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }
$Root = dirname(__DIR__);
$Errores = [];
$Iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Root . '/docs', FilesystemIterator::SKIP_DOTS));
foreach ($Iterador as $Archivo) {
    if (!$Archivo->isFile() || strtolower($Archivo->getExtension()) !== 'md') { continue; }
    $BaseDir = dirname($Archivo->getPathname());
    $RelDoc = str_replace($Root . DIRECTORY_SEPARATOR, '', $Archivo->getPathname());
    $Contenido = file_get_contents($Archivo->getPathname());
    if (preg_match_all('/`([^`]+\.(?:pdf|docx|md|txt|sql|sh))`/', $Contenido, $Matches)) {
        foreach ($Matches[1] as $Link) {
            if (preg_match('/^(https?:)?\/\//', $Link)) { continue; }
            $Ruta = $Link;
            if (str_starts_with($Ruta, 'docs/')) { $Destino = $Root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $Ruta); }
            else { $Destino = $BaseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $Ruta); }
            if (!is_file($Destino)) { $Errores[] = "Enlace roto en $RelDoc: $Link"; }
        }
    }
}
if ($Errores) { echo "SGCE DOCUMENTATION LINKS CHECKS: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "SGCE DOCUMENTATION LINKS CHECKS: OK\n";
