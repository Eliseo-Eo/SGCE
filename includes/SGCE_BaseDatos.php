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
}

function SgceDbTablaExiste(PDO $Pdo, string $Tabla): bool {
    try {
        $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $Stmt->execute([$Tabla]);
        return (int)$Stmt->fetchColumn() > 0;
    } catch (Exception $E) {
        return false;
    }
}

function SgceDbColumnaExiste(PDO $Pdo, string $Tabla, string $Columna): bool {
    try {
        $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $Stmt->execute([$Tabla, $Columna]);
        return (int)$Stmt->fetchColumn() > 0;
    } catch (Exception $E) {
        return false;
    }
}

