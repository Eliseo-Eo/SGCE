<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

define('SGCE_APP', true);
require_once dirname(__DIR__) . '/config/Conexion.php';

try {
    $Archivo = SgceGenerarBackupAutomatico($Pdo, 'semanal', 8);
    echo 'SGCE: respaldo semanal generado/verificado correctamente: ' . $Archivo . ' | ' . date('Y-m-d H:i:s') . PHP_EOL;
} catch (Throwable $E) {
    $Codigo = function_exists('SgceRegistrarErrorTecnico') ? SgceRegistrarErrorTecnico('CRON_BACKUP_SEMANAL', $E) : 'sin-log';
    fwrite(STDERR, 'SGCE: falló el respaldo semanal. Código: ' . $Codigo . PHP_EOL);
    exit(1);
}
