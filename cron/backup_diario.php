<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

define('SGCE_APP', true);
require_once dirname(__DIR__) . '/config/Conexion.php';

try {
    $Archivo = SgceGenerarBackupAutomatico($Pdo, 'diario', 14);
    echo 'SGCE: respaldo diario generado/verificado correctamente: ' . $Archivo . ' | ' . date('Y-m-d H:i:s') . PHP_EOL;
} catch (Throwable $E) {
    $Codigo = function_exists('SgceRegistrarErrorTecnico') ? SgceRegistrarErrorTecnico('CRON_BACKUP_DIARIO', $E) : 'sin-log';
    fwrite(STDERR, 'SGCE: falló el respaldo diario. Código: ' . $Codigo . PHP_EOL);
    exit(1);
}
