<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceAsignacionContarActivas(PDO $Pdo): int {
    return (int)$Pdo->query("SELECT COUNT(*) FROM Asignaciones Asn JOIN Usuarios U ON Asn.MaestroId = U.Id JOIN Grupos G ON Asn.GrupoId = G.Id WHERE Asn.Activo = 1 AND U.Activo = 1 AND G.Activo = 1")->fetchColumn();
}

function SgceAsignacionListarPaginadas(PDO $Pdo, int $Limit, int $Offset): array {
    $Stmt = $Pdo->prepare("SELECT Asn.Id, Asn.MateriaNombre, U.NombreCompleto AS Maestro, U.Id AS MaestroId, G.Id AS GrupoId, G.Grado, G.Grupo, G.Turno FROM Asignaciones Asn JOIN Usuarios U ON Asn.MaestroId = U.Id JOIN Grupos G ON Asn.GrupoId = G.Id WHERE Asn.Activo = 1 AND U.Activo = 1 AND G.Activo = 1 ORDER BY U.NombreCompleto ASC LIMIT ? OFFSET ?");
    $Stmt->bindValue(1, $Limit, PDO::PARAM_INT);
    $Stmt->bindValue(2, $Offset, PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}

function SgceReporteAlumnosRiesgo(PDO $Pdo, int $CicloId, string $FechaInicio, string $FechaFin, int $Limit = 10): array {
    if ($CicloId <= 0) { return []; }

    $Sql = "
        SELECT
            Al.Id,
            Al.NombreCompleto,
            G.Grado,
            G.Grupo,
            G.Turno,
            COALESCE(AsisAgg.Faltas, 0) AS Faltas,
            COALESCE(AsisAgg.Retardos, 0) AS Retardos,
            CalAgg.Promedio,
            (
                COALESCE(AsisAgg.Faltas, 0) * 3
                + COALESCE(AsisAgg.Retardos, 0)
                + CASE
                    WHEN CalAgg.Promedio IS NULL THEN 0
                    WHEN CalAgg.Promedio < 7 THEN ROUND((7 - CalAgg.Promedio) * 2, 1)
                    ELSE 0
                  END
            ) AS PuntajeRiesgo,
            TRIM(BOTH ' + ' FROM CONCAT(
                CASE WHEN COALESCE(AsisAgg.Faltas, 0) > 0 THEN 'FALTAS + ' ELSE '' END,
                CASE WHEN COALESCE(AsisAgg.Retardos, 0) > 0 THEN 'RETARDOS + ' ELSE '' END,
                CASE WHEN CalAgg.Promedio IS NOT NULL AND CalAgg.Promedio < 7 THEN 'PROMEDIO BAJO + ' ELSE '' END
            )) AS MotivoRiesgo,
            CASE
                WHEN (
                    COALESCE(AsisAgg.Faltas, 0) * 3
                    + COALESCE(AsisAgg.Retardos, 0)
                    + CASE
                        WHEN CalAgg.Promedio IS NULL THEN 0
                        WHEN CalAgg.Promedio < 7 THEN ROUND((7 - CalAgg.Promedio) * 2, 1)
                        ELSE 0
                      END
                ) >= 10 THEN 'ALTO'
                WHEN (
                    COALESCE(AsisAgg.Faltas, 0) * 3
                    + COALESCE(AsisAgg.Retardos, 0)
                    + CASE
                        WHEN CalAgg.Promedio IS NULL THEN 0
                        WHEN CalAgg.Promedio < 7 THEN ROUND((7 - CalAgg.Promedio) * 2, 1)
                        ELSE 0
                      END
                ) >= 5 THEN 'MEDIO'
                ELSE 'BAJO'
            END AS NivelRiesgo
        FROM Alumnos Al
        INNER JOIN Grupos G ON Al.GrupoId = G.Id AND G.Activo = 1
        LEFT JOIN (
            SELECT
                Asi.AlumnoId,
                SUM(CASE WHEN Asi.Estado = 'F' THEN 1 ELSE 0 END) AS Faltas,
                SUM(CASE WHEN Asi.Estado = 'R' THEN 1 ELSE 0 END) AS Retardos
            FROM Asistencias Asi
            INNER JOIN Asignaciones A2 ON A2.Id = Asi.AsignacionId AND A2.Activo = 1
            INNER JOIN Usuarios U2 ON U2.Id = A2.MaestroId AND U2.Activo = 1
            INNER JOIN Grupos G2 ON G2.Id = A2.GrupoId AND G2.Activo = 1
            INNER JOIN Alumnos Al2 ON Al2.Id = Asi.AlumnoId AND Al2.Activo = 1 AND Al2.GrupoId = G2.Id
            WHERE Asi.FechaDia BETWEEN ? AND ?
            GROUP BY Asi.AlumnoId
        ) AsisAgg ON AsisAgg.AlumnoId = Al.Id
        LEFT JOIN (
            SELECT
                C.AlumnoId,
                ROUND(AVG(C.Calificacion), 1) AS Promedio
            FROM Calificaciones C
            INNER JOIN PeriodosEvaluacion P ON P.Id = C.PeriodoId AND P.Activo = 1
            INNER JOIN Asignaciones A3 ON A3.Id = C.AsignacionId AND A3.Activo = 1
            INNER JOIN Usuarios U3 ON U3.Id = A3.MaestroId AND U3.Activo = 1
            INNER JOIN Grupos G3 ON G3.Id = A3.GrupoId AND G3.Activo = 1
            INNER JOIN Alumnos Al3 ON Al3.Id = C.AlumnoId AND Al3.Activo = 1 AND Al3.GrupoId = G3.Id
            WHERE P.CicloId = ?
            GROUP BY C.AlumnoId
        ) CalAgg ON CalAgg.AlumnoId = Al.Id
        WHERE Al.Activo = 1
        HAVING Faltas > 0 OR Retardos > 0 OR (Promedio IS NOT NULL AND Promedio < 7)
        ORDER BY PuntajeRiesgo DESC, Faltas DESC, Retardos DESC, Promedio ASC, Al.NombreCompleto ASC
        LIMIT ?
    ";

    $Stmt = $Pdo->prepare($Sql);
    $Stmt->bindValue(1, $FechaInicio);
    $Stmt->bindValue(2, $FechaFin);
    $Stmt->bindValue(3, $CicloId, PDO::PARAM_INT);
    $Stmt->bindValue(4, $Limit, PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}

function SgceReporteBitacoraReciente(PDO $Pdo, int $Limit = 100): array {
    if (function_exists('CrearTablaBitacoraSiNoExiste')) {
        CrearTablaBitacoraSiNoExiste($Pdo);
    }
    $Stmt = $Pdo->prepare("SELECT B.*, U.NombreCompleto FROM BitacoraMovimientos B LEFT JOIN Usuarios U ON B.UsuarioId = U.Id ORDER BY B.FechaRegistro DESC LIMIT ?");
    $Stmt->bindValue(1, $Limit, PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}
