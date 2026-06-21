<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/repositories/CalificacionRepository.php';

function SgceCalificacionPromedioGeneralCiclo(PDO $Pdo, int $CicloId): string {
    if ($CicloId <= 0) { return '0.0'; }
    $Oferta = function_exists('SgceOfertaActiva') ? SgceOfertaActiva($Pdo) : null;
    $OfertaId = (int)($Oferta['Id'] ?? 0);
    $Stmt = $Pdo->prepare("
        SELECT C.Calificacion
        FROM Calificaciones C
        INNER JOIN PeriodosEvaluacion P ON P.Id = C.PeriodoId AND P.Activo = 1
        INNER JOIN Asignaciones A ON A.Id = C.AsignacionId AND A.Activo = 1 AND A.CicloId = P.CicloId
        INNER JOIN AlumnoInscripciones AI ON AI.AlumnoId = C.AlumnoId AND AI.CicloId = P.CicloId AND AI.GrupoId = A.GrupoId AND AI.Estado IN ('INSCRITO','PROMOVIDO','EGRESADO')
        INNER JOIN Alumnos Al ON Al.Id = C.AlumnoId AND Al.Activo = 1
        WHERE P.CicloId = ?
          AND (? = 0 OR P.OfertaId = ?)
    ");
    $Stmt->execute([$CicloId, $OfertaId, $OfertaId]);
    $Promedio = SgcePromedioAcademico($Stmt->fetchAll(PDO::FETCH_COLUMN), 1);
    return $Promedio !== null ? number_format($Promedio, 1) : '0.0';
}

function SgceCalificarObtenerAsignacionDocente(PDO $Pdo, int $AsignacionId, int $MaestroId): ?array {
    return function_exists('SgceCalificacionRepoObtenerAsignacionDocente')
        ? SgceCalificacionRepoObtenerAsignacionDocente($Pdo, $AsignacionId, $MaestroId)
        : null;
}

function SgceCalificarGuardarCalificaciones(PDO $Pdo, int $AsignacionId, int $PeriodoId, int $GrupoId, int $CicloId, array $Notas): void {
    $StmtAlumnosValidos = $Pdo->prepare("\n        SELECT A.Id\n        FROM AlumnoInscripciones AI\n        INNER JOIN Alumnos A ON A.Id = AI.AlumnoId AND A.Activo = 1\n        WHERE AI.GrupoId = ?\n        AND AI.CicloId = ?\n        AND AI.Estado = 'INSCRITO'\n    ");
    $StmtAlumnosValidos->execute([$GrupoId, $CicloId]);
    $AlumnosValidos = array_flip(array_map('intval', $StmtAlumnosValidos->fetchAll(PDO::FETCH_COLUMN)));
    $StmtBuscar = $Pdo->prepare("\n        SELECT Id\n        FROM Calificaciones\n        WHERE AlumnoId = ?\n        AND AsignacionId = ?\n        AND PeriodoId = ?\n        ORDER BY Id DESC\n        LIMIT 1\n    ");
    $StmtActualizar = $Pdo->prepare("UPDATE Calificaciones SET Calificacion = ? WHERE Id = ?");
    $StmtInsertar = $Pdo->prepare("INSERT INTO Calificaciones (AlumnoId, AsignacionId, PeriodoId, Calificacion) VALUES (?, ?, ?, ?)");
    $StmtEliminar = $Pdo->prepare("DELETE FROM Calificaciones WHERE AlumnoId = ? AND AsignacionId = ? AND PeriodoId = ?");
    foreach ($Notas as $AlumnoId => $Calificacion) {
        $AlumnoId = (int)$AlumnoId;
        $Calificacion = trim((string)$Calificacion);
        if ($AlumnoId <= 0 || !isset($AlumnosValidos[$AlumnoId])) { continue; }
        if ($Calificacion === '') {
            $StmtEliminar->execute([$AlumnoId, $AsignacionId, $PeriodoId]);
            continue;
        }
        $CalificacionFloat = SgceCalificacionNormalizar($Pdo, $Calificacion);
        if ($CalificacionFloat === null) { continue; }
        $StmtBuscar->execute([$AlumnoId, $AsignacionId, $PeriodoId]);
        $CalificacionId = (int)$StmtBuscar->fetchColumn();
        if ($CalificacionId > 0) {
            $StmtActualizar->execute([$CalificacionFloat, $CalificacionId]);
        } else {
            $StmtInsertar->execute([$AlumnoId, $AsignacionId, $PeriodoId, $CalificacionFloat]);
        }
    }
    $StmtLimpiarDuplicados = $Pdo->prepare("\n        DELETE CalDuplicada\n        FROM Calificaciones CalDuplicada\n        INNER JOIN Calificaciones CalBase\n            ON CalBase.AlumnoId = CalDuplicada.AlumnoId\n            AND CalBase.AsignacionId = CalDuplicada.AsignacionId\n            AND CalBase.PeriodoId = CalDuplicada.PeriodoId\n            AND CalBase.Id > CalDuplicada.Id\n        WHERE CalDuplicada.AsignacionId = ?\n        AND CalDuplicada.PeriodoId = ?\n    ");
    $StmtLimpiarDuplicados->execute([$AsignacionId, $PeriodoId]);
}

function SgceCalificarAlumnosConCalificacion(PDO $Pdo, int $AsignacionId, int $PeriodoId, int $GrupoId, int $CicloId): array {
    return function_exists('SgceCalificacionRepoAlumnosConCalificacion')
        ? SgceCalificacionRepoAlumnosConCalificacion($Pdo, $AsignacionId, $PeriodoId, $GrupoId, $CicloId)
        : [];
}

function SgceCalificarResumen(array $Alumnos): array {
    $Total = count($Alumnos);
    $Calificados = 0;
    $Suma = 0.0;
    foreach ($Alumnos as $Alumno) {
        if ($Alumno['Calificacion'] !== null && $Alumno['Calificacion'] !== '') {
            $Calificados++;
            $Suma += (float)$Alumno['Calificacion'];
        }
    }
    return [
        'TotalAlumnos' => $Total,
        'Calificados' => $Calificados,
        'PromedioGrupo' => $Calificados > 0 ? number_format((float)SgcePromedioAcademico(array_column($Alumnos, 'Calificacion'), 1), 1) : '0.0',
    ];
}
