<?php
$Root = dirname(__DIR__);
$Project = dirname($Root);
$Errores = [];
$Revisiones = 0;

$VersionActual = '1.0.122';
$Raices = ['Produccion', 'Desarrollo'];

foreach ($Raices as $Raiz) {
    $Base = $Project . DIRECTORY_SEPARATOR . $Raiz;
    if (!is_dir($Base)) { $Errores[] = "Falta carpeta requerida: $Raiz"; continue; }
    foreach (['README.md', 'CAMBIOS_' . $VersionActual . '.md', 'VERSION.txt', 'docs/MIGRACION_CICLO_ESCOLAR.md', 'docs/RENDIMIENTO_10_ANIOS.md'] as $Relativo) {
        $Revisiones++;
        if (!is_file($Base . DIRECTORY_SEPARATOR . $Relativo)) { $Errores[] = "Falta archivo limpio en $Raiz: $Relativo"; }
    }
    foreach (glob($Base . DIRECTORY_SEPARATOR . 'CAMBIOS_1.0.11*.md') ?: [] as $ArchivoCambio) {
        $Revisiones++;
        if (!str_ends_with($ArchivoCambio, 'CAMBIOS_' . $VersionActual . '.md')) {
            $Errores[] = 'Changelog viejo no permitido: ' . str_replace($Project . DIRECTORY_SEPARATOR, '', $ArchivoCambio);
        }
    }
    foreach (glob($Base . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'SQL_MIGRACION_*.sql') ?: [] as $ArchivoSqlViejo) {
        $Revisiones++;
        $Errores[] = 'SQL de migración viejo no permitido en paquete limpio: ' . str_replace($Project . DIRECTORY_SEPARATOR, '', $ArchivoSqlViejo);
    }
}


$FuncionesRetiradas = ['SgceLayoutAdminJs', 'SgceReindexarBusquedasNormalizadas', 'function SgceMigrarGrupoSiguienteCiclo('];
foreach ($Raices as $Raiz) {
    $Base = $Project . DIRECTORY_SEPARATOR . $Raiz;
    $IteradorFunciones = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Base, FilesystemIterator::SKIP_DOTS));
    foreach ($IteradorFunciones as $ArchivoFuncion) {
        if (!$ArchivoFuncion->isFile() || strtolower($ArchivoFuncion->getExtension()) !== 'php') { continue; }
        if (str_ends_with($ArchivoFuncion->getPathname(), DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'RunPackageCleanChecks.php')) { continue; }
        $RelFuncion = str_replace($Project . DIRECTORY_SEPARATOR, '', $ArchivoFuncion->getPathname());
        $ContenidoFuncion = file_get_contents($ArchivoFuncion->getPathname());
        foreach ($FuncionesRetiradas as $FuncionRetirada) {
            $Revisiones++;
            if (str_contains($ContenidoFuncion, $FuncionRetirada)) { $Errores[] = "Función/código retirado todavía presente en $RelFuncion: $FuncionRetirada"; }
        }
    }
}

$Iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Project, FilesystemIterator::SKIP_DOTS));
foreach ($Iterador as $Archivo) {
    if (!$Archivo->isFile()) { continue; }
    $Ruta = $Archivo->getPathname();
    $Rel = str_replace($Project . DIRECTORY_SEPARATOR, '', $Ruta);
    if (!preg_match('/\.(php|css|js|md|txt|sql)$/i', $Rel)) { continue; }
    if (str_contains($Rel, 'tests/RunPackageCleanChecks.php')) { continue; }
    $Contenido = file_get_contents($Ruta);
    $Revisiones++;
    if (preg_match('/1\.0\.(?:11[5-9]|120|121m?)/', $Contenido, $Coincidencia)) {
        $Errores[] = 'Referencia obsoleta ' . $Coincidencia[0] . ' en ' . $Rel;
    }
}

if ($Errores) {
    echo "SGCE PACKAGE CLEAN CHECKS: ERROR\n" . implode("\n", $Errores) . "\n";
    exit(1);
}

echo "SGCE PACKAGE CLEAN CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
