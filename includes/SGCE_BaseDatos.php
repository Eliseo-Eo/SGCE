<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceNormalizarCicloActivoUnico(PDO $Pdo): void {
    static $Normalizado = false;
    if ($Normalizado || !SgceDbTablaExiste($Pdo, 'CiclosEscolares')) { return; }
    $Normalizado = true;

    $Ids = $Pdo->query('SELECT Id FROM CiclosEscolares WHERE Activo = 1 ORDER BY FechaInicio DESC, Id DESC')->fetchAll(PDO::FETCH_COLUMN);
    if (count($Ids) <= 1) { return; }

    $IdActivo = (int)$Ids[0];
    $Pdo->prepare('UPDATE CiclosEscolares SET Activo = 0 WHERE Activo = 1 AND Id <> ?')->execute([$IdActivo]);
    $GLOBALS['SGCE_CICLO_ACTIVO_CACHE_RESET'] = ($GLOBALS['SGCE_CICLO_ACTIVO_CACHE_RESET'] ?? 0) + 1;
}

function SgceDbTablaExiste(PDO $Pdo, string $Tabla): bool {
    static $Cache = [];
    $Tabla = preg_replace('/[^A-Za-z0-9_]/', '', (string)$Tabla);
    if ($Tabla === '') { return false; }
    if (array_key_exists($Tabla, $Cache)) { return $Cache[$Tabla]; }
    try {
        $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $Stmt->execute([$Tabla]);
        $Cache[$Tabla] = (int)$Stmt->fetchColumn() > 0;
    } catch (Exception $E) {
        $Cache[$Tabla] = false;
    }
    return $Cache[$Tabla];
}

function SgceDbColumnaExiste(PDO $Pdo, string $Tabla, string $Columna): bool {
    static $Cache = [];
    $Tabla = preg_replace('/[^A-Za-z0-9_]/', '', (string)$Tabla);
    $Columna = preg_replace('/[^A-Za-z0-9_]/', '', (string)$Columna);
    if ($Tabla === '' || $Columna === '') { return false; }
    $Key = $Tabla . '.' . $Columna;
    if (array_key_exists($Key, $Cache)) { return $Cache[$Key]; }
    try {
        $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $Stmt->execute([$Tabla, $Columna]);
        $Cache[$Key] = (int)$Stmt->fetchColumn() > 0;
    } catch (Exception $E) {
        $Cache[$Key] = false;
    }
    return $Cache[$Key];
}

