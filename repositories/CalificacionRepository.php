<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceCalificacionRepoObtenerAsignacionDocente(PDO $Pdo, int $AsignacionId, int $MaestroId): ?array {
    $Stmt = $Pdo->prepare("
        SELECT A.*, G.Grado, G.Grupo, G.Turno, C.Nombre AS CicloNombre
        FROM Asignaciones A
        JOIN Grupos G ON A.GrupoId = G.Id AND G.CicloId = A.CicloId
        JOIN CiclosEscolares C ON C.Id = A.CicloId AND C.Activo = 1
        WHERE A.Id = ? AND A.MaestroId = ? AND A.Activo = 1
        LIMIT 1
    ");
    $Stmt->execute([$AsignacionId, $MaestroId]);
    $Info = $Stmt->fetch(PDO::FETCH_ASSOC);
    return $Info ?: null;
}

function SgceCalificacionRepoAlumnosConCalificacion(PDO $Pdo, int $AsignacionId, int $PeriodoId, int $GrupoId, int $CicloId): array {
    $Stmt = $Pdo->prepare("
        SELECT Al.Id AS AlumnoId, Al.NombreCompleto, C.Calificacion
        FROM AlumnoInscripciones AI
        INNER JOIN Alumnos Al ON Al.Id = AI.AlumnoId
        LEFT JOIN (
            SELECT AlumnoId, MAX(Id) AS UltimaCalificacionId
            FROM Calificaciones
            WHERE AsignacionId = ? AND PeriodoId = ?
            GROUP BY AlumnoId
        ) CU ON CU.AlumnoId = Al.Id
        LEFT JOIN Calificaciones C ON C.Id = CU.UltimaCalificacionId
        WHERE AI.GrupoId = ? AND AI.CicloId = ? AND AI.Estado = 'INSCRITO' AND Al.Activo = 1
        ORDER BY Al.NombreCompleto ASC
    ");
    $Stmt->execute([$AsignacionId, $PeriodoId, $GrupoId, $CicloId]);
    return $Stmt->fetchAll(PDO::FETCH_ASSOC);
}
