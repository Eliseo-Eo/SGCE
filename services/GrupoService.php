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
    $Stmt = $Pdo->prepare("SELECT G.Id, G.CicloId, G.OfertaId, G.ProgramaId, G.EtapaId, G.Grado, G.Grupo, G.Turno,
        CA.Nombre AS ProgramaNombre, OE.TipoPeriodizacion, EA.Nombre AS EtapaNombre, EA.Orden AS EtapaOrden
        FROM Grupos G
        LEFT JOIN ProgramasEducativos CA ON CA.Id = G.ProgramaId
        LEFT JOIN OfertasEducativas OE ON OE.Id = G.OfertaId
        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId
        WHERE G.CicloId = ? AND G.Activo = 1
        ORDER BY G.Turno, COALESCE(EA.Orden, CAST(G.Grado AS UNSIGNED)), CA.Nombre, G.Grupo ASC");
    $Stmt->execute([$CicloId]);
    return $Stmt->fetchAll();
}

function SgceGrupoObtenerActivoPorId(PDO $Pdo, int $GrupoId) {
    $Ciclo = SgceCicloActivo($Pdo);
    $CicloId = (int)($Ciclo['Id'] ?? 0);
    $Stmt = $Pdo->prepare("SELECT G.Id, G.CicloId, G.OfertaId, G.ProgramaId, G.EtapaId, G.Grado, G.Grupo, G.Turno,
        CA.Nombre AS ProgramaNombre, OE.TipoPeriodizacion, EA.Nombre AS EtapaNombre, EA.Orden AS EtapaOrden
        FROM Grupos G
        LEFT JOIN ProgramasEducativos CA ON CA.Id = G.ProgramaId
        LEFT JOIN OfertasEducativas OE ON OE.Id = G.OfertaId
        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId
        WHERE G.Id = ? AND G.CicloId = ? AND G.Activo = 1 LIMIT 1");
    $Stmt->execute([$GrupoId, $CicloId]);
    return $Stmt->fetch();
}

