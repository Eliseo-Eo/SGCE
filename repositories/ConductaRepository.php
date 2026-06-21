<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }


function SgceConductaRepoFechaSegura($Valor, string $Default): string {
    $Valor = trim((string)$Valor);
    return SgceFechaYmdValida($Valor) ? $Valor : $Default;
}

function SgceConductaRepoAlumnosActivos(PDO $Pdo, int $CicloId = 0): array {
    if ($CicloId <= 0) { $Ciclo = SgceCicloActivo($Pdo); $CicloId = (int)($Ciclo['Id'] ?? 0); }
    if ($CicloId <= 0) { return []; }
    $Stmt = $Pdo->prepare("\n        SELECT Al.Id, Al.NombreCompleto, AI.GrupoId, G.Grado, G.Grupo, G.Turno\n        FROM AlumnoInscripciones AI\n        INNER JOIN Alumnos Al ON Al.Id = AI.AlumnoId AND Al.Activo = 1\n        INNER JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId AND G.Activo = 1\n        WHERE AI.CicloId = ? AND AI.Estado = 'INSCRITO'\n        ORDER BY G.Turno ASC, CAST(G.Grado AS UNSIGNED) ASC, G.Grado ASC, G.Grupo ASC, Al.NombreCompleto ASC\n    ");
    $Stmt->execute([$CicloId]);
    return $Stmt->fetchAll(PDO::FETCH_ASSOC);
}

function SgceConductaRepoPaseListaPorFecha(PDO $Pdo, int $CicloId, int $AsignacionId, string $Fecha): array {
    $Registros = [];
    if ($CicloId <= 0 || $AsignacionId <= 0 || !SgceFechaYmdValida($Fecha) || !SgceDbTablaExiste($Pdo, 'ConductaRegistros')) { return $Registros; }
    $Stmt = $Pdo->prepare("\n        SELECT *\n        FROM ConductaRegistros\n        WHERE CicloId = ? AND AsignacionId = ? AND FechaDia = ? AND Origen = 'PASE_LISTA' AND Estado <> 'CANCELADO'\n        ORDER BY Id ASC\n    ");
    $Stmt->execute([$CicloId, $AsignacionId, $Fecha]);
    foreach ($Stmt->fetchAll(PDO::FETCH_ASSOC) as $Fila) { $Registros[(int)$Fila['AlumnoId']] = $Fila; }
    return $Registros;
}

function SgceConductaRepoResumenHoy(PDO $Pdo, int $CicloId = 0): array {
    if ($CicloId <= 0) { $Ciclo = SgceCicloActivo($Pdo); $CicloId = (int)($Ciclo['Id'] ?? 0); }
    if ($CicloId <= 0 || !SgceDbTablaExiste($Pdo, 'ConductaRegistros')) { return ['Total' => 0, 'Pendientes' => 0, 'Graves' => 0]; }
    $Stmt = $Pdo->prepare("\n        SELECT COUNT(*) AS Total,\n               SUM(CASE WHEN Estado = 'PENDIENTE' THEN 1 ELSE 0 END) AS Pendientes,\n               SUM(CASE WHEN Severidad = 'GRAVE' AND Estado <> 'CANCELADO' THEN 1 ELSE 0 END) AS Graves\n        FROM ConductaRegistros\n        WHERE CicloId = ? AND FechaDia = CURDATE() AND Estado <> 'CANCELADO'\n    ");
    $Stmt->execute([$CicloId]);
    $Row = $Stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    return ['Total' => (int)($Row['Total'] ?? 0), 'Pendientes' => (int)($Row['Pendientes'] ?? 0), 'Graves' => (int)($Row['Graves'] ?? 0)];
}

function SgceConductaRepoResumenAlumno(PDO $Pdo, int $AlumnoId, int $CicloId): array {
    $Base = ['Total' => 0, 'Leves' => 0, 'Medias' => 0, 'Graves' => 0, 'Pendientes' => 0, 'VisiblesPadre' => 0];
    if ($AlumnoId <= 0 || $CicloId <= 0 || !SgceDbTablaExiste($Pdo, 'ConductaRegistros')) { return $Base; }
    $Stmt = $Pdo->prepare("\n        SELECT COUNT(*) AS Total,\n               SUM(CASE WHEN Severidad = 'LEVE' THEN 1 ELSE 0 END) AS Leves,\n               SUM(CASE WHEN Severidad = 'MEDIA' THEN 1 ELSE 0 END) AS Medias,\n               SUM(CASE WHEN Severidad = 'GRAVE' THEN 1 ELSE 0 END) AS Graves,\n               SUM(CASE WHEN Estado = 'PENDIENTE' THEN 1 ELSE 0 END) AS Pendientes,\n               SUM(CASE WHEN VisiblePadre = 1 AND Estado IN ('VALIDADO','EN_SEGUIMIENTO','CERRADO') THEN 1 ELSE 0 END) AS VisiblesPadre\n        FROM ConductaRegistros\n        WHERE AlumnoId = ? AND CicloId = ? AND Estado <> 'CANCELADO'\n    ");
    $Stmt->execute([$AlumnoId, $CicloId]);
    $Row = $Stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    foreach ($Base as $K => $_) { $Base[$K] = (int)($Row[$K] ?? 0); }
    return $Base;
}

function SgceConductaRepoHistorialAlumno(PDO $Pdo, int $AlumnoId, int $CicloId, int $Limite = 80, bool $SoloPadre = false): array {
    if ($AlumnoId <= 0 || $CicloId <= 0 || !SgceDbTablaExiste($Pdo, 'ConductaRegistros')) { return []; }
    $Limite = max(1, min(300, $Limite));
    $WherePadre = $SoloPadre ? " AND CR.VisiblePadre = 1 AND CR.Estado IN ('VALIDADO','EN_SEGUIMIENTO','CERRADO')" : '';
    $Stmt = $Pdo->prepare("\n        SELECT CR.*, DATE_FORMAT(CR.FechaDia, '%d/%m/%Y') AS FechaTexto,\n               Al.NombreCompleto AS AlumnoNombre,\n               G.Grado, G.Grupo, G.Turno,\n               Asg.MateriaNombre,\n               Reporta.NombreCompleto AS ReportaNombre,\n               Revisa.NombreCompleto AS RevisaNombre\n        FROM ConductaRegistros CR\n        INNER JOIN Alumnos Al ON Al.Id = CR.AlumnoId\n        INNER JOIN Grupos G ON G.Id = CR.GrupoId\n        LEFT JOIN Asignaciones Asg ON Asg.Id = CR.AsignacionId\n        LEFT JOIN Usuarios Reporta ON Reporta.Id = CR.ReportaUsuarioId\n        LEFT JOIN Usuarios Revisa ON Revisa.Id = CR.RevisaUsuarioId\n        WHERE CR.AlumnoId = ? AND CR.CicloId = ? AND CR.Estado <> 'CANCELADO' {$WherePadre}\n        ORDER BY CR.FechaIncidente DESC, CR.Id DESC\n        LIMIT {$Limite}\n    ");
    $Stmt->execute([$AlumnoId, $CicloId]);
    return $Stmt->fetchAll(PDO::FETCH_ASSOC);
}

function SgceConductaRepoFiltros(array $Origen): array {
    return [
        'FechaInicio' => SgceConductaRepoFechaSegura($Origen['FechaInicio'] ?? SgceFechaYmdSumarDias(date('Y-m-d'), -30), SgceFechaYmdSumarDias(date('Y-m-d'), -30)),
        'FechaFin' => SgceConductaRepoFechaSegura($Origen['FechaFin'] ?? date('Y-m-d'), date('Y-m-d')),
        'GrupoId' => max(0, (int)($Origen['GrupoId'] ?? 0)),
        'AlumnoId' => max(0, (int)($Origen['AlumnoId'] ?? 0)),
        'DocenteId' => max(0, (int)($Origen['DocenteId'] ?? 0)),
        'Tipo' => SgceConductaTipoSeguro($Origen['Tipo'] ?? ''),
        'Severidad' => SgceConductaSeveridadSeguro($Origen['Severidad'] ?? ''),
        'Estado' => SgceConductaEstadoSeguro($Origen['Estado'] ?? ''),
        'VisiblePadre' => in_array((string)($Origen['VisiblePadre'] ?? ''), ['0','1'], true) ? (int)$Origen['VisiblePadre'] : -1,
    ];
}

function SgceConductaRepoSqlFiltro(array $Filtro, int $CicloId, array &$Params): string {
    $Where = ['CR.CicloId = ?', 'CR.Estado <> \'CANCELADO\''];
    $Params = [$CicloId];
    if (!empty($Filtro['FechaInicio'])) { $Where[] = 'CR.FechaDia >= ?'; $Params[] = $Filtro['FechaInicio']; }
    if (!empty($Filtro['FechaFin'])) { $Where[] = 'CR.FechaDia <= ?'; $Params[] = $Filtro['FechaFin']; }
    if ((int)($Filtro['GrupoId'] ?? 0) > 0) { $Where[] = 'CR.GrupoId = ?'; $Params[] = (int)$Filtro['GrupoId']; }
    if ((int)($Filtro['AlumnoId'] ?? 0) > 0) { $Where[] = 'CR.AlumnoId = ?'; $Params[] = (int)$Filtro['AlumnoId']; }
    if ((int)($Filtro['DocenteId'] ?? 0) > 0) { $Where[] = 'CR.ReportaUsuarioId = ?'; $Params[] = (int)$Filtro['DocenteId']; }
    if (($Filtro['Tipo'] ?? '') !== '') { $Where[] = 'CR.Tipo = ?'; $Params[] = $Filtro['Tipo']; }
    if (($Filtro['Severidad'] ?? '') !== '') { $Where[] = 'CR.Severidad = ?'; $Params[] = $Filtro['Severidad']; }
    if (($Filtro['Estado'] ?? '') !== '') { $Where[] = 'CR.Estado = ?'; $Params[] = $Filtro['Estado']; }
    if ((int)($Filtro['VisiblePadre'] ?? -1) >= 0) { $Where[] = 'CR.VisiblePadre = ?'; $Params[] = (int)$Filtro['VisiblePadre']; }
    return implode(' AND ', $Where);
}

function SgceConductaRepoListar(PDO $Pdo, int $CicloId, array $Filtro, int $Limite, int $Offset): array {
    if ($CicloId <= 0 || !SgceDbTablaExiste($Pdo, 'ConductaRegistros')) { return []; }
    $Limite = max(1, min(100, $Limite));
    $Offset = max(0, $Offset);
    $Params = [];
    $Where = SgceConductaRepoSqlFiltro($Filtro, $CicloId, $Params);
    $Sql = "\n        SELECT CR.*, DATE_FORMAT(CR.FechaDia, '%d/%m/%Y') AS FechaTexto,\n               Al.NombreCompleto AS AlumnoNombre,\n               G.Grado, G.Grupo, G.Turno,\n               Asg.MateriaNombre,\n               Reporta.NombreCompleto AS ReportaNombre,\n               Revisa.NombreCompleto AS RevisaNombre\n        FROM ConductaRegistros CR\n        INNER JOIN Alumnos Al ON Al.Id = CR.AlumnoId\n        INNER JOIN Grupos G ON G.Id = CR.GrupoId\n        LEFT JOIN Asignaciones Asg ON Asg.Id = CR.AsignacionId\n        LEFT JOIN Usuarios Reporta ON Reporta.Id = CR.ReportaUsuarioId\n        LEFT JOIN Usuarios Revisa ON Revisa.Id = CR.RevisaUsuarioId\n        WHERE {$Where}\n        ORDER BY CR.FechaIncidente DESC, CR.Id DESC\n        LIMIT ? OFFSET ?\n    ";
    $Stmt = $Pdo->prepare($Sql);
    $I = 1;
    foreach ($Params as $Valor) { $Stmt->bindValue($I++, $Valor); }
    $Stmt->bindValue($I++, $Limite, PDO::PARAM_INT);
    $Stmt->bindValue($I, $Offset, PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll(PDO::FETCH_ASSOC);
}

function SgceConductaRepoContar(PDO $Pdo, int $CicloId, array $Filtro): int {
    if ($CicloId <= 0 || !SgceDbTablaExiste($Pdo, 'ConductaRegistros')) { return 0; }
    $Params = [];
    $Where = SgceConductaRepoSqlFiltro($Filtro, $CicloId, $Params);
    $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM ConductaRegistros CR WHERE {$Where}");
    $Stmt->execute($Params);
    return (int)$Stmt->fetchColumn();
}

function SgceConductaRepoObtener(PDO $Pdo, int $Id): ?array {
    if ($Id <= 0 || !SgceDbTablaExiste($Pdo, 'ConductaRegistros')) { return null; }
    $Stmt = $Pdo->prepare('SELECT * FROM ConductaRegistros WHERE Id = ? LIMIT 1');
    $Stmt->execute([$Id]);
    $Row = $Stmt->fetch(PDO::FETCH_ASSOC);
    return $Row ?: null;
}
