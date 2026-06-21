<?php
/**
 * SGCE - Autoload progresivo.
 *
 * Prioridad:
 * 1. Si existe vendor/autoload.php generado por Composer, se carga.
 * 2. Siempre se registra un fallback PSR-4 para Sgce\ sobre src/.
 *
 * Esto permite instalar desde cero sin ejecutar Composer en el servidor,
 * pero también permite usar Composer de forma estándar en desarrollo.
 */
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli' && !defined('SGCE_INSTALLER')) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

static $SgceAutoloadRegistrado = false;
if ($SgceAutoloadRegistrado) { return; }
$SgceAutoloadRegistrado = true;

$SgceRootAutoload = dirname(__DIR__);
$SgceComposerAutoload = $SgceRootAutoload . '/vendor/autoload.php';
if (is_file($SgceComposerAutoload)) {
    require_once $SgceComposerAutoload;
}

spl_autoload_register(static function (string $Clase) use ($SgceRootAutoload): void {
    $Prefijo = 'Sgce\\';
    if (strncmp($Clase, $Prefijo, strlen($Prefijo)) !== 0) { return; }
    $Relativa = substr($Clase, strlen($Prefijo));
    $Archivo = $SgceRootAutoload . '/src/' . str_replace('\\', '/', $Relativa) . '.php';
    if (is_file($Archivo)) { require_once $Archivo; }
});
