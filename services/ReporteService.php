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
    $Stmt = $Pdo->prepare("\n        SELECT\n            Al.Id,\n            Al.NombreCompleto,\n            G.Grado,\n            G.Grupo,\n            G.Turno,\n            COALESCE(AsisAgg.Faltas, 0) AS Faltas,\n            COALESCE(AsisAgg.Retardos, 0) AS Retardos,\n            CalAgg.Promedio,\n            (\n                COALESCE(AsisAgg.Faltas, 0) * 3\n                + COALESCE(AsisAgg.Retardos, 0)\n                + CASE\n                    WHEN CalAgg.Promedio IS NULL THEN 0\n                    WHEN CalAgg.Promedio < 7 THEN ROUND((7 - CalAgg.Promedio) * 2, 1)\n                    ELSE 0\n                  END\n            ) AS PuntajeRiesgo,\n            TRIM(BOTH ' + ' FROM CONCAT(\n                CASE WHEN COALESCE(AsisAgg.Faltas, 0) > 0 THEN 'FALTAS + ' ELSE '' END,\n                CASE WHEN COALESCE(AsisAgg.Retardos, 0) > 0 THEN 'RETARDOS + ' ELSE '' END,\n                CASE WHEN CalAgg.Promedio IS NOT NULL AND CalAgg.Promedio < 7 THEN 'PROMEDIO BAJO + ' ELSE '' END\n            )) AS MotivoRiesgo,\n            CASE\n                WHEN (\n                    COALESCE(AsisAgg.Faltas, 0) * 3\n                    + COALESCE(AsisAgg.Retardos, 0)\n                    + CASE\n                        WHEN CalAgg.Promedio IS NULL THEN 0\n                        WHEN CalAgg.Promedio < 7 THEN ROUND((7 - CalAgg.Promedio) * 2, 1)\n                        ELSE 0\n                      END\n                ) >= 10 THEN 'ALTO'\n                WHEN (\n                    COALESCE(AsisAgg.Faltas, 0) * 3\n                    + COALESCE(AsisAgg.Retardos, 0)\n                    + CASE\n                        WHEN CalAgg.Promedio IS NULL THEN 0\n                        WHEN CalAgg.Promedio < 7 THEN ROUND((7 - CalAgg.Promedio) * 2, 1)\n                        ELSE 0\n                      END\n                ) >= 5 THEN 'MEDIO'\n                ELSE 'BAJO'\n            END AS NivelRiesgo\n        FROM Alumnos Al\n        JOIN Grupos G ON Al.GrupoId = G.Id\n        LEFT JOIN (\n            SELECT\n                AlumnoId,\n                SUM(CASE WHEN Estado='F' THEN 1 ELSE 0 END) AS Faltas,\n                SUM(CASE WHEN Estado='R' THEN 1 ELSE 0 END) AS Retardos\n            FROM Asistencias\n            WHERE FechaDia BETWEEN ? AND ?\n            GROUP BY AlumnoId\n        ) AsisAgg ON AsisAgg.AlumnoId = Al.Id\n        LEFT JOIN (\n            SELECT\n                AlumnoId,\n                ROUND(AVG(Calificacion), 1) AS Promedio\n            FROM Calificaciones C\n            INNER JOIN PeriodosEvaluacion P ON P.Id = C.PeriodoId\n            WHERE P.CicloId = ?\n            GROUP BY AlumnoId\n        ) CalAgg ON CalAgg.AlumnoId = Al.Id\n        WHERE Al.Activo = 1 AND G.Activo = 1\n        HAVING Faltas > 0 OR Retardos > 0 OR (Promedio IS NOT NULL AND Promedio < 7)\n        ORDER BY PuntajeRiesgo DESC, Faltas DESC, Retardos DESC, Promedio ASC, Al.NombreCompleto ASC\n        LIMIT ?\n    ");
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
    $Stmt = $Pdo->prepare("\n        SELECT B.*, U.NombreCompleto\n        FROM BitacoraMovimientos B\n        LEFT JOIN Usuarios U ON B.UsuarioId = U.Id\n        ORDER BY B.FechaRegistro DESC\n        LIMIT ?\n    ");
    $Stmt->bindValue(1, $Limit, PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}
