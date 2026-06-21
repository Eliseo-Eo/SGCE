<?php
return [
    'host' => 'localhost',
    'database' => 'sgce',
    'username' => 'usuario_mysql',
    'password' => 'password_mysql',
    'charset' => 'utf8mb4',
    'timezone' => 'America/Mexico_City',
    'backup_dir' => __DIR__ . '/../storage/backups',
    'log_dir' => __DIR__ . '/../storage/logs',
    'planeaciones_dir' => __DIR__ . '/../storage/planeaciones',
    'base_url' => '', // Ejemplo: https://sgce.tu-dominio.com/
    'force_https' => false,
    'trusted_proxy_headers' => false,
    'trusted_proxies' => '', // Ejemplo: 127.0.0.1,10.0.0.10
    // El instalador genera automáticamente una clave hexadecimal de 64 bytes.
    // Si configuras manualmente, usa: php -r "echo bin2hex(random_bytes(64)), PHP_EOL;"
    'backup_signing_key' => '',
    'production' => true,
];
