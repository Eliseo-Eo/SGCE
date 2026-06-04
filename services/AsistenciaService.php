<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceAsistenciaResumenHoy(PDO $Pdo): array {
    $Ciclo = SgceCicloActivo($Pdo);
    $CicloId = (int)($Ciclo['Id'] ?? 0);
    if ($CicloId <= 0) { return ['Total' => 0, 'Faltas' => 0]; }
    $Stmt = $Pdo->prepare("\n        SELECT\n            COUNT(*) AS Total,\n            SUM(CASE WHEN Asi.Estado = 'F' THEN 1 ELSE 0 END) AS Faltas\n        FROM Asistencias Asi\n        INNER JOIN Asignaciones A ON A.Id = Asi.AsignacionId AND A.Activo = 1 AND A.CicloId = Asi.CicloId\n        INNER JOIN Usuarios U ON U.Id = A.MaestroId AND U.Activo = 1\n        INNER JOIN Grupos G ON G.Id = A.GrupoId AND G.Activo = 1 AND G.CicloId = Asi.CicloId\n        INNER JOIN AlumnoInscripciones AI ON AI.AlumnoId = Asi.AlumnoId AND AI.CicloId = Asi.CicloId AND AI.GrupoId = A.GrupoId AND AI.Estado = 'INSCRITO'\n        INNER JOIN Alumnos Al ON Al.Id = Asi.AlumnoId AND Al.Activo = 1\n        WHERE Asi.CicloId = ? AND Asi.FechaDia = CURDATE()\n    ");
    $Stmt->execute([$CicloId]);
    $Row = $Stmt->fetch() ?: [];
    return [
        'Total' => (int)($Row['Total'] ?? 0),
        'Faltas' => (int)($Row['Faltas'] ?? 0),
    ];
}
