<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceRepoAsignacionFiltros(array $Entrada): array {
    return [
        'buscar' => trim((string)($Entrada['BuscarAsignaciones'] ?? '')),
        'maestro_id' => max(0, (int)($Entrada['MaestroIdFiltro'] ?? 0)),
        'grupo_id' => max(0, (int)($Entrada['GrupoIdFiltro'] ?? 0)),
    ];
}

function SgceRepoAsignacionWhere(array $Filtros, array &$Params): string {
    $Where = ['Asn.Activo = 1', 'U.Activo = 1', 'G.Activo = 1'];
    if (!empty($Filtros['buscar'])) {
        $Where[] = '(Asn.MateriaNombre LIKE ? OR U.NombreCompleto LIKE ?)';
        $Params[] = '%' . $Filtros['buscar'] . '%';
        $Params[] = '%' . $Filtros['buscar'] . '%';
    }
    if (!empty($Filtros['maestro_id'])) {
        $Where[] = 'Asn.MaestroId = ?';
        $Params[] = (int)$Filtros['maestro_id'];
    }
    if (!empty($Filtros['grupo_id'])) {
        $Where[] = 'Asn.GrupoId = ?';
        $Params[] = (int)$Filtros['grupo_id'];
    }
    return implode(' AND ', $Where);
}

function SgceRepoAsignacionContar(PDO $Pdo, array $Filtros = []): int {
    $Params = [];
    $Where = SgceRepoAsignacionWhere($Filtros, $Params);
    $Stmt = $Pdo->prepare("
        SELECT COUNT(*)
        FROM Asignaciones Asn
        INNER JOIN Usuarios U ON U.Id = Asn.MaestroId
        INNER JOIN Grupos G ON G.Id = Asn.GrupoId
        WHERE {$Where}
    ");
    $Stmt->execute($Params);
    return (int)$Stmt->fetchColumn();
}

function SgceRepoAsignacionListar(PDO $Pdo, array $Filtros, int $Limit, int $Offset): array {
    $Params = [];
    $Where = SgceRepoAsignacionWhere($Filtros, $Params);
    $Stmt = $Pdo->prepare("
        SELECT Asn.Id, Asn.MateriaNombre, U.NombreCompleto AS Maestro, U.Id AS MaestroId, G.Id AS GrupoId, G.Grado, G.Grupo, G.Turno
        FROM Asignaciones Asn
        INNER JOIN Usuarios U ON U.Id = Asn.MaestroId
        INNER JOIN Grupos G ON G.Id = Asn.GrupoId
        WHERE {$Where}
        ORDER BY U.NombreCompleto, G.Turno, G.Grado, G.Grupo, Asn.MateriaNombre, Asn.Id
        LIMIT ? OFFSET ?
    ");
    foreach ($Params as $I => $Param) { $Stmt->bindValue($I + 1, $Param); }
    $Stmt->bindValue(count($Params) + 1, max(1, min(100, $Limit)), PDO::PARAM_INT);
    $Stmt->bindValue(count($Params) + 2, max(0, $Offset), PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}
