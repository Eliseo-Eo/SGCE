<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }



require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/services/ConductaService.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'reportes', 'No tienes permiso para consultar expedientes de alumnos.');

$AlumnoId = intval($_GET['AlumnoId'] ?? 0);
$PeriodoId = SgcePeriodoActualId($Pdo, $_GET['PeriodoId'] ?? 0);
$PeriodoInfo = SgcePeriodoInfo($Pdo, $PeriodoId);
if (!$PeriodoInfo) {
    $CicloActivoSinPeriodo = SgceCicloActivo($Pdo);
    http_response_code(400);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
<?= SgceLayoutHeadBase('SGCE | Periodos no configurados', $Pdo) ?>
</head>
    <body>
    <div class="SgcePageWrap SgceModuleWrap container-fluid px-4 py-5">
        <section class="SgceHero mb-4">
            <div class="SgceHeroInfo">
                <div class="SgceHeroIcon"><span class="SgceColorIcon" aria-hidden="true">📅</span></div>
                <div>
                    <h1>PERIODOS NO CONFIGURADOS</h1>
                    <p>El ciclo activo no tiene periodos de evaluación disponibles.</p>
                </div>
            </div>
            <div class="SgceHeroActions">
                <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
            </div>
        </section>
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h2 class="h5 fw-bold text-danger">No se puede abrir el expediente todavía</h2>
            <p class="mb-3">El ciclo activo <?= htmlspecialchars((string)($CicloActivoSinPeriodo['Nombre'] ?? 'actual'), ENT_QUOTES, 'UTF-8') ?> no tiene periodos configurados. Registra o copia los periodos antes de consultar calificaciones, boletas o expedientes.</p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-success rounded-pill fw-bold px-4" href="PeriodosAdmin.php"><i class="fa-solid fa-calendar-days me-2"></i>Ir a ciclos y periodos</a>
                <a class="btn btn-outline-secondary rounded-pill fw-bold px-4" href="MigracionAdmin.php"><i class="fa-solid fa-rotate me-2"></i>Ir a migración</a>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}
$FechaInicioCiclo = $PeriodoInfo['FechaInicio'];
$FechaFinCiclo = $PeriodoInfo['FechaFin'];
$PeriodosDisponibles = SgcePeriodosDisponibles($Pdo);
if ($AlumnoId <= 0) {
    SgceSalirConError('Alumno inválido.', 400);
}

function H($Texto) {
    return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8');
}

$CicloConsultaId = (int)$PeriodoInfo['CicloId'];

$StmtAlumno = $Pdo->prepare("\n    SELECT Al.Id, Al.NombreCompleto, AI.CicloId, AI.GrupoId, AI.Estado AS EstadoInscripcion,\n           G.Grado, G.Grupo, G.Turno, G.OfertaId, G.ProgramaId, C.Nombre AS CicloNombre\n    FROM AlumnoInscripciones AI\n    INNER JOIN Alumnos Al ON Al.Id = AI.AlumnoId AND Al.Activo = 1\n    INNER JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId\n    INNER JOIN CiclosEscolares C ON C.Id = AI.CicloId\n    WHERE AI.AlumnoId = ?\n      AND AI.CicloId = ?\n    LIMIT 1\n");
$StmtAlumno->execute([$AlumnoId, $CicloConsultaId]);
$Alumno = $StmtAlumno->fetch();

if (!$Alumno) {
    SgceSalirConError('El alumno no tiene inscripción registrada en el ciclo escolar seleccionado.', 404);
}

$TablaAsistenciaHistorial = SgceAsistenciaTablaParaCiclo($Pdo, $CicloConsultaId);
$StmtResumenAsis = $Pdo->prepare("\n    SELECT Asis.Estado, COUNT(*) AS Total\n    FROM {$TablaAsistenciaHistorial} Asis\n    INNER JOIN Asignaciones Asg ON Asg.Id = Asis.AsignacionId AND Asg.CicloId = Asis.CicloId\n    WHERE Asis.AlumnoId = ?\n      AND Asis.CicloId = ?\n      AND Asg.GrupoId = ?\n      AND Asis.FechaDia BETWEEN ? AND ?\n    GROUP BY Asis.Estado\n");
$StmtResumenAsis->execute([$AlumnoId, $CicloConsultaId, (int)$Alumno['GrupoId'], $FechaInicioCiclo, $FechaFinCiclo]);
$Conteos = ['A'=>0,'F'=>0,'R'=>0,'J'=>0];
foreach ($StmtResumenAsis->fetchAll() as $Fila) {
    if (isset($Conteos[$Fila['Estado']])) {
        $Conteos[$Fila['Estado']] = (int)$Fila['Total'];
    }
}

$StmtPromedio = $Pdo->prepare("
    SELECT C.Calificacion
    FROM Calificaciones C
    INNER JOIN PeriodosEvaluacion P ON P.Id = C.PeriodoId AND P.OfertaId = ?
    INNER JOIN Asignaciones Asg ON Asg.Id = C.AsignacionId AND Asg.CicloId = P.CicloId
    WHERE C.AlumnoId = ?
      AND P.CicloId = ?
      AND Asg.GrupoId = ?
");
$StmtPromedio->execute([(int)($Alumno['OfertaId'] ?? 0), $AlumnoId, $CicloConsultaId, (int)$Alumno['GrupoId']]);
$PromedioValor = SgcePromedioAcademico($StmtPromedio->fetchAll(PDO::FETCH_COLUMN), 1);
$Promedio = $PromedioValor !== null ? number_format($PromedioValor, 1) : '0.0';

$StmtCalificaciones = $Pdo->prepare("\n    SELECT Asg.MateriaNombre, U.NombreCompleto AS Maestro, P.Nombre AS PeriodoNombre, C.Calificacion, C.FechaActualizacion\n    FROM Calificaciones C\n    JOIN PeriodosEvaluacion P ON P.Id = C.PeriodoId AND P.OfertaId = ?\n    JOIN Asignaciones Asg ON C.AsignacionId = Asg.Id AND Asg.CicloId = P.CicloId\n    JOIN Usuarios U ON Asg.MaestroId = U.Id\n    WHERE C.AlumnoId = ? AND P.CicloId = ? AND Asg.GrupoId = ?\n    ORDER BY P.Orden ASC, Asg.MateriaNombre ASC\n");
$StmtCalificaciones->execute([(int)($Alumno['OfertaId'] ?? 0), $AlumnoId, $CicloConsultaId, (int)$Alumno['GrupoId']]);
$Calificaciones = $StmtCalificaciones->fetchAll();

$StmtAsistencias = $Pdo->prepare("\n    SELECT Asis.FechaDia, DATE_FORMAT(Asis.FechaDia, '%d/%m/%Y') AS FechaTexto, Asg.MateriaNombre, U.NombreCompleto AS Maestro, Asis.Estado\n    FROM {$TablaAsistenciaHistorial} Asis\n    JOIN Asignaciones Asg ON Asis.AsignacionId = Asg.Id AND Asg.CicloId = Asis.CicloId\n    JOIN Usuarios U ON Asg.MaestroId = U.Id\n    WHERE Asis.AlumnoId = ?\n      AND Asis.CicloId = ?\n      AND Asg.GrupoId = ?\n      AND Asis.FechaDia BETWEEN ? AND ?\n    ORDER BY Asis.FechaDia DESC, Asg.MateriaNombre ASC\n    LIMIT 300\n");
$StmtAsistencias->execute([$AlumnoId, $CicloConsultaId, (int)$Alumno['GrupoId'], $FechaInicioCiclo, $FechaFinCiclo]);
$Asistencias = $StmtAsistencias->fetchAll();

$ResumenConductaAlumno = SgceConductaResumenAlumno($Pdo, $AlumnoId, $CicloConsultaId);
$ConductaHistorial = SgceConductaHistorialAlumno($Pdo, $AlumnoId, $CicloConsultaId, 80, false);

function TextoEstado($Estado) {
    switch ($Estado) {
        case 'A': return 'ASISTENCIA';
        case 'F': return 'FALTA';
        case 'R': return 'RETARDO';
        case 'J': return 'JUSTIFICANTE';
        default: return 'SIN REGISTRO';
    }
}

RegistrarBitacora($Pdo, $UserSession, 'CONSULTAR_EXPEDIENTE', 'Alumnos', $AlumnoId, 'EXPEDIENTE INDIVIDUAL CONSULTADO');

?>
<!DOCTYPE html>
<html lang="es">
<head>
<?= SgceLayoutHeadBase('SGCE | Expediente del alumno', $Pdo, ['assets/css/expediente-alumno.css']) ?>
</head>
<body class="ExpedienteAlumnoBody">
<div class="SgcePageWrap SgceModuleWrap ExpedienteAlumnoPage">
    <header class="Top ExpedienteAlumnoHero">
        <div class="SgceHeroInfo">
            <div class="IconBox"><span class="SgceColorIcon" aria-hidden="true">📁</span></div>
            <div class="ExpedienteAlumnoTitleBlock">
                <h2>EXPEDIENTE DEL ALUMNO</h2>
                <p><?= H($Alumno['NombreCompleto']) ?> · <?= H($Alumno['Grado'].' '.$Alumno['Grupo'].' '.$Alumno['Turno']) ?> · <?= H($PeriodoInfo['CicloNombre']) ?></p>
            </div>
        </div>
        <div class="SgceHeroActions ExpedienteAlumnoActions">
            <a href="ExportarAlumno.php?AlumnoId=<?= (int)$AlumnoId ?>&PeriodoId=<?= (int)$PeriodoId ?>" target="_blank" rel="noopener noreferrer" class="BtnBack BtnExpedientePdf">
                <i class="fa-solid fa-file-pdf"></i>
                <span>Boleta PDF</span>
            </a>
            <a href="ExportarHistorialAlumno.php?AlumnoId=<?= (int)$AlumnoId ?>" target="_blank" rel="noopener noreferrer" class="BtnBack BtnExpedientePdf">
                <i class="fa-solid fa-scroll"></i>
                <span>Historial PDF</span>
            </a>
            <a href="ExportarConductaAlumno.php?AlumnoId=<?= (int)$AlumnoId ?>&CicloId=<?= (int)$CicloConsultaId ?>" target="_blank" rel="noopener noreferrer" class="BtnBack BtnExpedientePdf">
                <i class="fa-solid fa-compass"></i>
                <span>Conducta PDF</span>
            </a>
            <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
        </div>
    </header>

    <section class="Card ExpedienteAlumnoFilterCard">
        <form method="get" class="ExpedienteAlumnoFilterForm">
            <input type="hidden" name="AlumnoId" value="<?= (int)$AlumnoId ?>">
            <div class="ExpedienteAlumnoFilterField">
                <label>Ciclo y parcial</label>
                <select name="PeriodoId" class="form-select">
                    <?php foreach($PeriodosDisponibles as $P): ?>
                        <option value="<?= (int)$P['Id'] ?>" <?= (int)$P['Id']===(int)$PeriodoId?'selected':'' ?>><?= H($P['CicloNombre'].' - '.$P['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="BtnPrimary BtnExpedienteFilter" type="submit">
                <i class="fa-solid fa-filter"></i>
                <span>Aplicar filtro</span>
            </button>
        </form>
    </section>

    <section class="ExpedienteMetricGrid" aria-label="Resumen del alumno">
        <article class="ExpedienteMetricCard MetricPromedio">
            <span class="MetricIcon"><span class="SgceColorIcon" aria-hidden="true">📈</span></span>
            <small>Promedio</small>
            <strong><?= H($Promedio) ?></strong>
        </article>
        <article class="ExpedienteMetricCard MetricAsistencias">
            <span class="MetricIcon"><span class="SgceColorIcon" aria-hidden="true">✅</span></span>
            <small>Asistencias</small>
            <strong><?= $Conteos['A'] ?></strong>
        </article>
        <article class="ExpedienteMetricCard MetricFaltas">
            <span class="MetricIcon"><span class="SgceColorIcon" aria-hidden="true">❌</span></span>
            <small>Faltas</small>
            <strong><?= $Conteos['F'] ?></strong>
        </article>
        <article class="ExpedienteMetricCard MetricRetardos">
            <span class="MetricIcon"><span class="SgceColorIcon" aria-hidden="true">⏱️</span></span>
            <small>Retardos</small>
            <strong><?= $Conteos['R'] ?></strong>
        </article>
        <article class="ExpedienteMetricCard MetricJustificantes">
            <span class="MetricIcon"><span class="SgceColorIcon" aria-hidden="true">📄</span></span>
            <small>Justificantes</small>
            <strong><?= $Conteos['J'] ?></strong>
        </article>
        <article class="ExpedienteMetricCard MetricConducta">
            <span class="MetricIcon"><span class="SgceColorIcon" aria-hidden="true">🧭</span></span>
            <small>Reportes conducta</small>
            <strong><?= (int)$ResumenConductaAlumno['Total'] ?></strong>
        </article>
        <article class="ExpedienteMetricCard MetricConductaGrave">
            <span class="MetricIcon"><span class="SgceColorIcon" aria-hidden="true">🚨</span></span>
            <small>Reportes graves</small>
            <strong><?= (int)$ResumenConductaAlumno['Graves'] ?></strong>
        </article>
    </section>

    <section class="ExpedienteAlumnoContentGrid">
        <article class="Card ExpedienteAlumnoPanel ExpedienteCalificacionesPanel">
            <div class="ExpedientePanelHeader">
                <div>
                    <span class="PanelKicker"><i class="fa-solid fa-star"></i> Evaluación</span>
                    <h3>Calificaciones</h3>
                </div>
                <span class="PanelCount"><?= count($Calificaciones) ?> registros</span>
            </div>
            <div class="table-responsive ExpedienteTableWrap">
                <table class="table align-middle ExpedienteAlumnoTable">
                    <thead>
                        <tr>
                            <th>Parcial</th>
                            <th>Materia</th>
                            <th>Docente</th>
                            <th>Calificación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($Calificaciones as $C): ?>
                            <tr>
                                <td><?= H($C['PeriodoNombre']) ?></td>
                                <td><?= H($C['MateriaNombre']) ?></td>
                                <td><?= H($C['Maestro']) ?></td>
                                <td><span class="ExpedienteGradeBadge"><?= H(number_format((float)$C['Calificacion'],2)) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($Calificaciones)): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="ExpedienteEmptyState">
                                        <i class="fa-solid fa-circle-info"></i>
                                        <span>Sin calificaciones capturadas en el ciclo seleccionado.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="Card ExpedienteAlumnoPanel ExpedienteAsistenciasPanel">
            <div class="ExpedientePanelHeader">
                <div>
                    <span class="PanelKicker"><i class="fa-solid fa-calendar-check"></i> Seguimiento</span>
                    <h3>Últimas asistencias</h3>
                </div>
                <span class="PanelCount">Hasta 300</span>
            </div>
            <div class="table-responsive ExpedienteTableWrap">
                <table class="table align-middle ExpedienteAlumnoTable">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Materia</th>
                            <th>Docente</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($Asistencias as $A): ?>
                            <tr>
                                <td><?= H($A['FechaTexto']) ?></td>
                                <td><?= H($A['MateriaNombre']) ?></td>
                                <td><?= H($A['Maestro']) ?></td>
                                <td><span class="ExpedienteEstadoBadge Estado<?= H($A['Estado']) ?>"><?= TextoEstado($A['Estado']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($Asistencias)): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="ExpedienteEmptyState">
                                        <i class="fa-solid fa-circle-info"></i>
                                        <span>Sin asistencias capturadas en el ciclo seleccionado.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="ExpedienteFootnote">Se muestran registros recientes del ciclo escolar seleccionado.</p>
        </article>

        <article class="Card ExpedienteAlumnoPanel ExpedienteConductaPanel">
            <div class="ExpedientePanelHeader">
                <div>
                    <span class="PanelKicker"><i class="fa-solid fa-compass"></i> Conducta</span>
                    <h3>Conducta y disciplina</h3>
                </div>
                <span class="PanelCount"><?= count($ConductaHistorial) ?> reportes</span>
            </div>
            <div class="table-responsive ExpedienteTableWrap">
                <table class="table align-middle ExpedienteAlumnoTable">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Materia/Área</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($ConductaHistorial as $R): ?>
                            <tr>
                                <td><?= H($R['FechaTexto']) ?></td>
                                <td><span class="badge bg-<?= H(SgceConductaClaseSeveridad((string)$R['Severidad'])) ?>"><?= H(SgceConductaTextoTipo((string)$R['Tipo'])) ?></span></td>
                                <td><?= H($R['MateriaNombre'] ?: $R['Origen']) ?></td>
                                <td><strong><?= H($R['MotivoCorto']) ?></strong><br><small class="text-muted"><?= H($R['AccionTomada'] ?? '') ?></small></td>
                                <td><span class="badge bg-<?= H(SgceConductaClaseEstado((string)$R['Estado'])) ?>"><?= H(SgceConductaTextoEstado((string)$R['Estado'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($ConductaHistorial)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="ExpedienteEmptyState">
                                        <i class="fa-solid fa-circle-info"></i>
                                        <span>Sin reportes de conducta en el ciclo seleccionado.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="ExpedienteFootnote">Los reportes internos pueden requerir revisión antes de mostrarse al padre o tutor.</p>
        </article>
    </section>
</div>
<?= SgceLayoutSharedJs() ?>
</body>
</html>
