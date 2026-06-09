<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

if (in_array('--self-check', $argv ?? [], true)) {
    $Base = dirname(__DIR__);
    $Requeridos = [
        $Base . '/config/Conexion.php',
        $Base . '/includes/SGCE_Mantenimiento.php',
        $Base . '/storage',
    ];
    foreach ($Requeridos as $Ruta) {
        if (!file_exists($Ruta)) {
            fwrite(STDERR, 'SGCE mantenimiento: falta ' . $Ruta . PHP_EOL);
            exit(1);
        }
    }
    echo 'SGCE mantenimiento: self-check OK' . PHP_EOL;
    exit(0);
}

define('SGCE_APP', true);
require_once dirname(__DIR__) . '/config/Conexion.php';

$LockDir = defined('SGCE_STORAGE_DIR') ? SGCE_STORAGE_DIR . '/locks' : dirname(__DIR__) . '/storage/locks';
if (!is_dir($LockDir) && !@mkdir($LockDir, 0775, true) && !is_dir($LockDir)) {
    fwrite(STDERR, 'SGCE: no se pudo crear carpeta de locks.' . PHP_EOL);
    exit(1);
}
$LockPath = $LockDir . '/mantenimiento_diario.lock';
$LockHandle = fopen($LockPath, 'c');
if (!$LockHandle) {
    fwrite(STDERR, 'SGCE: no se pudo abrir lock de mantenimiento.' . PHP_EOL);
    exit(1);
}
if (!flock($LockHandle, LOCK_EX | LOCK_NB)) {
    echo 'SGCE: ya hay mantenimiento diario en ejecución. | ' . date('Y-m-d H:i:s') . PHP_EOL;
    exit(0);
}

try {
    $Resultado = SgceMantenimientoDiario($Pdo, [
        'DiasBitacora' => isset($argv[1]) && is_numeric($argv[1]) ? (int)$argv[1] : 365,
        'DiasIntentos' => 30,
        'DiasRespaldosTemporales' => 7,
    ]);
    echo 'SGCE mantenimiento diario completado: ' . json_encode($Resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $E) {
    $Codigo = function_exists('SgceRegistrarErrorTecnico') ? SgceRegistrarErrorTecnico('CRON_MANTENIMIENTO_DIARIO', $E) : 'sin-log';
    fwrite(STDERR, 'SGCE: falló mantenimiento diario. Código: ' . $Codigo . PHP_EOL);
    exit(1);
} finally {
    flock($LockHandle, LOCK_UN);
    fclose($LockHandle);
}
