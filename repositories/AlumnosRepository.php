<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceRepoAlumnoFiltros(array $Entrada): array {
    return [
        'buscar' => trim((string)($Entrada['BuscarAlumnos'] ?? '')),
        'grupo_id' => max(0, (int)($Entrada['GrupoIdFiltro'] ?? 0)),
        'turno' => SgceNormalizarTurno($Entrada['TurnoFiltro'] ?? ''),
        'ciclo_id' => max(0, (int)($Entrada['CicloIdFiltro'] ?? 0)),
    ];
}

function SgceRepoAlumnoWhere(PDO $Pdo, array $Filtros, array &$Params): string {
    $CicloActivo = SgceCicloActivo($Pdo);
    $CicloId = !empty($Filtros['ciclo_id']) ? (int)$Filtros['ciclo_id'] : (int)($CicloActivo['Id'] ?? 0);
    $Where = ['A.Activo = 1', 'G.Activo = 1', "AI.Estado = 'INSCRITO'"];
    if ($CicloId > 0) { $Where[] = 'AI.CicloId = ?'; $Params[] = $CicloId; }
    if (!empty($Filtros['buscar'])) {
        $Where[] = 'A.NombreCompleto LIKE ?';
        $Params[] = '%' . $Filtros['buscar'] . '%';
    }
    if (!empty($Filtros['grupo_id'])) {
        $Where[] = 'AI.GrupoId = ?';
        $Params[] = (int)$Filtros['grupo_id'];
    }
    if (!empty($Filtros['turno'])) {
        $Where[] = 'G.Turno = ?';
        $Params[] = (string)$Filtros['turno'];
    }
    return implode(' AND ', $Where);
}

function SgceRepoAlumnoContar(PDO $Pdo, array $Filtros = []): int {
    $Params = [];
    $Where = SgceRepoAlumnoWhere($Pdo, $Filtros, $Params);
    $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM AlumnoInscripciones AI INNER JOIN Alumnos A ON A.Id = AI.AlumnoId INNER JOIN Grupos G ON G.Id = AI.GrupoId WHERE {$Where}");
    $Stmt->execute($Params);
    return (int)$Stmt->fetchColumn();
}

function SgceRepoAlumnoListar(PDO $Pdo, array $Filtros, int $Limit, int $Offset): array {
    $Params = [];
    $Where = SgceRepoAlumnoWhere($Pdo, $Filtros, $Params);
    $Stmt = $Pdo->prepare("
        SELECT A.Id, A.NombreCompleto, AI.GrupoId, G.Grado, G.Grupo, G.Turno, AI.CicloId, C.Nombre AS CicloNombre
        FROM AlumnoInscripciones AI
        INNER JOIN Alumnos A ON A.Id = AI.AlumnoId
        INNER JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId
        INNER JOIN CiclosEscolares C ON C.Id = AI.CicloId
        WHERE {$Where}
        ORDER BY G.Turno, CAST(G.Grado AS UNSIGNED), G.Grado, G.Grupo, A.NombreCompleto, A.Id
        LIMIT ? OFFSET ?
    ");
    foreach ($Params as $I => $Param) { $Stmt->bindValue($I + 1, $Param); }
    $Stmt->bindValue(count($Params) + 1, max(1, min(100, $Limit)), PDO::PARAM_INT);
    $Stmt->bindValue(count($Params) + 2, max(0, $Offset), PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}

function SgceRepoAlumnoPorGrupo(PDO $Pdo, int $GrupoId, int $Limit = 1000, int $Offset = 0): array {
    $Grupo = SgceGrupoObtenerPorId($Pdo, $GrupoId);
    $CicloId = (int)($Grupo['CicloId'] ?? 0);
    if ($GrupoId <= 0 || $CicloId <= 0) { return []; }
    $Stmt = $Pdo->prepare("
        SELECT A.Id, A.NombreCompleto, AI.GrupoId, G.Grado, G.Grupo, G.Turno, AI.CicloId, AI.Estado
        FROM AlumnoInscripciones AI
        INNER JOIN Alumnos A ON A.Id = AI.AlumnoId AND A.Activo = 1
        INNER JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId
        WHERE G.Activo = 1 AND AI.GrupoId = ? AND AI.CicloId = ? AND AI.Estado = 'INSCRITO'
        ORDER BY A.NombreCompleto, A.Id
        LIMIT ? OFFSET ?
    ");
    $Stmt->bindValue(1, $GrupoId, PDO::PARAM_INT);
    $Stmt->bindValue(2, $CicloId, PDO::PARAM_INT);
    $Stmt->bindValue(3, max(1, min(1000, $Limit)), PDO::PARAM_INT);
    $Stmt->bindValue(4, max(0, $Offset), PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}
