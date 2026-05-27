<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

require_once dirname(__DIR__) . '/config/Conexion.php';
SgceGenerarBackupAutomatico($Pdo);
echo 'SGCE: respaldo diario verificado correctamente. ' . date('Y-m-d H:i:s') . PHP_EOL;
