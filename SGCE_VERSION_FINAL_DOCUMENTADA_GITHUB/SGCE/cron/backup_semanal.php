<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

define('SGCE_APP', true);
require_once dirname(__DIR__) . '/config/Conexion.php';

$LockDir = defined('SGCE_STORAGE_DIR') ? SGCE_STORAGE_DIR . '/locks' : dirname(__DIR__) . '/storage/locks';
if (!is_dir($LockDir) && !@mkdir($LockDir, 0775, true) && !is_dir($LockDir)) {
    fwrite(STDERR, 'SGCE: no se pudo crear carpeta de locks.' . PHP_EOL);
    exit(1);
}
$LockPath = $LockDir . '/backup_semanal.lock';
$LockHandle = fopen($LockPath, 'c');
if (!$LockHandle) {
    fwrite(STDERR, 'SGCE: no se pudo abrir lock de respaldo.' . PHP_EOL);
    exit(1);
}

if (!flock($LockHandle, LOCK_EX | LOCK_NB)) {
    echo 'SGCE: ya hay un respaldo semanal ejecutándose. No se inicia otro proceso. | ' . date('Y-m-d H:i:s') . PHP_EOL;
    exit(0);
}

try {
    $Archivo = SgceGenerarBackupAutomatico($Pdo, 'semanal', 8);
    echo 'SGCE: respaldo semanal generado/verificado correctamente: ' . $Archivo . ' | ' . date('Y-m-d H:i:s') . PHP_EOL;
} catch (Throwable $E) {
    $Codigo = function_exists('SgceRegistrarErrorTecnico') ? SgceRegistrarErrorTecnico('CRON_BACKUP_SEMANAL', $E) : 'sin-log';
    fwrite(STDERR, 'SGCE: falló el respaldo semanal. Código: ' . $Codigo . PHP_EOL);
    exit(1);
} finally {
    flock($LockHandle, LOCK_UN);
    fclose($LockHandle);
}
