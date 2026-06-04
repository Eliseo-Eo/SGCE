<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

// Funciones académicas de kardex histórico.

function SgceKardexAlumnoExiste(PDO $Pdo, int $AlumnoId, int $CicloId): bool {
    if (!SgceDbTablaExiste($Pdo, 'KardexAlumno')) { return false; }
    $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM KardexAlumno WHERE AlumnoId = ? AND CicloId = ?');
    $Stmt->execute([$AlumnoId, $CicloId]);
    return (int)$Stmt->fetchColumn() > 0;
}

function SgceKardexCongelarAlumnoCiclo(PDO $Pdo, int $AlumnoId, int $CicloId, int $UsuarioId = 0, bool $Forzar = true): bool {
    if ($AlumnoId <= 0 || $CicloId <= 0 || !SgceDbTablaExiste($Pdo, 'KardexAlumno') || !SgceDbTablaExiste($Pdo, 'KardexDetalle')) { return false; }
    $StmtInfo = $Pdo->prepare("SELECT A.Id AS AlumnoId, A.NombreCompleto, AI.GrupoId, AI.Estado, C.Nombre AS CicloNombre,
            G.Grado, G.Grupo, G.Turno, G.OfertaId,
            OE.Nombre AS OfertaNombre, CA.Nombre AS ProgramaNombre, EA.Nombre AS EtapaNombre
        FROM AlumnoInscripciones AI
        INNER JOIN Alumnos A ON A.Id = AI.AlumnoId
        INNER JOIN CiclosEscolares C ON C.Id = AI.CicloId
        INNER JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId
        LEFT JOIN OfertasEducativas OE ON OE.Id = G.OfertaId
        LEFT JOIN ProgramasEducativos CA ON CA.Id = G.ProgramaId
        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId
        WHERE AI.AlumnoId = ? AND AI.CicloId = ? LIMIT 1");
    $StmtInfo->execute([$AlumnoId, $CicloId]);
    $Info = $StmtInfo->fetch();
    if (!$Info) { return false; }

    if (!$Forzar && SgceKardexAlumnoExiste($Pdo, $AlumnoId, $CicloId)) { return true; }

    $StmtDetalle = $Pdo->prepare("SELECT Asg.Id AS AsignacionId, Asg.MateriaNombre, U.NombreCompleto AS MaestroNombre,
            P.Nombre AS PeriodoNombre, P.Orden AS PeriodoOrden, Cal.Calificacion
        FROM Asignaciones Asg
        LEFT JOIN Usuarios U ON U.Id = Asg.MaestroId
        LEFT JOIN PeriodosEvaluacion P ON P.CicloId = Asg.CicloId AND P.OfertaId = ? AND P.Activo = 1
        LEFT JOIN Calificaciones Cal ON Cal.AlumnoId = ? AND Cal.AsignacionId = Asg.Id AND Cal.PeriodoId = P.Id
        WHERE Asg.CicloId = ? AND Asg.GrupoId = ?
        ORDER BY Asg.MateriaNombre ASC, Asg.Id ASC, P.Orden ASC");
    $StmtDetalle->execute([(int)($Info['OfertaId'] ?? 0), $AlumnoId, $CicloId, (int)$Info['GrupoId']]);
    $Agrupados = [];
    foreach ($StmtDetalle->fetchAll() as $D) {
        $AsignacionId = (int)$D['AsignacionId'];
        if (!isset($Agrupados[$AsignacionId])) {
            $Agrupados[$AsignacionId] = [
                'MateriaNombre' => (string)$D['MateriaNombre'],
                'MaestroNombre' => (string)($D['MaestroNombre'] ?? ''),
                'Periodos' => [],
                'Promedio' => null,
                'P1' => null,
                'P2' => null,
                'P3' => null,
            ];
        }
        if ($D['PeriodoNombre'] !== null) {
            $Cal = $D['Calificacion'] !== null && $D['Calificacion'] !== '' ? (float)$D['Calificacion'] : null;
            $OrdenPeriodo = (int)($D['PeriodoOrden'] ?? 0);
            $Agrupados[$AsignacionId]['Periodos'][] = ['Nombre' => (string)$D['PeriodoNombre'], 'Orden' => $OrdenPeriodo, 'Calificacion' => $Cal];
            if ($OrdenPeriodo >= 1 && $OrdenPeriodo <= 3) { $Agrupados[$AsignacionId]['P'.$OrdenPeriodo] = $Cal; }
        }
    }
    $Detalles = [];
    $Suma = 0.0; $Cuenta = 0;
    foreach ($Agrupados as $D) {
        $SumaMateria = 0.0; $CuentaMateria = 0;
        foreach ($D['Periodos'] as $PDet) {
            if ($PDet['Calificacion'] !== null) { $SumaMateria += (float)$PDet['Calificacion']; $CuentaMateria++; $Suma += (float)$PDet['Calificacion']; $Cuenta++; }
        }
        if ($CuentaMateria <= 0) { continue; }
        $D['Promedio'] = round($SumaMateria / $CuentaMateria, 2);
        $Detalles[] = $D;
    }
    $PromedioFinal = $Cuenta > 0 ? round($Suma / $Cuenta, 2) : null;

    $TransaccionPropia = !$Pdo->inTransaction();
    if ($TransaccionPropia) { $Pdo->beginTransaction(); }
    try {
        $StmtId = $Pdo->prepare('SELECT Id FROM KardexAlumno WHERE AlumnoId = ? AND CicloId = ? LIMIT 1 FOR UPDATE');
        $StmtId->execute([$AlumnoId, $CicloId]);
        $KardexId = (int)$StmtId->fetchColumn();
        if ($KardexId > 0) {
            $Pdo->prepare('UPDATE KardexAlumno SET GrupoId = ?, CicloNombreSnapshot = ?, GradoSnapshot = ?, GrupoSnapshot = ?, TurnoSnapshot = ?, OfertaNombreSnapshot = ?, ProgramaNombreSnapshot = ?, EtapaNombreSnapshot = ?, EstadoFinal = ?, PromedioFinal = ?, GeneradoPor = NULLIF(?,0), FechaGeneracion = CURRENT_TIMESTAMP WHERE Id = ?')
                ->execute([(int)$Info['GrupoId'], (string)$Info['CicloNombre'], (string)$Info['Grado'], (string)$Info['Grupo'], (string)$Info['Turno'], (string)($Info['OfertaNombre'] ?? ''), (string)($Info['ProgramaNombre'] ?? ''), (string)($Info['EtapaNombre'] ?? $Info['Grado']), (string)$Info['Estado'], $PromedioFinal, $UsuarioId, $KardexId]);
            $Pdo->prepare('DELETE FROM KardexDetalle WHERE KardexId = ?')->execute([$KardexId]);
        } else {
            $Pdo->prepare('INSERT INTO KardexAlumno (AlumnoId, CicloId, GrupoId, CicloNombreSnapshot, GradoSnapshot, GrupoSnapshot, TurnoSnapshot, OfertaNombreSnapshot, ProgramaNombreSnapshot, EtapaNombreSnapshot, EstadoFinal, PromedioFinal, GeneradoPor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?,0))')
                ->execute([$AlumnoId, $CicloId, (int)$Info['GrupoId'], (string)$Info['CicloNombre'], (string)$Info['Grado'], (string)$Info['Grupo'], (string)$Info['Turno'], (string)($Info['OfertaNombre'] ?? ''), (string)($Info['ProgramaNombre'] ?? ''), (string)($Info['EtapaNombre'] ?? $Info['Grado']), (string)$Info['Estado'], $PromedioFinal, $UsuarioId]);
            $KardexId = (int)$Pdo->lastInsertId();
        }
        $StmtInsDet = $Pdo->prepare('INSERT INTO KardexDetalle (KardexId, MateriaNombreSnapshot, MaestroNombreSnapshot, CalificacionesJson, Promedio, Orden) VALUES (?, ?, ?, ?, ?, ?)');
        $Orden = 1;
        foreach ($Detalles as $D) {
            $JsonPeriodos = json_encode($D['Periodos'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $StmtInsDet->execute([
                $KardexId,
                (string)$D['MateriaNombre'],
                (string)($D['MaestroNombre'] ?? ''),
                $JsonPeriodos !== false ? $JsonPeriodos : '[]',
                $D['Promedio'] !== null ? (float)$D['Promedio'] : null,
                $Orden++,
            ]);
        }
        if ($TransaccionPropia) { $Pdo->commit(); }
        return true;
    } catch (Throwable $E) {
        if ($TransaccionPropia && $Pdo->inTransaction()) { $Pdo->rollBack(); }
        throw $E;
    }
}

