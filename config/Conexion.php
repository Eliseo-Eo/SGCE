<?php

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

$Config = require __DIR__ . '/database.php';
date_default_timezone_set($Config['timezone'] ?? 'America/Mexico_City');

$Host = $Config['host'];
$Db = $Config['database'];
$User = $Config['username'];
$Pass = $Config['password'];
$Charset = $Config['charset'] ?? 'utf8mb4';
if (!defined('SGCE_BACKUP_DIR')) { define('SGCE_BACKUP_DIR', $Config['backup_dir'] ?? dirname(__DIR__) . '/storage/backups'); }
if (!defined('SGCE_LOG_DIR')) { define('SGCE_LOG_DIR', $Config['log_dir'] ?? dirname(__DIR__) . '/storage/logs'); }
if (!defined('SGCE_PLANEACIONES_DIR')) { define('SGCE_PLANEACIONES_DIR', $Config['planeaciones_dir'] ?? dirname(__DIR__) . '/storage/planeaciones'); }
if (!defined('SGCE_PRODUCTION')) { define('SGCE_PRODUCTION', (bool)($Config['production'] ?? true)); }
require_once dirname(__DIR__) . '/includes/SGCE_ErrorHandler.php';
$Dsn = "mysql:host={$Host};dbname={$Db};charset={$Charset}";

$Options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $Pdo = new PDO($Dsn, $User, $Pass, $Options);
} catch (PDOException $E) {
    $CodigoError = function_exists('SgceRegistrarErrorTecnico') ? SgceRegistrarErrorTecnico('CONEXION_BASE_DATOS', $E) : '';
    if (function_exists('SgceMostrarErrorCliente')) { SgceMostrarErrorCliente($CodigoError); exit; }
    http_response_code(500);
    exit('No fue posible conectar con la base de datos.');
}

require_once dirname(__DIR__) . '/includes/SGCE_Helpers.php';

IniciarSesionSegura();
EnviarHeadersSeguridad();
