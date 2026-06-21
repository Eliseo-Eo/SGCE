<?php

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('expose_php', '0');
error_reporting(E_ALL);


$SgceBootstrap = dirname(__DIR__) . '/app/bootstrap.php';
if (is_file($SgceBootstrap)) { require_once $SgceBootstrap; }
if (!defined('SGCE_VERSION')) {
    define('SGCE_VERSION', class_exists('Sgce\\Foundation\\Version') ? \Sgce\Foundation\Version::current() : '0.0.0');
}



ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', '86400');

$Config = require __DIR__ . '/database.php';
date_default_timezone_set($Config['timezone'] ?? 'America/Mexico_City');

$Host = $Config['host'];
$Db = $Config['database'];
$User = $Config['username'];
$Pass = $Config['password'];
$Charset = $Config['charset'] ?? 'utf8mb4';
if (!defined('SGCE_BACKUP_DIR')) {
    define('SGCE_BACKUP_DIR', $Config['backup_dir'] ?? (class_exists('Sgce\Foundation\Path') ? \Sgce\Foundation\Path::backups() : dirname(__DIR__) . '/storage/backups'));
}
if (!defined('SGCE_LOG_DIR')) {
    define('SGCE_LOG_DIR', $Config['log_dir'] ?? (class_exists('Sgce\Foundation\Path') ? \Sgce\Foundation\Path::logs() : dirname(__DIR__) . '/storage/logs'));
}
if (!defined('SGCE_PLANEACIONES_DIR')) {
    define('SGCE_PLANEACIONES_DIR', $Config['planeaciones_dir'] ?? (class_exists('Sgce\Foundation\Path') ? \Sgce\Foundation\Path::planeaciones() : dirname(__DIR__) . '/storage/planeaciones'));
}
$SgceBoolConfig = static function ($Valor): bool {
    if (is_bool($Valor)) { return $Valor; }
    return in_array(strtolower(trim((string)$Valor)), ['1','true','on','yes','si','sí'], true);
};
if (!defined('SGCE_BASE_URL')) { define('SGCE_BASE_URL', trim((string)($Config['base_url'] ?? getenv('SGCE_BASE_URL') ?: ''))); }
if (!defined('SGCE_FORCE_HTTPS')) { define('SGCE_FORCE_HTTPS', $SgceBoolConfig($Config['force_https'] ?? getenv('SGCE_FORCE_HTTPS') ?: false)); }
if (!defined('SGCE_TRUST_PROXY_HEADERS')) { define('SGCE_TRUST_PROXY_HEADERS', $SgceBoolConfig($Config['trusted_proxy_headers'] ?? getenv('SGCE_TRUST_PROXY_HEADERS') ?: false)); }
if (!defined('SGCE_TRUSTED_PROXIES')) { define('SGCE_TRUSTED_PROXIES', trim((string)($Config['trusted_proxies'] ?? getenv('SGCE_TRUSTED_PROXIES') ?: ''))); }
if (!defined('SGCE_BACKUP_SIGNING_KEY')) { define('SGCE_BACKUP_SIGNING_KEY', trim((string)($Config['backup_signing_key'] ?? getenv('SGCE_BACKUP_SIGNING_KEY') ?: ''))); }
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
require_once dirname(__DIR__) . '/includes/SGCE_SearchHelpers.php';
require_once dirname(__DIR__) . '/includes/SGCE_ImportacionReportes.php';
require_once dirname(__DIR__) . '/includes/SGCE_Mantenimiento.php';
require_once dirname(__DIR__) . '/includes/SGCE_Academico.php';
if (function_exists('SgceForzarHttpsRedirect')) { SgceForzarHttpsRedirect(); }
SgcePrepararDirectoriosSeguros();
IniciarSesionSegura();
EnviarHeadersSeguridad();
