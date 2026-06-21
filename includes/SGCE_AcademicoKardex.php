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
    $TodasCalificaciones = [];
    foreach ($Agrupados as $D) {
        $CalificacionesMateria = array_column($D['Periodos'], 'Calificacion');
        $PromedioMateria = SgcePromedioAcademico($CalificacionesMateria, 2);
        if ($PromedioMateria === null) { continue; }
        foreach ($CalificacionesMateria as $CalificacionMateria) {
            if (\Sgce\Support\AcademicCalculator::normalizeScore($CalificacionMateria) !== null) { $TodasCalificaciones[] = $CalificacionMateria; }
        }
        $D['Promedio'] = $PromedioMateria;
        $Detalles[] = $D;
    }
    $PromedioFinal = SgcePromedioAcademico($TodasCalificaciones, 2);

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


function SgceKardexReporteFormatoCalificacion($Valor): string {
    return $Valor !== null && $Valor !== '' ? number_format((float)$Valor, 2) : 'NC';
}

function SgceKardexReporteNombreArchivo(string $Texto): string {
    $TextoAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $Texto);
    $TextoAscii = $TextoAscii !== false ? $TextoAscii : $Texto;
    $TextoAscii = preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string)$TextoAscii);
    return trim((string)$TextoAscii, '_') ?: 'Kardex';
}

function SgceKardexAlumnoReporteDatos(PDO $Pdo, int $AlumnoId, int $CicloId = 0): array {
    if ($AlumnoId <= 0) { throw new InvalidArgumentException('Alumno inválido.'); }

    $StmtAlumno = $Pdo->prepare('SELECT Id, NombreCompleto FROM Alumnos WHERE Id = ? LIMIT 1');
    $StmtAlumno->execute([$AlumnoId]);
    $AlumnoBase = $StmtAlumno->fetch();
    if (!$AlumnoBase) { throw new RuntimeException('Alumno no encontrado.', 404); }

    $WhereCiclo = $CicloId > 0 ? ' AND AI.CicloId = ? ' : '';
    $ParamsCiclos = [$AlumnoId];
    if ($CicloId > 0) { $ParamsCiclos[] = $CicloId; }
    $StmtCiclos = $Pdo->prepare("SELECT AI.CicloId, AI.GrupoId, AI.Estado, C.Nombre AS CicloNombre, C.FechaInicio, G.Grado, G.Grupo, G.Turno, G.OfertaId
        FROM AlumnoInscripciones AI
        JOIN CiclosEscolares C ON C.Id = AI.CicloId
        JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId
        WHERE AI.AlumnoId = ? $WhereCiclo
        ORDER BY C.FechaInicio ASC, C.Id ASC");
    $StmtCiclos->execute($ParamsCiclos);
    $Ciclos = $StmtCiclos->fetchAll();
    if (!$Ciclos) { throw new RuntimeException('El alumno no tiene inscripción en el ciclo solicitado.', 404); }

    $Filas = [];
    $ResumenCiclos = [];
    $PeriodosPorCicloOferta = [];
    $AsignacionesPorCicloGrupo = [];
    $Calificaciones = [];
    $PeriodoIdsTodos = [];
    $AsignacionIdsTodos = [];

    $ClausulasPeriodos = [];
    $ParamsPeriodos = [];
    $ParesPeriodos = [];
    foreach ($Ciclos as $Ciclo) {
        $ClavePeriodo = (int)$Ciclo['CicloId'] . ':' . (int)$Ciclo['OfertaId'];
        if (isset($ParesPeriodos[$ClavePeriodo])) { continue; }
        $ParesPeriodos[$ClavePeriodo] = true;
        $ClausulasPeriodos[] = '(CicloId = ? AND OfertaId = ?)';
        $ParamsPeriodos[] = (int)$Ciclo['CicloId'];
        $ParamsPeriodos[] = (int)$Ciclo['OfertaId'];
    }
    if ($ClausulasPeriodos) {
        $SqlPeriodos = 'SELECT Id, Nombre, Orden, CicloId, OfertaId FROM PeriodosEvaluacion WHERE (' . implode(' OR ', $ClausulasPeriodos) . ') AND Activo = 1 ORDER BY CicloId ASC, OfertaId ASC, Orden ASC, Id ASC';
        $StmtPeriodos = $Pdo->prepare($SqlPeriodos);
        $StmtPeriodos->execute($ParamsPeriodos);
        foreach ($StmtPeriodos->fetchAll() as $Periodo) {
            $ClavePeriodo = (int)$Periodo['CicloId'] . ':' . (int)$Periodo['OfertaId'];
            $PeriodosPorCicloOferta[$ClavePeriodo][] = $Periodo;
            $PeriodoIdsTodos[] = (int)$Periodo['Id'];
        }
    }
    $PeriodoIdsTodos = array_values(array_unique($PeriodoIdsTodos));

    $ClausulasAsignaciones = [];
    $ParamsAsignaciones = [];
    $ParesAsignaciones = [];
    foreach ($Ciclos as $Ciclo) {
        $ClaveAsignacion = (int)$Ciclo['CicloId'] . ':' . (int)$Ciclo['GrupoId'];
        if (isset($ParesAsignaciones[$ClaveAsignacion])) { continue; }
        $ParesAsignaciones[$ClaveAsignacion] = true;
        $ClausulasAsignaciones[] = '(A.CicloId = ? AND A.GrupoId = ?)';
        $ParamsAsignaciones[] = (int)$Ciclo['CicloId'];
        $ParamsAsignaciones[] = (int)$Ciclo['GrupoId'];
    }
    if ($ClausulasAsignaciones) {
        $SqlAsignaciones = 'SELECT A.Id, A.CicloId, A.GrupoId, A.MateriaNombre, U.NombreCompleto AS Maestro FROM Asignaciones A LEFT JOIN Usuarios U ON U.Id = A.MaestroId WHERE ' . implode(' OR ', $ClausulasAsignaciones) . ' ORDER BY A.CicloId ASC, A.GrupoId ASC, A.MateriaNombre ASC';
        $StmtAsignaciones = $Pdo->prepare($SqlAsignaciones);
        $StmtAsignaciones->execute($ParamsAsignaciones);
        foreach ($StmtAsignaciones->fetchAll() as $Asignacion) {
            $ClaveAsignacion = (int)$Asignacion['CicloId'] . ':' . (int)$Asignacion['GrupoId'];
            $AsignacionesPorCicloGrupo[$ClaveAsignacion][] = $Asignacion;
            $AsignacionIdsTodos[] = (int)$Asignacion['Id'];
        }
    }
    $AsignacionIdsTodos = array_values(array_unique($AsignacionIdsTodos));

    if ($PeriodoIdsTodos && $AsignacionIdsTodos) {
        $MarcadoresPeriodos = implode(',', array_fill(0, count($PeriodoIdsTodos), '?'));
        $MarcadoresAsignaciones = implode(',', array_fill(0, count($AsignacionIdsTodos), '?'));
        $StmtCal = $Pdo->prepare("SELECT AsignacionId, PeriodoId, Calificacion FROM Calificaciones WHERE AlumnoId = ? AND AsignacionId IN ($MarcadoresAsignaciones) AND PeriodoId IN ($MarcadoresPeriodos)");
        $StmtCal->execute(array_merge([$AlumnoId], $AsignacionIdsTodos, $PeriodoIdsTodos));
        foreach ($StmtCal->fetchAll() as $Cal) { $Calificaciones[(int)$Cal['AsignacionId']][(int)$Cal['PeriodoId']] = $Cal['Calificacion']; }
    }

    foreach ($Ciclos as $Ciclo) {
        $ClavePeriodo = (int)$Ciclo['CicloId'] . ':' . (int)$Ciclo['OfertaId'];
        $ClaveAsignacion = (int)$Ciclo['CicloId'] . ':' . (int)$Ciclo['GrupoId'];
        $Periodos = $PeriodosPorCicloOferta[$ClavePeriodo] ?? [];
        $Asignaciones = $AsignacionesPorCicloGrupo[$ClaveAsignacion] ?? [];

        $ValoresCiclo = [];
        $MateriasConCal = 0;
        $GrupoTexto = trim((string)$Ciclo['Grado'] . ' ' . (string)$Ciclo['Grupo'] . ' ' . (string)$Ciclo['Turno']);
        foreach ($Asignaciones as $Asig) {
            $Partes = [];
            $ValoresMateria = [];
            foreach ($Periodos as $Periodo) {
                $Valor = $Calificaciones[(int)$Asig['Id']][(int)$Periodo['Id']] ?? null;
                if ($Valor !== null && $Valor !== '') { $ValoresMateria[] = $Valor; $ValoresCiclo[] = $Valor; }
                $Partes[] = (string)$Periodo['Nombre'] . ': ' . SgceKardexReporteFormatoCalificacion($Valor);
            }
            $PromedioMateria = SgcePromedioAcademico($ValoresMateria, 2);
            if ($PromedioMateria !== null) { $MateriasConCal++; }
            $Filas[] = [
                (string)$Ciclo['CicloNombre'],
                $GrupoTexto,
                (string)$Asig['MateriaNombre'],
                (string)($Asig['Maestro'] ?? ''),
                implode(' | ', $Partes),
                SgceFormatoPromedioAcademico($PromedioMateria, 2, 'NC'),
            ];
        }
        if (!$Asignaciones) {
            $Filas[] = [(string)$Ciclo['CicloNombre'], $GrupoTexto, 'SIN MATERIAS', '', 'SIN CALIFICACIONES', 'NC'];
        }
        $ResumenCiclos[] = (string)$Ciclo['CicloNombre'] . ' ' . $GrupoTexto . ' PROMEDIO ' . SgceFormatoPromedioAcademico(SgcePromedioAcademico($ValoresCiclo, 2), 2, 'NC') . ' (' . $MateriasConCal . ' MATERIAS CON CALIFICACIÓN)';
    }

    $TituloArchivo = 'Kardex_Individual_' . SgceKardexReporteNombreArchivo((string)$AlumnoBase['NombreCompleto'] . ($CicloId > 0 ? '_' . (string)$Ciclos[0]['CicloNombre'] : '_Completo'));
    $Subtitulo = 'Alumno: ' . (string)$AlumnoBase['NombreCompleto'] . ' | ' . implode(' | ', $ResumenCiclos);
    if (strlen($Subtitulo) > 430) { $Subtitulo = 'Alumno: ' . (string)$AlumnoBase['NombreCompleto'] . ' | Ciclos incluidos: ' . count($Ciclos); }

    return [
        'AlumnoBase' => $AlumnoBase,
        'Ciclos' => $Ciclos,
        'Filas' => $Filas,
        'ResumenCiclos' => $ResumenCiclos,
        'TituloArchivo' => $TituloArchivo,
        'Subtitulo' => $Subtitulo,
    ];
}
