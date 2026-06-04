<?php
// Uso CLI: php tools/AplicarMigraciones.php
// No migra datos entre sistemas; solo aplica cambios versionados del esquema SGCE cuando una instalación ya existe.
define('SGCE_APP', true);
require dirname(__DIR__) . '/config/Conexion.php';
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('Solo CLI.'); }
$Directorio = dirname(__DIR__) . '/install/migrations';
$Aplicadas = SgceMigracionesAplicarPendientes($Pdo, $Directorio);
echo "Migraciones aplicadas: " . count($Aplicadas) . PHP_EOL;
foreach ($Aplicadas as $M) { echo '- ' . $M['Version'] . ' ' . $M['Nombre'] . PHP_EOL; }
