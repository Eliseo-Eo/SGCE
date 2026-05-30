<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceAsistenciaResumenHoy(PDO $Pdo): array {
    $Stmt = $Pdo->query("\n        SELECT\n            COUNT(*) AS Total,\n            SUM(CASE WHEN Estado = 'F' THEN 1 ELSE 0 END) AS Faltas\n        FROM Asistencias\n        WHERE FechaDia = CURDATE()\n    ");
    $Row = $Stmt->fetch() ?: [];
    return [
        'Total' => (int)($Row['Total'] ?? 0),
        'Faltas' => (int)($Row['Faltas'] ?? 0),
    ];
}
