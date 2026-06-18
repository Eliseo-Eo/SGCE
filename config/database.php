<?php
$LocalConfig = __DIR__ . '/database.local.php';
if (is_file($LocalConfig)) {
    $ConfigLocal = require $LocalConfig;
    if (is_array($ConfigLocal)) {
        return $ConfigLocal;
    }
}

return [
    'host' => getenv('SGCE_DB_HOST') ?: 'localhost',
    'database' => getenv('SGCE_DB_NAME') ?: 'sgce',
    'username' => getenv('SGCE_DB_USER') ?: 'root',
    'password' => getenv('SGCE_DB_PASS') ?: '',
    'charset' => getenv('SGCE_DB_CHARSET') ?: 'utf8mb4',
    'timezone' => getenv('SGCE_TIMEZONE') ?: 'America/Mexico_City',
    'backup_dir' => getenv('SGCE_BACKUP_DIR') ?: dirname(__DIR__) . '/storage/backups',
    'log_dir' => getenv('SGCE_LOG_DIR') ?: dirname(__DIR__) . '/storage/logs',
    'planeaciones_dir' => getenv('SGCE_PLANEACIONES_DIR') ?: dirname(__DIR__) . '/storage/planeaciones',
    'base_url' => getenv('SGCE_BASE_URL') ?: '',
    'force_https' => filter_var(getenv('SGCE_FORCE_HTTPS') ?: '0', FILTER_VALIDATE_BOOLEAN),
    'trusted_proxy_headers' => filter_var(getenv('SGCE_TRUST_PROXY_HEADERS') ?: '0', FILTER_VALIDATE_BOOLEAN),
    'trusted_proxies' => getenv('SGCE_TRUSTED_PROXIES') ?: '',
    'production' => (getenv('SGCE_ENV') ?: 'production') === 'production',
];
