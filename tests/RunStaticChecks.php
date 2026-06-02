<?php
$Root = dirname(__DIR__);
$Errores = [];
$Revisiones = 0;
function Check($Condicion, $Mensaje) { global $Errores, $Revisiones; $Revisiones++; if (!$Condicion) { $Errores[] = $Mensaje; } }
$PhpFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Root));
foreach ($PhpFiles as $File) {
    if (!$File->isFile() || strtolower($File->getExtension()) !== 'php') { continue; }
    $Path = $File->getPathname();
    $Out = [];$Code = 0;exec('php -l ' . escapeshellarg($Path) . ' 2>&1', $Out, $Code);
    Check($Code === 0, 'PHP lint falló: ' . str_replace($Root . DIRECTORY_SEPARATOR, '', $Path) . ' | ' . implode(' ', $Out));
}
$JsFiles = glob($Root . '/assets/js/*.js') ?: [];
foreach ($JsFiles as $Path) {
    $Out = [];$Code = 0;exec('node --check ' . escapeshellarg($Path) . ' 2>&1', $Out, $Code);
    Check($Code === 0, 'JS syntax falló: ' . str_replace($Root . DIRECTORY_SEPARATOR, '', $Path) . ' | ' . implode(' ', $Out));
}
Check(!file_exists($Root . '/favicon.ico'), 'No debe existir favicon.ico en raíz.');
Check(!file_exists($Root . '/favicon.png'), 'No debe existir favicon.png en raíz.');
Check(file_exists($Root . '/assets/media/img/favicon.ico'), 'Falta favicon centralizado.');
Check(is_dir($Root . '/repositories'), 'Falta carpeta repositories.');
Check(file_exists($Root . '/repositories/SGCE_RepositoryLoader.php'), 'Falta loader de repositorios.');
$Forbidden = ['.bak','.old','.tmp','.orig','.dm'];
$Iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Root));
foreach ($Iterator as $File) {
    if (!$File->isFile()) { continue; }
    $Rel = str_replace($Root . DIRECTORY_SEPARATOR, '', $File->getPathname());
    Check(strtolower($File->getExtension()) !== 'zip', 'No debe haber ZIP interno: ' . $Rel);
    foreach ($Forbidden as $Ext) { Check(substr(strtolower($Rel), -strlen($Ext)) !== $Ext, 'Archivo residual: ' . $Rel); }
}
$TextFiles = glob($Root . '/**/*.{php,js,css,md,txt,sql}', GLOB_BRACE) ?: [];
$EncodedForbidden = ['c2djZTIwMjY=','c2djZS1maW5hbA==','bW9kYWxmaXg=','bW90aW9uX2dpdGh1Yg==','Y29uc3VsdGFfZWZlY3Rv','Y2FuY2VsX21vZGFs','cGFyY2hlIHRlbXBvcmFs'];
foreach ($TextFiles as $Path) {
    $Rel = str_replace($Root . DIRECTORY_SEPARATOR, '', $Path);
    if ($Rel === 'tests/RunStaticChecks.php') { continue; }
    $Content = @file_get_contents($Path); if ($Content === false) { continue; }
    foreach ($EncodedForbidden as $Word64) { $Word = base64_decode($Word64); Check(stripos($Content, $Word) === false, 'Rastro anterior detectado en ' . $Rel); }
}
$Sql = file_get_contents($Root . '/install/SGCE.sql');
foreach (['idx_alumnos_activo_grupo_nombre','idx_asignaciones_activo_maestro_grupo_materia','idx_asistencias_rango_reporte','idx_bitacora_fecha_id'] as $Idx) { Check(strpos($Sql, $Idx) !== false, 'Falta índice: ' . $Idx); }
if ($Errores) { echo "SGCE STATIC CHECKS: ERROR
" . implode("
", $Errores) . "
"; exit(1); }
echo "SGCE STATIC CHECKS: OK
Revisiones ejecutadas: {$Revisiones}
";
