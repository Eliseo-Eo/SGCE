<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceAlumnoContarActivos(PDO $Pdo): int {
    return (int)$Pdo->query("SELECT COUNT(*) FROM Alumnos A JOIN Grupos G ON A.GrupoId = G.Id WHERE A.Activo = 1 AND G.Activo = 1")->fetchColumn();
}

function SgceAlumnoListarPaginado(PDO $Pdo, int $Limit, int $Offset): array {
    $Stmt = $Pdo->prepare("SELECT A.Id, A.NombreCompleto, A.GrupoId, G.Grado, G.Grupo, G.Turno FROM Alumnos A JOIN Grupos G ON A.GrupoId = G.Id WHERE A.Activo = 1 AND G.Activo = 1 ORDER BY G.Turno, G.Grado, G.Grupo, A.NombreCompleto ASC LIMIT ? OFFSET ?");
    $Stmt->bindValue(1, $Limit, PDO::PARAM_INT);
    $Stmt->bindValue(2, $Offset, PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}

function SgceAlumnoListarActivosPorGrupo(PDO $Pdo, int $GrupoId): array {
    $Stmt = $Pdo->prepare("\n        SELECT A.Id, A.NombreCompleto, A.GrupoId, G.Grado, G.Grupo, G.Turno\n        FROM Alumnos A\n        JOIN Grupos G ON A.GrupoId = G.Id\n        WHERE A.Activo = 1\n          AND G.Activo = 1\n          AND A.GrupoId = ?\n        ORDER BY A.NombreCompleto ASC\n    ");
    $Stmt->execute([$GrupoId]);
    return $Stmt->fetchAll();
}
