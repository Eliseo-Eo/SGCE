<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

define('SGCE_APP', true);
require dirname(__DIR__) . '/config/Conexion.php';

$Lote = isset($argv[1]) ? max(100, min(50000, (int)$argv[1])) : 5000;
$LockDir = dirname(__DIR__) . '/storage/locks';
if (!is_dir($LockDir) && !@mkdir($LockDir, 0775, true) && !is_dir($LockDir)) {
    fwrite(STDERR, 'SGCE: no se pudo crear carpeta de locks.' . PHP_EOL);
    exit(1);
}
$LockPath = $LockDir . '/archivar_asistencias.lock';
$LockHandle = fopen($LockPath, 'c');
if (!$LockHandle) {
    fwrite(STDERR, 'SGCE: no se pudo abrir lock de asistencias.' . PHP_EOL);
    exit(1);
}
if (!flock($LockHandle, LOCK_EX | LOCK_NB)) {
    echo 'SGCE: ya hay archivado de asistencias en ejecución. | ' . date('Y-m-d H:i:s') . PHP_EOL;
    exit(0);
}
try {
    $Movidos = SgceAsistenciasArchivarCiclosCerrados($Pdo, $Lote);
    echo 'SGCE asistencias archivadas. Lote: ' . $Lote . '. Registros movidos: ' . $Movidos . ' | ' . date('Y-m-d H:i:s') . PHP_EOL;
} catch (Throwable $E) {
    $Codigo = function_exists('SgceRegistrarErrorTecnico') ? SgceRegistrarErrorTecnico('CRON_ARCHIVAR_ASISTENCIAS', $E) : 'sin-log';
    fwrite(STDERR, 'SGCE: falló archivado de asistencias. Código: ' . $Codigo . PHP_EOL);
    exit(1);
} finally {
    flock($LockHandle, LOCK_UN);
    fclose($LockHandle);
}
