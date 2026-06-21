<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceRepoAsignacionFiltros(array $Entrada): array {
    return [
        'buscar' => trim((string)($Entrada['BuscarAsignaciones'] ?? '')),
        'maestro_id' => max(0, (int)($Entrada['MaestroIdFiltro'] ?? 0)),
        'grupo_id' => max(0, (int)($Entrada['GrupoIdFiltro'] ?? 0)),
        'etapa' => max(0, (int)($Entrada['EtapaFiltro'] ?? 0)),
        'grupo' => SgceNormalizarGrupo($Entrada['GrupoFiltro'] ?? ''),
        'turno' => SgceNormalizarTurno($Entrada['TurnoFiltro'] ?? ''),
        'materia' => trim((string)($Entrada['MateriaFiltro'] ?? '')),
        'ciclo_id' => max(0, (int)($Entrada['CicloIdFiltro'] ?? 0)),
    ];
}

function SgceRepoAsignacionWhere(PDO $Pdo, array $Filtros, array &$Params): string {
    $CicloActivo = SgceCicloActivo($Pdo);
    $CicloId = !empty($Filtros['ciclo_id']) ? (int)$Filtros['ciclo_id'] : (int)($CicloActivo['Id'] ?? 0);
    $Where = ['Asn.Activo = 1', 'U.Activo = 1', 'G.Activo = 1'];
    if ($CicloId > 0) { $Where[] = 'Asn.CicloId = ?'; $Params[] = $CicloId; }
    if (!empty($Filtros['buscar'])) {
        $FullText = SgceFullTextBusqueda($Filtros['buscar']);
        if ($FullText !== '') {
            $Where[] = '(MATCH(Asn.MateriaBusqueda, Asn.MateriaNombre) AGAINST (? IN BOOLEAN MODE) OR MATCH(U.NombreBusqueda, U.NombreCompleto) AGAINST (? IN BOOLEAN MODE) OR Asn.MateriaBusqueda LIKE ? OR U.NombreBusqueda LIKE ? OR G.Grado LIKE ? OR G.Grupo LIKE ? OR G.Turno LIKE ? OR EA.Nombre LIKE ?)';
            $Params[] = $FullText;
            $Params[] = $FullText;
            $LikeAsignacion = ('%' . SgceTextoBusquedaNormalizado($Filtros['buscar']) . '%');
            $Params[] = $LikeAsignacion;
            $Params[] = $LikeAsignacion;
            $Params[] = $LikeAsignacion;
            $Params[] = $LikeAsignacion;
            $Params[] = $LikeAsignacion;
            $Params[] = $LikeAsignacion;
        } else {
            $Where[] = '(Asn.MateriaBusqueda LIKE ? OR U.NombreBusqueda LIKE ? OR G.Grado LIKE ? OR G.Grupo LIKE ? OR G.Turno LIKE ? OR EA.Nombre LIKE ?)';
            $LikeAsignacion = ('%' . SgceTextoBusquedaNormalizado($Filtros['buscar']) . '%');
            $Params[] = $LikeAsignacion;
            $Params[] = $LikeAsignacion;
            $Params[] = $LikeAsignacion;
            $Params[] = $LikeAsignacion;
            $Params[] = $LikeAsignacion;
            $Params[] = $LikeAsignacion;
        }
    }
    if (!empty($Filtros['materia'])) { $Where[] = 'Asn.MateriaBusqueda LIKE ?'; $Params[] = SgceLikePrefijoBusqueda($Filtros['materia']); }
    if (!empty($Filtros['maestro_id'])) { $Where[] = 'Asn.MaestroId = ?'; $Params[] = (int)$Filtros['maestro_id']; }
    if (!empty($Filtros['grupo_id'])) { $Where[] = 'Asn.GrupoId = ?'; $Params[] = (int)$Filtros['grupo_id']; }
    if (!empty($Filtros['etapa'])) { $Where[] = 'EA.Orden = ?'; $Params[] = (int)$Filtros['etapa']; }
    if (!empty($Filtros['grupo'])) { $Where[] = 'G.Grupo = ?'; $Params[] = (string)$Filtros['grupo']; }
    if (!empty($Filtros['turno'])) { $Where[] = 'G.Turno = ?'; $Params[] = (string)$Filtros['turno']; }
    return implode(' AND ', $Where);
}

function SgceRepoAsignacionContar(PDO $Pdo, array $Filtros = []): int {
    $Params = [];
    $Where = SgceRepoAsignacionWhere($Pdo, $Filtros, $Params);
    $Stmt = $Pdo->prepare("\n        SELECT COUNT(*)\n        FROM Asignaciones Asn\n        INNER JOIN Usuarios U ON U.Id = Asn.MaestroId\n        INNER JOIN Grupos G ON G.Id = Asn.GrupoId AND G.CicloId = Asn.CicloId\n        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId\n        WHERE {$Where}\n    ");
    $Stmt->execute($Params);
    return (int)$Stmt->fetchColumn();
}

function SgceRepoAsignacionListar(PDO $Pdo, array $Filtros, int $Limit, int $Offset): array {
    $Params = [];
    $Where = SgceRepoAsignacionWhere($Pdo, $Filtros, $Params);
    $Stmt = $Pdo->prepare("\n        SELECT Asn.Id, Asn.CicloId, Asn.MateriaGrupoId, Asn.MateriaId, Asn.MateriaNombre, Asn.HorasSemana, U.NombreCompleto AS Maestro, U.Id AS MaestroId, G.Id AS GrupoId, G.Grado, G.Grupo, G.Turno, PE.Nombre AS ProgramaNombre, C.Nombre AS CicloNombre, OE.TipoPeriodizacion, EA.Nombre AS EtapaNombre, EA.Orden AS EtapaOrden\n        FROM Asignaciones Asn\n        INNER JOIN Usuarios U ON U.Id = Asn.MaestroId\n        INNER JOIN Grupos G ON G.Id = Asn.GrupoId AND G.CicloId = Asn.CicloId\n        INNER JOIN ProgramasEducativos PE ON PE.Id = G.ProgramaId\n        INNER JOIN CiclosEscolares C ON C.Id = Asn.CicloId\n        LEFT JOIN OfertasEducativas OE ON OE.Id = G.OfertaId\n        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId\n        WHERE {$Where}\n        ORDER BY U.NombreCompleto, G.Turno, COALESCE(EA.Orden, CAST(G.Grado AS UNSIGNED)), G.Grado, G.Grupo, Asn.MateriaNombre, Asn.Id\n        LIMIT ? OFFSET ?\n    ");
    foreach ($Params as $I => $Param) { $Stmt->bindValue($I + 1, $Param); }
    SgceBindLimitOffset($Stmt, count($Params) + 1, $Limit, $Offset);
    $Stmt->execute();
    return $Stmt->fetchAll();
}

