<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceAsistenciaRepoObtenerAsignacionActiva(PDO $Pdo, int $AsignacionId): ?array {
    if ($AsignacionId <= 0) { return null; }
    $Stmt = $Pdo->prepare("
        SELECT A.Id, A.MaestroId, A.GrupoId, A.CicloId, A.MateriaNombre,
               G.Grado, G.Grupo, G.Turno, C.Nombre AS CicloNombre, C.FechaInicio, C.FechaFin
        FROM Asignaciones A
        JOIN Grupos G ON A.GrupoId = G.Id AND G.CicloId = A.CicloId
        JOIN CiclosEscolares C ON C.Id = A.CicloId
        WHERE A.Id = ? AND A.Activo = 1 AND G.Activo = 1 AND C.Activo = 1
        LIMIT 1
    ");
    $Stmt->execute([$AsignacionId]);
    $Info = $Stmt->fetch(PDO::FETCH_ASSOC);
    return $Info ?: null;
}


function SgceAsistenciaRepoObtenerAsignacionContexto(PDO $Pdo, int $AsignacionId): ?array {
    if ($AsignacionId <= 0) { return null; }
    $Stmt = $Pdo->prepare("
        SELECT A.Id, A.MaestroId, A.GrupoId, A.CicloId, A.MateriaNombre, A.Activo AS AsignacionActiva,
               G.Grado, G.Grupo, G.Turno, G.Activo AS GrupoActivo,
               C.Nombre AS CicloNombre, C.FechaInicio, C.FechaFin, C.Activo AS CicloActivo
        FROM Asignaciones A
        JOIN Grupos G ON A.GrupoId = G.Id AND G.CicloId = A.CicloId
        JOIN CiclosEscolares C ON C.Id = A.CicloId
        WHERE A.Id = ?
        LIMIT 1
    ");
    $Stmt->execute([$AsignacionId]);
    $Info = $Stmt->fetch(PDO::FETCH_ASSOC);
    return $Info ?: null;
}

function SgceAsistenciaRepoObtenerAlumnos(PDO $Pdo, int $GrupoId, int $CicloId): array {
    $Stmt = $Pdo->prepare("
        SELECT A.Id, A.NombreCompleto
        FROM AlumnoInscripciones AI
        INNER JOIN Alumnos A ON A.Id = AI.AlumnoId
        WHERE AI.GrupoId = ? AND AI.CicloId = ? AND AI.Estado = 'INSCRITO' AND A.Activo = 1
        ORDER BY A.NombreCompleto ASC
    ");
    $Stmt->execute([$GrupoId, $CicloId]);
    return $Stmt->fetchAll(PDO::FETCH_ASSOC);
}

function SgceAsistenciaRepoExisteFecha(PDO $Pdo, int $CicloId, int $AsignacionId, string $Fecha): bool {
    $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM Asistencias WHERE CicloId = ? AND AsignacionId = ? AND FechaDia = ?');
    $Stmt->execute([$CicloId, $AsignacionId, $Fecha]);
    return (int)$Stmt->fetchColumn() > 0;
}

function SgceAsistenciaRepoEstadosRegistrados(PDO $Pdo, int $CicloId, int $AsignacionId, string $Fecha): array {
    $Estados = [];
    $Stmt = $Pdo->prepare('SELECT AlumnoId, Estado FROM Asistencias WHERE CicloId = ? AND AsignacionId = ? AND FechaDia = ? ORDER BY Id ASC');
    $Stmt->execute([$CicloId, $AsignacionId, $Fecha]);
    foreach ($Stmt->fetchAll(PDO::FETCH_ASSOC) as $RowEstado) {
        $Estados[(int)$RowEstado['AlumnoId']] = SgceAsistenciaEstadoSeguro($RowEstado['Estado'] ?? 'A');
    }
    return $Estados;
}
