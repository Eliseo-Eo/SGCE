<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }



require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'reportes', 'No tienes permiso para consultar expedientes de alumnos.');

$AlumnoId = intval($_GET['AlumnoId'] ?? 0);
$PeriodoId = SgcePeriodoActualId($Pdo, $_GET['PeriodoId'] ?? 0);
$PeriodoInfo = SgcePeriodoInfo($Pdo, $PeriodoId);
if (!$PeriodoInfo) { die('Periodo inválido.'); }
$FechaInicioCiclo = $PeriodoInfo['FechaInicio'];
$FechaFinCiclo = $PeriodoInfo['FechaFin'];
$PeriodosDisponibles = SgcePeriodosDisponibles($Pdo);
if ($AlumnoId <= 0) {
    die('Alumno inválido.');
}

function H($Texto) {
    return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8');
}

$StmtAlumno = $Pdo->prepare("\n    SELECT Al.Id, Al.NombreCompleto, G.Grado, G.Grupo, G.Turno\n    FROM Alumnos Al\n    LEFT JOIN Grupos G ON Al.GrupoId = G.Id\n    WHERE Al.Id = ?\n    AND Al.Activo = 1\n    LIMIT 1\n");
$StmtAlumno->execute([$AlumnoId]);
$Alumno = $StmtAlumno->fetch();

if (!$Alumno) {
    die('Alumno no encontrado o dado de baja.');
}

$StmtResumenAsis = $Pdo->prepare("\n    SELECT Estado, COUNT(*) AS Total\n    FROM Asistencias\n    WHERE AlumnoId = ? AND FechaDia BETWEEN ? AND ?\n    GROUP BY Estado\n");
$StmtResumenAsis->execute([$AlumnoId, $FechaInicioCiclo, $FechaFinCiclo]);
$Conteos = ['A'=>0,'F'=>0,'R'=>0,'J'=>0];
foreach ($StmtResumenAsis->fetchAll() as $Fila) {
    if (isset($Conteos[$Fila['Estado']])) {
        $Conteos[$Fila['Estado']] = (int)$Fila['Total'];
    }
}

$StmtPromedio = $Pdo->prepare("SELECT ROUND(AVG(C.Calificacion), 1) FROM Calificaciones C INNER JOIN PeriodosEvaluacion P ON P.Id = C.PeriodoId WHERE C.AlumnoId = ? AND P.CicloId = ? AND P.Orden BETWEEN 1 AND 3");
$StmtPromedio->execute([$AlumnoId, (int)$PeriodoInfo['CicloId']]);
$Promedio = $StmtPromedio->fetchColumn();
$Promedio = $Promedio !== null ? $Promedio : '0.0';

$StmtCalificaciones = $Pdo->prepare("\n    SELECT Asg.MateriaNombre, U.NombreCompleto AS Maestro, P.Nombre AS PeriodoNombre, C.Calificacion, C.FechaActualizacion\n    FROM Calificaciones C\n    JOIN PeriodosEvaluacion P ON P.Id = C.PeriodoId\n    JOIN Asignaciones Asg ON C.AsignacionId = Asg.Id\n    JOIN Usuarios U ON Asg.MaestroId = U.Id\n    WHERE C.AlumnoId = ? AND P.CicloId = ? AND P.Orden BETWEEN 1 AND 3\n    ORDER BY P.Orden ASC, Asg.MateriaNombre ASC\n");
$StmtCalificaciones->execute([$AlumnoId, (int)$PeriodoInfo['CicloId']]);
$Calificaciones = $StmtCalificaciones->fetchAll();

$StmtAsistencias = $Pdo->prepare("\n    SELECT Asis.FechaDia, DATE_FORMAT(Asis.FechaDia, '%d/%m/%Y') AS FechaTexto, Asg.MateriaNombre, U.NombreCompleto AS Maestro, Asis.Estado\n    FROM Asistencias Asis\n    JOIN Asignaciones Asg ON Asis.AsignacionId = Asg.Id\n    JOIN Usuarios U ON Asg.MaestroId = U.Id\n    WHERE Asis.AlumnoId = ? AND Asis.FechaDia BETWEEN ? AND ?\n    ORDER BY Asis.FechaDia DESC, Asg.MateriaNombre ASC\n    LIMIT 300\n");
$StmtAsistencias->execute([$AlumnoId, $FechaInicioCiclo, $FechaFinCiclo]);
$Asistencias = $StmtAsistencias->fetchAll();

function TextoEstado($Estado) {
    switch ($Estado) {
        case 'A': return 'ASISTENCIA';
        case 'F': return 'FALTA';
        case 'R': return 'RETARDO';
        case 'J': return 'JUSTIFICANTE';
        default: return 'SIN REGISTRO';
    }
}

function ClaseEstado($Estado) {
    switch ($Estado) {
        case 'A': return 'success';
        case 'F': return 'danger';
        case 'R': return 'warning text-dark';
        case 'J': return 'primary';
        default: return 'secondary';
    }
}

RegistrarBitacora($Pdo, $UserSession, 'CONSULTAR_EXPEDIENTE', 'Alumnos', $AlumnoId, 'EXPEDIENTE INDIVIDUAL CONSULTADO');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGCE | Expediente Del Alumno</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.min.css?cache=sgce2026">
<?= SgceEstilosTema($Pdo) ?>
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
            <a href="ExportarAlumno.php?AlumnoId=<?= (int)$AlumnoId ?>&PeriodoId=<?= (int)$PeriodoId ?>" target="_blank" rel="noopener noreferrer" class="BtnBack BtnBoletaPdf">
                <i class="fa-solid fa-file-pdf"></i>
                <span>BOLETA PDF</span>
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
    </section>
</div>
<script src="assets/js/sgce-shared.js?cache=sgce2026"></script>
</body>
</html>
