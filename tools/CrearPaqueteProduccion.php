<?php
$Root = dirname(__DIR__);
$Salida = $argv[1] ?? ($Root . '/../SGCE_Produccion_Generado.zip');
$Excluir = ['tests/','tools/','.git/'];
$Zip = new ZipArchive();
if ($Zip->open($Salida, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { fwrite(STDERR, "No se pudo crear ZIP.\n"); exit(1); }
$Iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Root, FilesystemIterator::SKIP_DOTS));
foreach ($Iterador as $Archivo) {
    if (!$Archivo->isFile()) { continue; }
    $Ruta = $Archivo->getPathname();
    $Rel = str_replace($Root . DIRECTORY_SEPARATOR, '', $Ruta);
    $RelNorm = str_replace('\\', '/', $Rel);
    $Omitir = false;
    foreach ($Excluir as $Patron) { if (str_starts_with($RelNorm, $Patron)) { $Omitir = true; break; } }
    if ($Omitir) { continue; }
    $Zip->addFile($Ruta, $RelNorm);
}
$Zip->close();
echo "Paquete de producción generado: $Salida\n";
