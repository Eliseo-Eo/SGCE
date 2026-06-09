<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceCalificacionPromedioGeneralCiclo(PDO $Pdo, int $CicloId): string {
    if ($CicloId <= 0) { return '0.0'; }
    $Oferta = function_exists('SgceOfertaActiva') ? SgceOfertaActiva($Pdo) : null;
    $OfertaId = (int)($Oferta['Id'] ?? 0);
    $Stmt = $Pdo->prepare("\n        SELECT ROUND(AVG(C.Calificacion), 1)\n        FROM Calificaciones C\n        INNER JOIN PeriodosEvaluacion P ON P.Id = C.PeriodoId AND P.Activo = 1\n        INNER JOIN Asignaciones A ON A.Id = C.AsignacionId AND A.Activo = 1 AND A.CicloId = P.CicloId\n        INNER JOIN AlumnoInscripciones AI ON AI.AlumnoId = C.AlumnoId AND AI.CicloId = P.CicloId AND AI.GrupoId = A.GrupoId AND AI.Estado IN ('INSCRITO','PROMOVIDO','EGRESADO')\n        INNER JOIN Alumnos Al ON Al.Id = C.AlumnoId AND Al.Activo = 1\n        WHERE P.CicloId = ?\n          AND (? = 0 OR P.OfertaId = ?)\n    ");
    $Stmt->execute([$CicloId, $OfertaId, $OfertaId]);
    $Promedio = $Stmt->fetchColumn();
    return $Promedio !== null ? (string)$Promedio : '0.0';
}


function SgceCalificacionCssClase(PDO $Pdo, $Valor): string {
    if ($Valor === null || $Valor === '') { return ''; }
    $Cfg = SgceCalificacionConfig($Pdo);
    $Valor = (float)$Valor;
    if ($Valor < $Cfg['Aprobatoria']) { return 'border-danger'; }
    $Alto = max($Cfg['Aprobatoria'], $Cfg['Maxima'] - (($Cfg['Maxima'] - $Cfg['Minima']) * 0.25));
    return $Valor >= $Alto ? 'border-success' : 'border-warning';
}
