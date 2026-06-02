<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceGrupoContarActivos(PDO $Pdo): int {
    $Ciclo = SgceCicloActivo($Pdo);
    $CicloId = (int)($Ciclo['Id'] ?? 0);
    if ($CicloId <= 0) { return 0; }
    $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM Grupos WHERE CicloId = ? AND Activo = 1");
    $Stmt->execute([$CicloId]);
    return (int)$Stmt->fetchColumn();
}

function SgceGrupoListarActivos(PDO $Pdo): array {
    $Ciclo = SgceCicloActivo($Pdo);
    $CicloId = (int)($Ciclo['Id'] ?? 0);
    if ($CicloId <= 0) { return []; }
    SgceAsegurarTablasMultiescolar($Pdo);
    $Stmt = $Pdo->prepare("SELECT G.Id, G.CicloId, G.OfertaId, G.CarreraId, G.EtapaId, G.Grado, G.Grupo, G.Turno,
        CA.Nombre AS CarreraNombre, EA.Nombre AS EtapaNombre, EA.Orden AS EtapaOrden
        FROM Grupos G
        LEFT JOIN Carreras CA ON CA.Id = G.CarreraId
        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId
        WHERE G.CicloId = ? AND G.Activo = 1
        ORDER BY G.Turno, COALESCE(EA.Orden, CAST(G.Grado AS UNSIGNED)), CA.Nombre, G.Grupo ASC");
    $Stmt->execute([$CicloId]);
    return $Stmt->fetchAll();
}

function SgceGrupoListarPaginado(PDO $Pdo, int $Limit, int $Offset): array {
    $Ciclo = SgceCicloActivo($Pdo);
    $CicloId = (int)($Ciclo['Id'] ?? 0);
    if ($CicloId <= 0) { return []; }
    SgceAsegurarTablasMultiescolar($Pdo);
    $Stmt = $Pdo->prepare("SELECT G.Id, G.CicloId, G.OfertaId, G.CarreraId, G.EtapaId, G.Grado, G.Grupo, G.Turno,
        CA.Nombre AS CarreraNombre, EA.Nombre AS EtapaNombre, EA.Orden AS EtapaOrden
        FROM Grupos G
        LEFT JOIN Carreras CA ON CA.Id = G.CarreraId
        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId
        WHERE G.CicloId = ? AND G.Activo = 1
        ORDER BY G.Turno, COALESCE(EA.Orden, CAST(G.Grado AS UNSIGNED)), CA.Nombre, G.Grupo ASC LIMIT ? OFFSET ?");
    $Stmt->bindValue(1, $CicloId, PDO::PARAM_INT);
    $Stmt->bindValue(2, $Limit, PDO::PARAM_INT);
    $Stmt->bindValue(3, $Offset, PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}

function SgceGrupoObtenerActivoPorId(PDO $Pdo, int $GrupoId) {
    $Ciclo = SgceCicloActivo($Pdo);
    $CicloId = (int)($Ciclo['Id'] ?? 0);
    SgceAsegurarTablasMultiescolar($Pdo);
    $Stmt = $Pdo->prepare("SELECT G.Id, G.CicloId, G.OfertaId, G.CarreraId, G.EtapaId, G.Grado, G.Grupo, G.Turno,
        CA.Nombre AS CarreraNombre, EA.Nombre AS EtapaNombre, EA.Orden AS EtapaOrden
        FROM Grupos G
        LEFT JOIN Carreras CA ON CA.Id = G.CarreraId
        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId
        WHERE G.Id = ? AND G.CicloId = ? AND G.Activo = 1 LIMIT 1");
    $Stmt->execute([$GrupoId, $CicloId]);
    return $Stmt->fetch();
}

function SgceGrupoExisteActivo(PDO $Pdo, int $GrupoId): bool {
    $Ciclo = SgceCicloActivo($Pdo);
    $CicloId = (int)($Ciclo['Id'] ?? 0);
    $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM Grupos WHERE Id = ? AND CicloId = ? AND Activo = 1");
    $Stmt->execute([$GrupoId, $CicloId]);
    return (int)$Stmt->fetchColumn() > 0;
}
