<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceMigracionesCrearTabla(PDO $Pdo): void {
    $Pdo->exec("CREATE TABLE IF NOT EXISTS SchemaMigrations (
        Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        Version VARCHAR(40) NOT NULL,
        Nombre VARCHAR(180) NOT NULL,
        Checksum CHAR(64) NOT NULL DEFAULT '',
        AplicadaEn TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unico_schema_migration_version (Version),
        INDEX idx_schema_migrations_aplicada (AplicadaEn)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function SgceMigracionAplicada(PDO $Pdo, string $Version): bool {
    SgceMigracionesCrearTabla($Pdo);
    $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM SchemaMigrations WHERE Version = ?');
    $Stmt->execute([$Version]);
    return (int)$Stmt->fetchColumn() > 0;
}

function SgceMigracionRegistrar(PDO $Pdo, string $Version, string $Nombre, string $Checksum = ''): void {
    SgceMigracionesCrearTabla($Pdo);
    $Stmt = $Pdo->prepare('INSERT IGNORE INTO SchemaMigrations (Version, Nombre, Checksum) VALUES (?, ?, ?)');
    $Stmt->execute([$Version, $Nombre, $Checksum]);
}

function SgceMigracionesDisponibles(string $Directorio): array {
    if (!is_dir($Directorio)) { return []; }
    $Archivos = glob(rtrim($Directorio, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql') ?: [];
    sort($Archivos, SORT_NATURAL);
    $Migraciones = [];
    foreach ($Archivos as $Archivo) {
        $Base = basename($Archivo, '.sql');
        $Version = preg_replace('/[^0-9A-Za-z_.-]/', '', $Base);
        if ($Version === '') { continue; }
        $Migraciones[] = [
            'Version' => $Version,
            'Nombre' => $Base,
            'Ruta' => $Archivo,
            'Checksum' => hash_file('sha256', $Archivo) ?: '',
        ];
    }
    return $Migraciones;
}

function SgceMigracionesAplicarPendientes(PDO $Pdo, string $Directorio): array {
    SgceMigracionesCrearTabla($Pdo);
    $Aplicadas = [];
    foreach (SgceMigracionesDisponibles($Directorio) as $M) {
        if (SgceMigracionAplicada($Pdo, (string)$M['Version'])) { continue; }
        $Sql = (string)file_get_contents($M['Ruta']);
        if (trim($Sql) === '') { continue; }
        try {
            // MySQL aplica commits implícitos en DDL; por eso las migraciones de esquema no se envuelven en transacción falsa.
            $Pdo->exec($Sql);
            SgceMigracionRegistrar($Pdo, (string)$M['Version'], (string)$M['Nombre'], (string)$M['Checksum']);
            $Aplicadas[] = $M;
        } catch (Throwable $E) {
            throw $E;
        }
    }
    return $Aplicadas;
}
