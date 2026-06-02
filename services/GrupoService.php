<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceGrupoContarActivos(PDO $Pdo): int {
    return (int)$Pdo->query("SELECT COUNT(*) FROM Grupos WHERE Activo = 1")->fetchColumn();
}

function SgceGrupoListarActivos(PDO $Pdo): array {
    return $Pdo->query("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Activo = 1 ORDER BY Turno, Grado, Grupo ASC")->fetchAll();
}

function SgceGrupoListarPaginado(PDO $Pdo, int $Limit, int $Offset): array {
    $Stmt = $Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Activo = 1 ORDER BY Turno, Grado, Grupo ASC LIMIT ? OFFSET ?");
    $Stmt->bindValue(1, $Limit, PDO::PARAM_INT);
    $Stmt->bindValue(2, $Offset, PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}

function SgceGrupoObtenerActivoPorId(PDO $Pdo, int $GrupoId) {
    $Stmt = $Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Id = ? AND Activo = 1 LIMIT 1");
    $Stmt->execute([$GrupoId]);
    return $Stmt->fetch();
}

function SgceGrupoExisteActivo(PDO $Pdo, int $GrupoId): bool {
    $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM Grupos WHERE Id = ? AND Activo = 1");
    $Stmt->execute([$GrupoId]);
    return (int)$Stmt->fetchColumn() > 0;
}
