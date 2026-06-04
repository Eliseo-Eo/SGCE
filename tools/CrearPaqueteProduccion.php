<?php
define('SGCE_APP', true);

$Root = dirname(__DIR__);
$Salida = $argv[1] ?? ($Root . '/SGCE_Produccion_1.0.91.zip');
$Excluir = [
    '/tests/',
    '/tools/',
    '/.git/',
    '/.gitignore',
    '/.sgce-production-exclude',
];

if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "La extensión ZIP de PHP no está disponible.\n");
    exit(1);
}

function SgceProduccionDebeExcluir(string $Relativo, array $Excluir): bool {
    $RelativoNormalizado = '/' . str_replace('\\', '/', ltrim($Relativo, '/'));
    foreach ($Excluir as $Item) {
        if (str_ends_with($Item, '/')) {
            if (str_starts_with($RelativoNormalizado . '/', $Item)) { return true; }
        } elseif ($RelativoNormalizado === $Item) {
            return true;
        }
    }
    return false;
}

$Zip = new ZipArchive();
if ($Zip->open($Salida, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "No se pudo crear el ZIP de producción.\n");
    exit(1);
}

$Iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Root, FilesystemIterator::SKIP_DOTS));
foreach ($Iterator as $Archivo) {
    if (!$Archivo->isFile()) { continue; }
    $Ruta = $Archivo->getPathname();
    if ($Ruta === $Salida) { continue; }
    $Relativo = substr($Ruta, strlen($Root) + 1);
    if (SgceProduccionDebeExcluir($Relativo, $Excluir)) { continue; }
    $Zip->addFile($Ruta, $Relativo);
}
$Zip->close();

echo "Paquete de producción creado: {$Salida}\n";
