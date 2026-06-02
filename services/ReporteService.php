<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceAsignacionContarActivas(PDO $Pdo): int { return SgceAsignacionContarFiltradas($Pdo, []); }

function SgceAsignacionContarFiltradas(PDO $Pdo, array $Filtros = []): int { return SgceRepoAsignacionContar($Pdo, $Filtros); }

function SgceAsignacionListarPaginadas(PDO $Pdo, int $Limit, int $Offset): array { return SgceAsignacionListarFiltradas($Pdo, [], $Limit, $Offset); }

function SgceAsignacionListarFiltradas(PDO $Pdo, array $Filtros, int $Limit, int $Offset): array { return SgceRepoAsignacionListar($Pdo, $Filtros, $Limit, $Offset); }

function SgceReporteAlumnosRiesgo(PDO $Pdo, int $CicloId, string $FechaInicio, string $FechaFin, int $Limit = 10): array {
    if ($CicloId <= 0 || !SgceRepoValidarRangoReporte($FechaInicio, $FechaFin, 370)) { return []; }
    $Sql = "
        SELECT
            Al.Id, Al.NombreCompleto, G.Grado, G.Grupo, G.Turno,
            COALESCE(AsisAgg.Faltas, 0) AS Faltas,
            COALESCE(AsisAgg.Retardos, 0) AS Retardos,
            CalAgg.Promedio,
            (COALESCE(AsisAgg.Faltas, 0) * 3 + COALESCE(AsisAgg.Retardos, 0) + CASE WHEN CalAgg.Promedio IS NULL THEN 0 WHEN CalAgg.Promedio < 7 THEN ROUND((7 - CalAgg.Promedio) * 2, 1) ELSE 0 END) AS PuntajeRiesgo,
            TRIM(BOTH ' + ' FROM CONCAT(CASE WHEN COALESCE(AsisAgg.Faltas, 0) > 0 THEN 'FALTAS + ' ELSE '' END, CASE WHEN COALESCE(AsisAgg.Retardos, 0) > 0 THEN 'RETARDOS + ' ELSE '' END, CASE WHEN CalAgg.Promedio IS NOT NULL AND CalAgg.Promedio < 7 THEN 'PROMEDIO BAJO + ' ELSE '' END)) AS MotivoRiesgo,
            CASE WHEN (COALESCE(AsisAgg.Faltas, 0) * 3 + COALESCE(AsisAgg.Retardos, 0) + CASE WHEN CalAgg.Promedio IS NULL THEN 0 WHEN CalAgg.Promedio < 7 THEN ROUND((7 - CalAgg.Promedio) * 2, 1) ELSE 0 END) >= 10 THEN 'ALTO' WHEN (COALESCE(AsisAgg.Faltas, 0) * 3 + COALESCE(AsisAgg.Retardos, 0) + CASE WHEN CalAgg.Promedio IS NULL THEN 0 WHEN CalAgg.Promedio < 7 THEN ROUND((7 - CalAgg.Promedio) * 2, 1) ELSE 0 END) >= 5 THEN 'MEDIO' ELSE 'BAJO' END AS NivelRiesgo
        FROM AlumnoInscripciones AI
        INNER JOIN Alumnos Al ON Al.Id = AI.AlumnoId AND Al.Activo = 1
        INNER JOIN Grupos G ON G.Id = AI.GrupoId AND G.Activo = 1
        LEFT JOIN (
            SELECT Asi.AlumnoId, SUM(Asi.Estado = 'F') AS Faltas, SUM(Asi.Estado = 'R') AS Retardos
            FROM Asistencias Asi
            INNER JOIN Asignaciones A2 ON A2.Id = Asi.AsignacionId AND A2.Activo = 1 AND A2.CicloId = Asi.CicloId
            WHERE Asi.CicloId = ? AND Asi.FechaDia BETWEEN ? AND ?
            GROUP BY Asi.AlumnoId
        ) AsisAgg ON AsisAgg.AlumnoId = Al.Id
        LEFT JOIN (
            SELECT C.AlumnoId, ROUND(AVG(C.Calificacion), 1) AS Promedio
            FROM Calificaciones C
            INNER JOIN PeriodosEvaluacion P ON P.Id = C.PeriodoId AND P.Activo = 1
            INNER JOIN Asignaciones A3 ON A3.Id = C.AsignacionId AND A3.Activo = 1 AND A3.CicloId = P.CicloId
            WHERE P.CicloId = ?
            GROUP BY C.AlumnoId
        ) CalAgg ON CalAgg.AlumnoId = Al.Id
        WHERE AI.CicloId = ? AND AI.Estado = 'INSCRITO'
        HAVING Faltas > 0 OR Retardos > 0 OR (Promedio IS NOT NULL AND Promedio < 7)
        ORDER BY PuntajeRiesgo DESC, Faltas DESC, Retardos DESC, Promedio ASC, Al.NombreCompleto ASC
        LIMIT ?
    ";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->bindValue(1, $CicloId, PDO::PARAM_INT);
    $Stmt->bindValue(2, $FechaInicio);
    $Stmt->bindValue(3, $FechaFin);
    $Stmt->bindValue(4, $CicloId, PDO::PARAM_INT);
    $Stmt->bindValue(5, $CicloId, PDO::PARAM_INT);
    $Stmt->bindValue(6, max(1, min(50, $Limit)), PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}

function SgceReporteBitacoraContar(PDO $Pdo, array $Filtros = []): int { return SgceRepoBitacoraContar($Pdo, $Filtros); }

function SgceReporteBitacoraPaginada(PDO $Pdo, array $Filtros, int $Limit, int $Offset): array { return SgceRepoBitacoraListar($Pdo, $Filtros, $Limit, $Offset); }

function SgceReporteBitacoraReciente(PDO $Pdo, int $Limit = 100): array { return SgceRepoBitacoraListar($Pdo, [], $Limit, 0); }
