<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceRepoMateriaFiltros(array $Entrada): array {
    return [
        'buscar' => trim((string)($Entrada['BuscarMaterias'] ?? '')),
        'materia' => trim((string)($Entrada['MateriaFiltro'] ?? '')),
        'etapa' => max(0, (int)($Entrada['EtapaFiltro'] ?? 0)),
        'grupo' => SgceNormalizarGrupo($Entrada['GrupoFiltro'] ?? ''),
        'turno' => SgceNormalizarTurno($Entrada['TurnoFiltro'] ?? ''),
        'estado' => in_array(SgceNormalizarMayusculas((string)($Entrada['EstadoFiltro'] ?? '')), ['DISPONIBLE','ASIGNADA'], true) ? SgceNormalizarMayusculas((string)$Entrada['EstadoFiltro']) : '',
        'ciclo_id' => max(0, (int)($Entrada['CicloIdFiltro'] ?? 0)),
    ];
}

function SgceRepoMateriaWhere(PDO $Pdo, array $Filtros, array &$Params): string {
    $CicloActivo = SgceCicloActivo($Pdo);
    $CicloId = !empty($Filtros['ciclo_id']) ? (int)$Filtros['ciclo_id'] : (int)($CicloActivo['Id'] ?? 0);
    $Where = ['MG.Activo = 1', 'G.Activo = 1'];
    if ($CicloId > 0) { $Where[] = 'MG.CicloId = ?'; $Params[] = $CicloId; }
    if (!empty($Filtros['buscar'])) {
        $FullText = SgceFullTextBusqueda($Filtros['buscar']);
        if ($FullText !== '') {
            $Where[] = '(MATCH(MG.MateriaBusqueda, MG.MateriaNombre) AGAINST (? IN BOOLEAN MODE) OR MG.MateriaBusqueda LIKE ?)';
            $Params[] = $FullText;
            $Params[] = SgceLikePrefijoBusqueda($Filtros['buscar']);
        } else {
            $Where[] = 'MG.MateriaBusqueda LIKE ?';
            $Params[] = SgceLikePrefijoBusqueda($Filtros['buscar']);
        }
    }
    if (!empty($Filtros['materia'])) { $Where[] = 'MG.MateriaBusqueda LIKE ?'; $Params[] = SgceLikePrefijoBusqueda($Filtros['materia']); }
    if (!empty($Filtros['etapa'])) { $Where[] = 'EA.Orden = ?'; $Params[] = (int)$Filtros['etapa']; }
    if (!empty($Filtros['grupo'])) { $Where[] = 'G.Grupo = ?'; $Params[] = (string)$Filtros['grupo']; }
    if (!empty($Filtros['turno'])) { $Where[] = 'G.Turno = ?'; $Params[] = (string)$Filtros['turno']; }
    if (!empty($Filtros['estado'])) {
        $Where[] = $Filtros['estado'] === 'ASIGNADA'
            ? 'EXISTS (SELECT 1 FROM Asignaciones AX WHERE AX.MateriaGrupoId = MG.Id AND AX.Activo = 1)'
            : 'NOT EXISTS (SELECT 1 FROM Asignaciones AX WHERE AX.MateriaGrupoId = MG.Id AND AX.Activo = 1)';
    }
    return implode(' AND ', $Where);
}

function SgceRepoMateriaContar(PDO $Pdo, array $Filtros = []): int {
    $Params = [];
    $Where = SgceRepoMateriaWhere($Pdo, $Filtros, $Params);
    $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM MateriasGrupo MG INNER JOIN Grupos G ON G.Id = MG.GrupoId AND G.CicloId = MG.CicloId LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId WHERE {$Where}");
    $Stmt->execute($Params);
    return (int)$Stmt->fetchColumn();
}

function SgceRepoMateriaListar(PDO $Pdo, array $Filtros, int $Limit, int $Offset): array {
    $Params = [];
    $Where = SgceRepoMateriaWhere($Pdo, $Filtros, $Params);
    $Stmt = $Pdo->prepare("\n        SELECT MG.Id, MG.CicloId, MG.OfertaId, MG.ProgramaId, MG.EtapaId, MG.GrupoId, MG.MateriaId, MG.MateriaNombre, MG.HorasSemana, MG.Activo,\n               G.Grado, G.Grupo, G.Turno, PE.Nombre AS ProgramaNombre, OE.TipoPeriodizacion, EA.Nombre AS EtapaNombre, EA.Orden AS EtapaOrden, EA.EsTerminal,\n               (SELECT COUNT(*) FROM Asignaciones A WHERE A.MateriaGrupoId = MG.Id AND A.Activo = 1) AS TieneAsignacion\n        FROM MateriasGrupo MG\n        INNER JOIN Grupos G ON G.Id = MG.GrupoId AND G.CicloId = MG.CicloId\n        INNER JOIN ProgramasEducativos PE ON PE.Id = MG.ProgramaId\n        LEFT JOIN OfertasEducativas OE ON OE.Id = G.OfertaId\n        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId\n        WHERE {$Where}\n        ORDER BY PE.Nombre, G.Turno, COALESCE(EA.Orden, CAST(G.Grado AS UNSIGNED)), G.Grado, G.Grupo, MG.MateriaNombre\n        LIMIT ? OFFSET ?\n    ");
    foreach ($Params as $I => $Param) { $Stmt->bindValue($I + 1, $Param); }
    SgceBindLimitOffset($Stmt, count($Params) + 1, $Limit, $Offset);
    $Stmt->execute();
    return $Stmt->fetchAll();
}
