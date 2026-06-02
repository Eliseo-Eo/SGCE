<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceRepoAlumnoFiltros(array $Entrada): array {
    return [
        'buscar' => trim((string)($Entrada['BuscarAlumnos'] ?? '')),
        'grupo_id' => max(0, (int)($Entrada['GrupoIdFiltro'] ?? 0)),
        'turno' => SgceNormalizarTurno($Entrada['TurnoFiltro'] ?? ''),
    ];
}

function SgceRepoAlumnoWhere(array $Filtros, array &$Params): string {
    $Where = ['A.Activo = 1', 'G.Activo = 1'];
    if (!empty($Filtros['buscar'])) {
        $Where[] = 'A.NombreCompleto LIKE ?';
        $Params[] = '%' . $Filtros['buscar'] . '%';
    }
    if (!empty($Filtros['grupo_id'])) {
        $Where[] = 'A.GrupoId = ?';
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
    $Where = SgceRepoAlumnoWhere($Filtros, $Params);
    $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM Alumnos A INNER JOIN Grupos G ON G.Id = A.GrupoId WHERE {$Where}");
    $Stmt->execute($Params);
    return (int)$Stmt->fetchColumn();
}

function SgceRepoAlumnoListar(PDO $Pdo, array $Filtros, int $Limit, int $Offset): array {
    $Params = [];
    $Where = SgceRepoAlumnoWhere($Filtros, $Params);
    $Stmt = $Pdo->prepare("
        SELECT A.Id, A.NombreCompleto, A.GrupoId, G.Grado, G.Grupo, G.Turno
        FROM Alumnos A
        INNER JOIN Grupos G ON G.Id = A.GrupoId
        WHERE {$Where}
        ORDER BY G.Turno, G.Grado, G.Grupo, A.NombreCompleto, A.Id
        LIMIT ? OFFSET ?
    ");
    foreach ($Params as $I => $Param) { $Stmt->bindValue($I + 1, $Param); }
    $Stmt->bindValue(count($Params) + 1, max(1, min(100, $Limit)), PDO::PARAM_INT);
    $Stmt->bindValue(count($Params) + 2, max(0, $Offset), PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}

function SgceRepoAlumnoPorGrupo(PDO $Pdo, int $GrupoId, int $Limit = 1000, int $Offset = 0): array {
    $Stmt = $Pdo->prepare("
        SELECT A.Id, A.NombreCompleto, A.GrupoId, G.Grado, G.Grupo, G.Turno
        FROM Alumnos A
        INNER JOIN Grupos G ON G.Id = A.GrupoId
        WHERE A.Activo = 1 AND G.Activo = 1 AND A.GrupoId = ?
        ORDER BY A.NombreCompleto, A.Id
        LIMIT ? OFFSET ?
    ");
    $Stmt->bindValue(1, $GrupoId, PDO::PARAM_INT);
    $Stmt->bindValue(2, max(1, min(1000, $Limit)), PDO::PARAM_INT);
    $Stmt->bindValue(3, max(0, $Offset), PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}
