<?php
$Root = realpath(__DIR__ . '/..');
$Errors = [];
$Warnings = [];

function SgceFindFiles(string $Root, array $Extensions): array
{
    $Files = [];
    $Iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Root, FilesystemIterator::SKIP_DOTS));
    foreach ($Iterator as $File) {
        if (!$File->isFile()) { continue; }
        $Path = $File->getPathname();
        $Relative = str_replace($Root . DIRECTORY_SEPARATOR, '', $Path);
        if (preg_match('#(^|/)(storage/backups|storage/logs|storage/tmp_uploads)/#', str_replace('\\', '/', $Relative))) { continue; }
        $Ext = strtolower(pathinfo($Path, PATHINFO_EXTENSION));
        if (in_array($Ext, $Extensions, true)) { $Files[] = $Path; }
    }
    sort($Files);
    return $Files;
}

foreach (SgceFindFiles($Root, ['php']) as $File) {
    $Command = 'php -l ' . escapeshellarg($File) . ' 2>&1';
    exec($Command, $Output, $Code);
    if ($Code !== 0) { $Errors[] = 'PHP syntax error: ' . str_replace($Root . DIRECTORY_SEPARATOR, '', $File) . ' => ' . implode(' ', $Output); }
}

foreach (SgceFindFiles($Root, ['js']) as $File) {
    $Command = 'node --check ' . escapeshellarg($File) . ' 2>&1';
    exec($Command, $Output, $Code);
    if ($Code !== 0) { $Errors[] = 'JS syntax error: ' . str_replace($Root . DIRECTORY_SEPARATOR, '', $File) . ' => ' . implode(' ', $Output); }
}

foreach (['favicon.ico', 'favicon.png', 'SGCE.zip'] as $Forbidden) {
    if (file_exists($Root . DIRECTORY_SEPARATOR . $Forbidden)) { $Errors[] = 'Archivo no permitido en raiz: ' . $Forbidden; }
}

$ForbiddenPatterns = ['*.bak', '*.old', '*.tmp', '*.orig', '*.dm'];
foreach ($ForbiddenPatterns as $Pattern) {
    foreach (glob($Root . DIRECTORY_SEPARATOR . $Pattern) ?: [] as $File) { $Errors[] = 'Residuo no permitido: ' . basename($File); }
}

foreach (['assets/media/img/favicon.ico', 'assets/media/img/favicon.png', 'README.md', 'docs/MANUAL_TECNICO_INSTALACION_SGCE.md', 'docs/MANUAL_USUARIO_SGCE.md'] as $Required) {
    if (!file_exists($Root . DIRECTORY_SEPARATOR . $Required)) { $Errors[] = 'Archivo requerido faltante: ' . $Required; }
}

$PhpText = '';
foreach (SgceFindFiles($Root, ['php']) as $File) { $PhpText .= file_get_contents($File) . "\n"; }
preg_match_all('/function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $PhpText, $Matches);
$Counts = array_count_values($Matches[1] ?? []);
foreach ($Counts as $Name => $Count) {
    if ($Count > 1) { $Errors[] = 'Funcion PHP duplicada: ' . $Name . ' (' . $Count . ')'; }
}

$Checks = count(SgceFindFiles($Root, ['php'])) + count(SgceFindFiles($Root, ['js'])) + 6;
if ($Errors) {
    echo "SGCE STATIC CHECKS: ERROR\n";
    foreach ($Errors as $Error) { echo "- " . $Error . "\n"; }
    exit(1);
}

echo "SGCE STATIC CHECKS: OK\n";
echo "Revisiones ejecutadas: " . $Checks . "\n";
echo "Advertencias: " . count($Warnings) . "\n";
exit(0);
