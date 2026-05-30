<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('Uso CLI únicamente.'); }

$Raiz = dirname(__DIR__, 2);
$Partes = [
    '00-core.css',
    '10-login-dashboard.css',
    '20-admin-catalogos.css',
    '30-reportes-avisos-expediente.css',
    '40-docente-publico.css',
    '50-configuracion-ajustes.css',
    '90-ajustes-finales.css',
];
$Css = '';
foreach ($Partes as $Parte) {
    $Ruta = __DIR__ . '/src/' . $Parte;
    if (!is_file($Ruta)) { fwrite(STDERR, "Falta $Parte\n"); exit(1); }
    $Css .= file_get_contents($Ruta) . "\n";
}
file_put_contents(__DIR__ . '/sgce-base.css', $Css);
$Min = preg_replace('!/\*.*?\*/!s', '', $Css);
$Min = preg_replace('/\s+/', ' ', $Min);
$Min = preg_replace('/\s*([{}:;,>])\s*/', '$1', $Min);
$Min = trim($Min);
file_put_contents(__DIR__ . '/sgce-base.min.css', $Min);
echo "CSS compilado correctamente.\n";
