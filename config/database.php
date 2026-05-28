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
    'database' => getenv('SGCE_DB_NAME') ?: 'ControlEscolar',
    'username' => getenv('SGCE_DB_USER') ?: 'root',
    'password' => getenv('SGCE_DB_PASS') ?: '',
    'charset' => getenv('SGCE_DB_CHARSET') ?: 'utf8mb4',
    'timezone' => getenv('SGCE_TIMEZONE') ?: 'America/Mexico_City',
    'backup_dir' => getenv('SGCE_BACKUP_DIR') ?: dirname(__DIR__) . '/storage/backups',
    'log_dir' => getenv('SGCE_LOG_DIR') ?: dirname(__DIR__) . '/storage/logs',
    'production' => (getenv('SGCE_ENV') ?: 'production') === 'production',
];
