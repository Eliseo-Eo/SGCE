<?php
// Uso CLI: php tools/ReindexarBusqueda.php
// Regenera columnas de búsqueda sin acentos para registros existentes.
define('SGCE_APP', true);
require dirname(__DIR__) . '/config/Conexion.php';
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('Solo CLI.'); }
$Resultado = SgceReindexarBusquedasNormalizadas($Pdo, 5000);
echo "Reindexación de búsqueda:\n";
foreach ($Resultado as $Tabla => $Total) { echo "- {$Tabla}: {$Total}\n"; }
