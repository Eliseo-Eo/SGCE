<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

// Funciones académicas de asignaciones docentes.

function SgceAsignacionObtener(PDO $Pdo, int $AsignacionId) {
    $Stmt = $Pdo->prepare("SELECT A.Id, A.CicloId, A.MaestroId, A.GrupoId, A.MateriaGrupoId, A.MateriaId, A.MateriaNombre, A.HorasSemana, A.Activo,
        G.Grado, G.Grupo, G.Turno, C.Nombre AS CicloNombre, C.Activo AS CicloActivo,
        U.NombreCompleto AS MaestroNombre
        FROM Asignaciones A
        INNER JOIN Grupos G ON G.Id = A.GrupoId AND G.CicloId = A.CicloId
        INNER JOIN CiclosEscolares C ON C.Id = A.CicloId
        INNER JOIN Usuarios U ON U.Id = A.MaestroId
        WHERE A.Id = ? LIMIT 1");
    $Stmt->execute([$AsignacionId]);
    return $Stmt->fetch() ?: null;
}

function SgceGrupoTieneUsoAcademico(PDO $Pdo, int $GrupoId): bool {
    if ($GrupoId <= 0) { return false; }
    $Consultas = [
        'SELECT COUNT(*) FROM AlumnoInscripciones WHERE GrupoId = ?',
        'SELECT COUNT(*) FROM MateriasGrupo WHERE GrupoId = ?',
        'SELECT COUNT(*) FROM Asignaciones WHERE GrupoId = ?',
        'SELECT COUNT(*) FROM Asistencias Asi INNER JOIN Asignaciones A ON A.Id = Asi.AsignacionId WHERE A.GrupoId = ?',
        'SELECT COUNT(*) FROM Calificaciones Cal INNER JOIN Asignaciones A ON A.Id = Cal.AsignacionId WHERE A.GrupoId = ?',
    ];
    foreach ($Consultas as $Sql) {
        try { $Stmt = $Pdo->prepare($Sql); $Stmt->execute([$GrupoId]); if ((int)$Stmt->fetchColumn() > 0) { return true; } } catch (Throwable $E) {}
    }
    return false;
}

function SgceCicloOfertaTieneCalificaciones(PDO $Pdo, int $CicloId, int $OfertaId): bool {
    if ($CicloId <= 0 || $OfertaId <= 0) { return false; }
    $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM Calificaciones Cal INNER JOIN PeriodosEvaluacion P ON P.Id = Cal.PeriodoId WHERE P.CicloId = ? AND P.OfertaId = ?');
    $Stmt->execute([$CicloId, $OfertaId]);
    return (int)$Stmt->fetchColumn() > 0;
}

function SgceCicloOfertaTienePlaneaciones(PDO $Pdo, int $CicloId, int $OfertaId): bool {
    if ($CicloId <= 0 || $OfertaId <= 0 || !SgceDbTablaExiste($Pdo, 'Planeaciones')) { return false; }
    $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM Planeaciones WHERE CicloId = ? AND OfertaId = ?');
    $Stmt->execute([$CicloId, $OfertaId]);
    return (int)$Stmt->fetchColumn() > 0;
}

function SgceAsignacionTieneDatosAcademicos(PDO $Pdo, int $AsignacionId): bool {
    if ($AsignacionId <= 0) { return false; }
    $Total = 0;
    if (SgceDbTablaExiste($Pdo, 'Calificaciones')) {
        $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM Calificaciones WHERE AsignacionId = ?');
        $Stmt->execute([$AsignacionId]);
        $Total += (int)$Stmt->fetchColumn();
    }
    if (SgceDbTablaExiste($Pdo, 'Asistencias')) {
        $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM Asistencias WHERE AsignacionId = ?');
        $Stmt->execute([$AsignacionId]);
        $Total += (int)$Stmt->fetchColumn();
    }
    return $Total > 0;
}

function SgceDocenteAsignacionesActuales(PDO $Pdo, int $MaestroId): int {
    if ($MaestroId <= 0 || !SgceDbTablaExiste($Pdo, 'Asignaciones')) { return 0; }
    $Stmt = $Pdo->prepare("SELECT COUNT(*)
        FROM Asignaciones A
        INNER JOIN CiclosEscolares C ON C.Id = A.CicloId AND C.Activo = 1
        INNER JOIN Grupos G ON G.Id = A.GrupoId AND G.CicloId = A.CicloId AND G.Activo = 1
        WHERE A.MaestroId = ? AND A.Activo = 1");
    $Stmt->execute([$MaestroId]);
    return (int)$Stmt->fetchColumn();
}

function SgceAsignacionTieneHistorialActivo(PDO $Pdo, int $AsignacionId, int $MaestroId): bool {
    if (!SgceDbTablaExiste($Pdo, 'AsignacionDocenteHistorial')) { return false; }
    $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM AsignacionDocenteHistorial WHERE AsignacionId = ? AND MaestroId = ? AND FechaFin IS NULL');
    $Stmt->execute([$AsignacionId, $MaestroId]);
    return (int)$Stmt->fetchColumn() > 0;
}

function SgceRegistrarDocenteAsignacionActual(PDO $Pdo, int $AsignacionId, int $MaestroId, int $UsuarioId = 0, string $Tipo = 'TITULAR', string $Motivo = ''): void {
    if ($AsignacionId <= 0 || $MaestroId <= 0 || !SgceDbTablaExiste($Pdo, 'AsignacionDocenteHistorial')) { return; }
    $Tipo = in_array($Tipo, ['TITULAR','INTERINATO','RELEVO'], true) ? $Tipo : 'TITULAR';
    if (SgceAsignacionTieneHistorialActivo($Pdo, $AsignacionId, $MaestroId)) { return; }
    $StmtCerrar = $Pdo->prepare('UPDATE AsignacionDocenteHistorial SET FechaFin = NOW() WHERE AsignacionId = ? AND FechaFin IS NULL');
    $StmtCerrar->execute([$AsignacionId]);
    $Stmt = $Pdo->prepare('INSERT INTO AsignacionDocenteHistorial (AsignacionId, MaestroId, FechaInicio, TipoMovimiento, Motivo, RegistradoPor) VALUES (?, ?, NOW(), ?, ?, NULLIF(?,0))');
    $Stmt->execute([$AsignacionId, $MaestroId, $Tipo, $Motivo, $UsuarioId]);
}

function SgceRelevarDocenteAsignacion(PDO $Pdo, int $AsignacionId, int $NuevoMaestroId, int $UsuarioId = 0, string $Motivo = 'RELEVO DOCENTE / INTERINATO'): bool {
    $Asignacion = SgceAsignacionObtener($Pdo, $AsignacionId);
    if (!$Asignacion) { throw new RuntimeException('La asignación no existe.'); }
    if ((int)$Asignacion['CicloActivo'] !== 1 || (int)$Asignacion['Activo'] !== 1) { throw new RuntimeException('Solo puedes relevar docentes en asignaciones activas del ciclo activo.'); }
    if (!SgceMaestroExisteActivo($Pdo, $NuevoMaestroId)) { throw new RuntimeException('El nuevo docente debe estar activo.'); }
    $MaestroAnteriorId = (int)$Asignacion['MaestroId'];
    if ($MaestroAnteriorId === $NuevoMaestroId) {
        SgceRegistrarDocenteAsignacionActual($Pdo, $AsignacionId, $NuevoMaestroId, $UsuarioId, 'TITULAR', 'REGISTRO ACTUAL SIN CAMBIO');
        return false;
    }
    if (!SgceAsignacionTieneHistorialActivo($Pdo, $AsignacionId, $MaestroAnteriorId)) {
        $Pdo->prepare('INSERT INTO AsignacionDocenteHistorial (AsignacionId, MaestroId, FechaInicio, FechaFin, TipoMovimiento, Motivo, RegistradoPor) VALUES (?, ?, NULL, NOW(), ?, ?, NULLIF(?,0))')
            ->execute([$AsignacionId, $MaestroAnteriorId, 'TITULAR', 'DOCENTE RESPONSABLE ANTERIOR ANTES DEL RELEVO', $UsuarioId]);
    } else {
        $Pdo->prepare('UPDATE AsignacionDocenteHistorial SET FechaFin = NOW() WHERE AsignacionId = ? AND MaestroId = ? AND FechaFin IS NULL')
            ->execute([$AsignacionId, $MaestroAnteriorId]);
    }
    $Pdo->prepare('UPDATE Asignaciones SET MaestroId = ? WHERE Id = ?')->execute([$NuevoMaestroId, $AsignacionId]);
    $Pdo->prepare('INSERT INTO AsignacionDocenteHistorial (AsignacionId, MaestroId, FechaInicio, TipoMovimiento, Motivo, RegistradoPor) VALUES (?, ?, NOW(), ?, ?, NULLIF(?,0))')
        ->execute([$AsignacionId, $NuevoMaestroId, 'INTERINATO', $Motivo, $UsuarioId]);
    return true;
}

