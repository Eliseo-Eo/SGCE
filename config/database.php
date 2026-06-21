<?php
declare(strict_types=1);

$ConfigCandidates = [];
$EnvConfig = trim((string)(getenv('SGCE_CONFIG_FILE') ?: ''));
if ($EnvConfig !== '') { $ConfigCandidates[] = $EnvConfig; }
$ConfigCandidates[] = __DIR__ . '/database.local.php';
$ConfigCandidates[] = dirname(__DIR__) . '/../sgce_private/database.local.php';

foreach ($ConfigCandidates as $LocalConfig) {
    if (!is_file($LocalConfig)) { continue; }
    $ConfigLocal = require $LocalConfig;
    if (!is_array($ConfigLocal)) {
        throw new RuntimeException('El archivo de configuración local no devuelve un arreglo válido.');
    }
    return $ConfigLocal;
}

$RequiredEnv = ['SGCE_DB_HOST', 'SGCE_DB_NAME', 'SGCE_DB_USER', 'SGCE_DB_PASS'];
foreach ($RequiredEnv as $EnvKey) {
    $Valor = getenv($EnvKey);
    if ($Valor === false || trim((string)$Valor) === '') {
        throw new RuntimeException('SGCE no está instalado o falta configuración segura de base de datos. Ejecuta Instalar.php o define SGCE_CONFIG_FILE.');
    }
}

$Root = dirname(__DIR__);

return [
    'host' => (string)getenv('SGCE_DB_HOST'),
    'database' => (string)getenv('SGCE_DB_NAME'),
    'username' => (string)getenv('SGCE_DB_USER'),
    'password' => (string)getenv('SGCE_DB_PASS'),
    'charset' => getenv('SGCE_DB_CHARSET') ?: 'utf8mb4',
    'timezone' => getenv('SGCE_TIMEZONE') ?: 'America/Mexico_City',
    'backup_dir' => getenv('SGCE_BACKUP_DIR') ?: $Root . '/storage/backups',
    'log_dir' => getenv('SGCE_LOG_DIR') ?: $Root . '/storage/logs',
    'planeaciones_dir' => getenv('SGCE_PLANEACIONES_DIR') ?: $Root . '/storage/planeaciones',
    'base_url' => getenv('SGCE_BASE_URL') ?: '',
    'force_https' => filter_var(getenv('SGCE_FORCE_HTTPS') ?: '0', FILTER_VALIDATE_BOOLEAN),
    'trusted_proxy_headers' => filter_var(getenv('SGCE_TRUST_PROXY_HEADERS') ?: '0', FILTER_VALIDATE_BOOLEAN),
    'trusted_proxies' => getenv('SGCE_TRUSTED_PROXIES') ?: '',
    'backup_signing_key' => getenv('SGCE_BACKUP_SIGNING_KEY') ?: '',
    'production' => (getenv('SGCE_ENV') ?: 'production') === 'production',
];
