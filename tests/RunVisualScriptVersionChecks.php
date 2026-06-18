<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }
$Root = dirname(__DIR__);
$Errores = [];
foreach (['tools/visual-mobile-smoke.sh','tools/visual-mobile-smoke.py'] as $Rel) {
    $Path = $Root . DIRECTORY_SEPARATOR . $Rel;
    if (!is_file($Path)) { $Errores[] = "Falta $Rel"; continue; }
    $Contenido = file_get_contents($Path);
    if (!str_contains($Contenido, '1.0.185')) { $Errores[] = "$Rel no declara versión 1.0.185"; }
    $PatronViejo = '/' . '1\\.0\\.(?:13[0-9]|12[0-9])' . '|' . '138' . 'c' . '|' . '138' . 'b' . '/';
    if (preg_match($PatronViejo, $Contenido)) { $Errores[] = "$Rel conserva versión previa"; }
}
$Py = file_get_contents($Root . '/tools/visual-mobile-smoke.py');
foreach (['SGCE_VISUAL_AUTH_TOKEN','SGCE_VISUAL_LOGIN_USER','SGCE_VISUAL_LOGIN_PASSWORD','AuthToken','Admin.php?Tab=inicio'] as $Needle) {
    if (!str_contains($Py, $Needle)) { $Errores[] = "Script visual no soporta: $Needle"; }
}
if ($Errores) { echo "SGCE VISUAL SCRIPT VERSION CHECKS: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "SGCE VISUAL SCRIPT VERSION CHECKS: OK\n";
