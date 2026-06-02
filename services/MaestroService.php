<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceMaestroContarActivos(PDO $Pdo): int {
    return (int)$Pdo->query("SELECT COUNT(*) FROM Usuarios WHERE Rol='maestro' AND Activo = 1")->fetchColumn();
}

function SgceMaestroListarActivos(PDO $Pdo): array {
    return $Pdo->query("SELECT Id, NombreCompleto, Username FROM Usuarios WHERE Rol='maestro' AND Activo = 1 ORDER BY NombreCompleto ASC")->fetchAll();
}

function SgceMaestroListarPaginado(PDO $Pdo, int $Limit, int $Offset): array {
    $Stmt = $Pdo->prepare("SELECT Id, NombreCompleto, Username FROM Usuarios WHERE Rol='maestro' AND Activo = 1 ORDER BY NombreCompleto ASC LIMIT ? OFFSET ?");
    $Stmt->bindValue(1, $Limit, PDO::PARAM_INT);
    $Stmt->bindValue(2, $Offset, PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}

function SgceMaestroExisteActivo(PDO $Pdo, int $MaestroId): bool {
    $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM Usuarios WHERE Id = ? AND Rol = 'maestro' AND Activo = 1");
    $Stmt->execute([$MaestroId]);
    return (int)$Stmt->fetchColumn() > 0;
}
