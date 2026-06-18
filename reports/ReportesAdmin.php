<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'reportes', 'No tienes permiso para entrar al centro de reportes.');

$CicloActivo = SgceCicloActivo($Pdo) ?: ['Id'=>0,'Nombre'=>'','FechaInicio'=>'','FechaFin'=>''];
$Ciclos = [];
if (SgceDbTablaExiste($Pdo, 'CiclosEscolares')) {
    $StmtCiclos = $Pdo->query("SELECT Id, Nombre, FechaInicio, FechaFin, Activo FROM CiclosEscolares ORDER BY FechaInicio DESC, Id DESC");
    $Ciclos = $StmtCiclos ? $StmtCiclos->fetchAll() : [];
}
$CicloReporteId = (int)($_GET['CicloReporteId'] ?? ($CicloActivo['Id'] ?? 0));
$CicloReporte = null;
foreach ($Ciclos as $CicloFila) {
    if ((int)$CicloFila['Id'] === $CicloReporteId) { $CicloReporte = $CicloFila; break; }
}
if (!$CicloReporte && $Ciclos) {
    foreach ($Ciclos as $CicloFila) {
        if ((int)$CicloFila['Id'] === (int)($CicloActivo['Id'] ?? 0)) { $CicloReporte = $CicloFila; break; }
    }
    if (!$CicloReporte) { $CicloReporte = $Ciclos[0]; }
    $CicloReporteId = (int)$CicloReporte['Id'];
}
if (!$CicloReporte) { $CicloReporte = ['Id'=>0,'Nombre'=>'','FechaInicio'=>date('Y-m-d'),'FechaFin'=>date('Y-m-d'),'Activo'=>0]; }

$ReporteFechaFinDefault = min(date('Y-m-d'), (string)($CicloReporte['FechaFin'] ?: date('Y-m-d')));
$ReporteFechaInicioDefault = max((string)($CicloReporte['FechaInicio'] ?: date('Y-m-d', strtotime('-30 days'))), date('Y-m-d', strtotime($ReporteFechaFinDefault . ' -30 days')));
$Grupos = [];
$Asignaciones = [];
$Periodos = [];
if ($CicloReporteId > 0) {
    $StmtGrupos = $Pdo->prepare("SELECT Id, CicloId, Grado, Grupo, Turno FROM Grupos WHERE CicloId = ? AND Activo = 1 ORDER BY Turno, Grado, Grupo");
    $StmtGrupos->execute([$CicloReporteId]);
    $Grupos = $StmtGrupos->fetchAll();

    $StmtAsignaciones = $Pdo->prepare("SELECT Asg.Id, Asg.CicloId, Asg.MateriaNombre, G.Grado, G.Grupo, G.Turno, U.NombreCompleto AS Maestro FROM Asignaciones Asg JOIN Grupos G ON Asg.GrupoId = G.Id AND G.CicloId = Asg.CicloId JOIN Usuarios U ON Asg.MaestroId = U.Id WHERE Asg.CicloId = ? AND Asg.Activo = 1 AND G.Activo = 1 AND U.Activo = 1 ORDER BY G.Turno, G.Grado, G.Grupo, Asg.MateriaNombre");
    $StmtAsignaciones->execute([$CicloReporteId]);
    $Asignaciones = $StmtAsignaciones->fetchAll();

    $StmtPeriodos = $Pdo->prepare("SELECT P.Id, P.Nombre, P.Orden, P.OfertaId, C.Nombre AS CicloNombre, C.Id AS CicloId FROM PeriodosEvaluacion P INNER JOIN CiclosEscolares C ON C.Id = P.CicloId WHERE P.CicloId = ? AND P.Activo = 1 ORDER BY P.OfertaId ASC, P.Orden ASC, P.Id ASC");
    $StmtPeriodos->execute([$CicloReporteId]);
    $Periodos = $StmtPeriodos->fetchAll();
}

$Alumnos = [];
if ($CicloReporteId > 0) {
    $SqlAlumnos = "SELECT Al.Id, Al.NombreCompleto, AI.Estado, G.Grado, G.Grupo, G.Turno
            FROM AlumnoInscripciones AI
            JOIN Alumnos Al ON Al.Id = AI.AlumnoId
            JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId
            WHERE AI.CicloId = ?
            ORDER BY G.Turno, G.Grado, G.Grupo, Al.NombreCompleto";
    $StmtAlumnos = $Pdo->prepare($SqlAlumnos);
    $StmtAlumnos->execute([$CicloReporteId]);
    $Alumnos = $StmtAlumnos->fetchAll();
}
function HRpt($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function RptOpcionAlumno(array $Al): string {
    $Estado = (string)($Al['Estado'] ?? '');
    $EstadoTxt = $Estado !== '' ? ' - ' . $Estado : '';
    return $Al['NombreCompleto'] . ' - ' . $Al['Grado'] . ' ' . $Al['Grupo'] . ' ' . $Al['Turno'] . $EstadoTxt;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?= SgceLayoutHeadBase('SGCE | Reportes', $Pdo, ['assets/css/components/searchable-selects.css', 'assets/css/reportes-botones-metalicos.css']) ?>
</head>
<body class="SgceReportsPage">
<div class="container py-4 SgceReportsWrap">
    <div class="Top ReportHero mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="ReportHeroTitle">
            <div class="IconBox"><span class="SgceColorIcon" aria-hidden="true">📊</span></div>
            <div>
                <h2 class="fw-bold mb-1">CENTRO DE REPORTES</h2>
                <p class="mb-0">Reportes administrativos por ciclo: asistencia, calificaciones, boletas y kardex.</p>
            </div>
        </div>
        <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
    </div>

    <section class="SgceReportCyclePanel mb-4">
        <div class="SgceReportCycleText">
            <span><i class="fa-solid fa-calendar-days"></i> Ciclo de trabajo</span>
            <strong><?= HRpt($CicloReporte['Nombre'] ?: 'SIN CICLO') ?></strong>
            <small>Los grupos, asignaciones, alumnos y periodos de abajo corresponden al ciclo seleccionado.</small>
        </div>
        <form method="GET" class="SgceReportCycleForm">
            <select name="CicloReporteId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar ciclo..." onchange="this.form.submit()">
                <?php foreach($Ciclos as $C): ?>
                    <option value="<?= (int)$C['Id'] ?>" <?= (int)$C['Id'] === $CicloReporteId ? 'selected' : '' ?>><?= HRpt($C['Nombre'] . ((int)$C['Activo'] === 1 ? ' - ACTIVO' : '')) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </section>

    <div class="SgceReportCards">
        <section class="SgceReportCard AccentRed">
            <div class="SgceReportCardHead"><span class="SgceReportIcon"><span class="SgceColorIcon" aria-hidden="true">📅</span></span><div><h5>Asistencia por grupo</h5><p>Lista de asistencia por grupo y rango de fechas.</p></div></div>
            <form action="ExportarAsistencia.php" method="GET" target="_blank" rel="noopener noreferrer" class="SgceReportForm">
                <input type="hidden" name="Rango" value="Todas"><input type="hidden" name="CicloId" value="<?= (int)$CicloReporteId ?>">
                <div class="SgceFormField Full"><label>Grupo</label><select name="GrupoId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar grupo..." required><option value="">SELECCIONA...</option><?php foreach($Grupos as $G): ?><option value="<?= (int)$G['Id'] ?>"><?= HRpt($G['Grado'].' '.$G['Grupo'].' '.$G['Turno']) ?></option><?php endforeach; ?></select></div>
                <div class="SgceReportTwoCols"><div class="SgceFormField"><label>Fecha inicio</label><input type="date" name="FechaInicio" class="form-control" value="<?= HRpt($ReporteFechaInicioDefault) ?>" required></div><div class="SgceFormField"><label>Fecha fin</label><input type="date" name="FechaFin" class="form-control" value="<?= HRpt($ReporteFechaFinDefault) ?>" required></div></div>
                <div class="SgceFormField Full"><label>Formato</label><select name="Tipo" class="form-select"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select></div>
                <button id="BtnExportarAsistenciaGrupoVerdeMetalico" class="SgceReportBtn BtnReportExport BtnReporteVerdeMetalico" type="submit"><span class="SgceColorIcon" aria-hidden="true">📤</span><span>Exportar asistencia</span></button>
            </form>
        </section>

        <section class="SgceReportCard AccentBlue">
            <div class="SgceReportCardHead"><span class="SgceReportIcon"><span class="SgceColorIcon" aria-hidden="true">✅</span></span><div><h5>Asistencia por asignación</h5><p>Asistencia por materia, docente y grupo.</p></div></div>
            <form action="ExportarAsistencia.php" method="GET" target="_blank" rel="noopener noreferrer" class="SgceReportForm">
                <input type="hidden" name="Rango" value="Todas"><input type="hidden" name="CicloId" value="<?= (int)$CicloReporteId ?>">
                <div class="SgceFormField Full"><label>Asignación</label><select name="AsignacionId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar asignación, materia, docente o grupo..." required><option value="">SELECCIONA...</option><?php foreach($Asignaciones as $A): ?><option value="<?= (int)$A['Id'] ?>"><?= HRpt($A['Grado'].' '.$A['Grupo'].' '.$A['Turno'].' - '.$A['MateriaNombre'].' - '.$A['Maestro']) ?></option><?php endforeach; ?></select></div>
                <div class="SgceReportTwoCols"><div class="SgceFormField"><label>Fecha inicio</label><input type="date" name="FechaInicio" class="form-control" value="<?= HRpt($ReporteFechaInicioDefault) ?>" required></div><div class="SgceFormField"><label>Fecha fin</label><input type="date" name="FechaFin" class="form-control" value="<?= HRpt($ReporteFechaFinDefault) ?>" required></div></div>
                <div class="SgceFormField Full"><label>Formato</label><select name="Tipo" class="form-select"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select></div>
                <button id="BtnExportarAsistenciaAsignacionVerdeMetalico" class="SgceReportBtn BtnReportExport BtnReporteVerdeMetalico" type="submit"><span class="SgceColorIcon" aria-hidden="true">📤</span><span>Exportar asistencia</span></button>
            </form>
        </section>

        <section class="SgceReportCard AccentCyan">
            <div class="SgceReportCardHead"><span class="SgceReportIcon"><span class="SgceColorIcon" aria-hidden="true">👤</span></span><div><h5>Asistencia individual</h5><p>Historial de asistencia de un alumno en el ciclo seleccionado.</p></div></div>
            <form action="ExportarAsistenciaIndividual.php" method="GET" target="_blank" rel="noopener noreferrer" class="SgceReportForm">
                <input type="hidden" name="CicloId" value="<?= (int)$CicloReporteId ?>">
                <div class="SgceFormField Full"><label>Alumno</label><select name="AlumnoId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Escribe el nombre del alumno..." required><option value="">BUSCA Y SELECCIONA...</option><?php foreach($Alumnos as $Al): ?><option value="<?= (int)$Al['Id'] ?>"><?= HRpt(RptOpcionAlumno($Al)) ?></option><?php endforeach; ?></select></div>
                <div class="SgceReportTwoCols"><div class="SgceFormField"><label>Fecha inicio</label><input type="date" name="FechaInicio" class="form-control" value="<?= HRpt($ReporteFechaInicioDefault) ?>" required></div><div class="SgceFormField"><label>Fecha fin</label><input type="date" name="FechaFin" class="form-control" value="<?= HRpt($ReporteFechaFinDefault) ?>" required></div></div>
                <div class="SgceFormField Full"><label>Formato</label><select name="Tipo" class="form-select"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select></div>
                <button id="BtnExportarAsistenciaIndividualVerdeMetalico" class="SgceReportBtn BtnReportExport BtnReporteVerdeMetalico" type="submit"><span class="SgceColorIcon" aria-hidden="true">📤</span><span>Exportar individual</span></button>
            </form>
        </section>

        <section class="SgceReportCard AccentGreen">
            <div class="SgceReportCardHead"><span class="SgceReportIcon"><span class="SgceColorIcon" aria-hidden="true">⭐</span></span><div><h5>Calificaciones por periodo</h5><p>Reporte por periodo, grupo, asignación o resumen general.</p></div></div>
            <form action="ExportarCalificaciones.php" method="GET" target="_blank" rel="noopener noreferrer" class="SgceReportForm">
                <div class="SgceFormField Full"><label>Periodo</label><select name="PeriodoId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar periodo..." required><?php foreach($Periodos as $P): ?><option value="<?= (int)$P['Id'] ?>"><?= HRpt($P['CicloNombre'].' - '.$P['Nombre']) ?></option><?php endforeach; ?></select></div>
                <div class="SgceFormField Full"><label>Grupo</label><select name="GrupoId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar grupo..."><option value="">GENERAL / TODOS</option><?php foreach($Grupos as $G): ?><option value="<?= (int)$G['Id'] ?>"><?= HRpt($G['Grado'].' '.$G['Grupo'].' '.$G['Turno']) ?></option><?php endforeach; ?></select></div>
                <div class="SgceFormField Full"><label>Asignación</label><select name="AsignacionId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar asignación o materia..."><option value="">GENERAL / TODAS</option><?php foreach($Asignaciones as $A): ?><option value="<?= (int)$A['Id'] ?>"><?= HRpt($A['MateriaNombre'].' - '.$A['Grado'].' '.$A['Grupo']) ?></option><?php endforeach; ?></select></div>
                <div class="SgceFormField Full"><label>Formato</label><select name="Tipo" class="form-select"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select></div>
                <button id="BtnExportarCalificacionesVerdeMetalico" class="SgceReportBtn BtnReportExport BtnReporteVerdeMetalico" type="submit"><span class="SgceColorIcon" aria-hidden="true">⭐</span><span>Exportar calificaciones</span></button>
            </form>
        </section>

        <section class="SgceReportCard AccentOrange">
            <div class="SgceReportCardHead"><span class="SgceReportIcon"><span class="SgceColorIcon" aria-hidden="true">📄</span></span><div><h5>Boleta individual</h5><p>Boleta de un alumno por periodo del ciclo seleccionado.</p></div></div>
            <form action="ExportarAlumno.php" method="GET" target="_blank" rel="noopener noreferrer" class="SgceReportForm">
                <div class="SgceFormField Full"><label>Periodo</label><select name="PeriodoId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar periodo..." required><?php foreach($Periodos as $P): ?><option value="<?= (int)$P['Id'] ?>"><?= HRpt($P['CicloNombre'].' - '.$P['Nombre']) ?></option><?php endforeach; ?></select></div>
                <div class="SgceFormField Full"><label>Alumno</label><select name="AlumnoId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Escribe el nombre del alumno..." required><option value="">BUSCA Y SELECCIONA...</option><?php foreach($Alumnos as $Al): ?><option value="<?= (int)$Al['Id'] ?>"><?= HRpt(RptOpcionAlumno($Al)) ?></option><?php endforeach; ?></select></div>
                <p class="SgceReportHint"><i class="fa-solid fa-circle-info"></i> La boleta usa el periodo seleccionado y puede imprimirse de ciclos anteriores.</p>
                <button id="BtnGenerarBoletaRojoMetalico" class="SgceReportBtn BtnReportExport BtnReporteRojoMetalico" type="submit"><span class="SgceColorIcon" aria-hidden="true">📄</span><span>Generar boleta</span></button>
            </form>
        </section>

        <section class="SgceReportCard AccentPurple">
            <div class="SgceReportCardHead"><span class="SgceReportIcon"><span class="SgceColorIcon" aria-hidden="true">🗂️</span></span><div><h5>Kardex individual</h5><p>Historial del alumno por ciclo escolar: primero, segundo, tercero o todos.</p></div></div>
            <form action="ExportarKardexAlumno.php" method="GET" target="_blank" rel="noopener noreferrer" class="SgceReportForm">
                <div class="SgceFormField Full"><label>Alumno</label><select name="AlumnoId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Escribe el nombre del alumno..." required><option value="">BUSCA Y SELECCIONA...</option><?php foreach($Alumnos as $Al): ?><option value="<?= (int)$Al['Id'] ?>"><?= HRpt(RptOpcionAlumno($Al)) ?></option><?php endforeach; ?></select></div>
                <div class="SgceFormField Full"><label>Ciclo</label><select name="CicloId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar ciclo..."><option value="0">TODOS LOS CICLOS DEL ALUMNO</option><?php foreach($Ciclos as $C): ?><option value="<?= (int)$C['Id'] ?>" <?= (int)$C['Id'] === $CicloReporteId ? 'selected' : '' ?>><?= HRpt($C['Nombre']) ?></option><?php endforeach; ?></select></div>
                <div class="SgceFormField Full"><label>Formato</label><select name="Tipo" class="form-select"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select></div>
                <p class="SgceReportHint"><i class="fa-solid fa-circle-info"></i> Si eliges todos los ciclos, el kardex muestra el historial completo conservado del alumno.</p>
                <button id="BtnExportarKardexAzulMetalico" class="SgceReportBtn BtnReportExport BtnReporteAzulMetalico" type="submit"><span class="SgceColorIcon" aria-hidden="true">🗂️</span><span>Generar kardex</span></button>
            </form>
        </section>
    </div>

</div>

<?= SgceLayoutSharedJs(['assets/js/admin/AdminSearchableSelects.js', 'assets/js/ReportesAdmin.js']) ?>
</body>
</html>
