<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceAsistenciaResumenHoy(PDO $Pdo): array {
    $Stmt = $Pdo->query("\n        SELECT\n            COUNT(*) AS Total,\n            SUM(CASE WHEN Asi.Estado = 'F' THEN 1 ELSE 0 END) AS Faltas\n        FROM Asistencias Asi\n        INNER JOIN Asignaciones A ON A.Id = Asi.AsignacionId AND A.Activo = 1\n        INNER JOIN Usuarios U ON U.Id = A.MaestroId AND U.Activo = 1\n        INNER JOIN Grupos G ON G.Id = A.GrupoId AND G.Activo = 1\n        INNER JOIN Alumnos Al ON Al.Id = Asi.AlumnoId AND Al.Activo = 1\n        WHERE Asi.FechaDia = CURDATE()\n    ");
    $Row = $Stmt->fetch() ?: [];
    return [
        'Total' => (int)($Row['Total'] ?? 0),
        'Faltas' => (int)($Row['Faltas'] ?? 0),
    ];
}
