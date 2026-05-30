<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceCalificacionPromedioGeneralCiclo(PDO $Pdo, int $CicloId): string {
    if ($CicloId <= 0) { return '0.0'; }
    $Stmt = $Pdo->prepare("SELECT ROUND(AVG(C.Calificacion), 1) FROM Calificaciones C INNER JOIN PeriodosEvaluacion P ON P.Id = C.PeriodoId WHERE P.CicloId = ? AND P.Activo = 1");
    $Stmt->execute([$CicloId]);
    $Promedio = $Stmt->fetchColumn();
    return $Promedio !== null ? (string)$Promedio : '0.0';
}
