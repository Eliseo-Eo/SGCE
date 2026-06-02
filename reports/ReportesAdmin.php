<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'reportes', 'No tienes permiso para entrar al centro de reportes.');

$Grupos = $Pdo->query("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Activo = 1 ORDER BY Turno, Grado, Grupo")->fetchAll();
$Asignaciones = $Pdo->query("SELECT Asg.Id, Asg.MateriaNombre, G.Grado, G.Grupo, G.Turno, U.NombreCompleto AS Maestro FROM Asignaciones Asg JOIN Grupos G ON Asg.GrupoId = G.Id JOIN Usuarios U ON Asg.MaestroId = U.Id WHERE Asg.Activo = 1 AND G.Activo = 1 AND U.Activo = 1 ORDER BY G.Turno, G.Grado, G.Grupo, Asg.MateriaNombre")->fetchAll();
$Periodos = SgcePeriodosDisponibles($Pdo);
$CicloActivo = $Pdo->query("SELECT Id, Nombre, FechaInicio, FechaFin FROM CiclosEscolares WHERE Activo = 1 ORDER BY FechaInicio DESC, Id DESC LIMIT 1")->fetch() ?: ['Id'=>0,'Nombre'=>'','FechaInicio'=>'','FechaFin'=>''];

$BuscarAlumno = trim((string)($_GET['BuscarAlumno'] ?? ''));
$GrupoAlumno = (int)($_GET['GrupoAlumno'] ?? 0);
$Alumnos = [];
if ($BuscarAlumno !== '' || $GrupoAlumno > 0) {
    $Where = ['Al.Activo = 1', 'G.Activo = 1'];
    $Params = [];
    if ($GrupoAlumno > 0) { $Where[] = 'Al.GrupoId = ?'; $Params[] = $GrupoAlumno; }
    if ($BuscarAlumno !== '') { $Where[] = 'Al.NombreCompleto LIKE ?'; $Params[] = '%' . SgceNormalizarMayusculas($BuscarAlumno) . '%'; }
    $Sql = "SELECT Al.Id, Al.NombreCompleto, G.Grado, G.Grupo, G.Turno FROM Alumnos Al JOIN Grupos G ON Al.GrupoId = G.Id WHERE " . implode(' AND ', $Where) . " ORDER BY G.Turno, G.Grado, G.Grupo, Al.NombreCompleto LIMIT 80";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute($Params);
    $Alumnos = $Stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SGCE | Reportes</title>
<link rel="icon" href="assets/media/img/favicon.ico">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.min.css?v=sgce">
<link rel="stylesheet" href="assets/css/sgce-soft-motion.css?v=sgce">
<?= SgceEstilosTema($Pdo) ?>
<link rel="stylesheet" href="assets/css/reportes-botones-metalicos.css?v=sgce">
</head>
<body class="SgceReportsPage">
<div class="container py-4 SgceReportsWrap">
    <div class="Top ReportHero mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="ReportHeroTitle">
            <div class="IconBox"><span class="SgceColorIcon" aria-hidden="true">📊</span></div>
            <div>
                <h2 class="fw-bold mb-1">CENTRO DE REPORTES</h2>
                <p class="mb-0">Exporta asistencia, calificaciones y boletas del ciclo activo.</p>
            </div>
        </div>
        <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
    </div>

    <div class="SgceReportCards">
        <section class="SgceReportCard AccentRed">
            <div class="SgceReportCardHead">
                <span class="SgceReportIcon"><span class="SgceColorIcon" aria-hidden="true">📅</span></span>
                <div>
                    <h5>Asistencias por grupo</h5>
                    <p>Reporte de asistencia por grupo y rango de fechas.</p>
                </div>
            </div>
            <form action="ExportarAsistencia.php" method="GET" target="_blank" rel="noopener noreferrer" class="SgceReportForm">
                <input type="hidden" name="Rango" value="Todas">
                <input type="hidden" name="CicloId" value="<?= (int)$CicloActivo['Id'] ?>">
                <div class="SgceFormField Full">
                    <label>Grupo</label>
                    <select name="GrupoId" class="form-select" required>
                        <option value="">SELECCIONA...</option>
                        <?php foreach($Grupos as $G): ?><option value="<?= (int)$G['Id'] ?>"><?= HGlobal($G['Grado'].' '.$G['Grupo'].' '.$G['Turno']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="SgceReportTwoCols">
                    <div class="SgceFormField">
                        <label>Fecha inicio</label>
                        <input type="date" name="FechaInicio" class="form-control" value="<?= HGlobal($CicloActivo['FechaInicio'] ?? '') ?>" required>
                    </div>
                    <div class="SgceFormField">
                        <label>Fecha fin</label>
                        <input type="date" name="FechaFin" class="form-control" value="<?= HGlobal($CicloActivo['FechaFin'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="SgceFormField Full">
                    <label>Formato</label>
                    <select name="Tipo" class="form-select"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select>
                </div>
                <button id="BtnExportarAsistenciaGrupoVerdeMetalico" class="SgceReportBtn BtnReportExport BtnReporteVerdeMetalico" type="submit"><span class="SgceColorIcon" aria-hidden="true">📤</span><span>Exportar asistencias</span></button>
            </form>
        </section>

        <section class="SgceReportCard AccentBlue">
            <div class="SgceReportCardHead">
                <span class="SgceReportIcon"><span class="SgceColorIcon" aria-hidden="true">✅</span></span>
                <div>
                    <h5>Asistencias por asignación</h5>
                    <p>Reporte de asistencia por materia, docente y grupo.</p>
                </div>
            </div>
            <form action="ExportarAsistencia.php" method="GET" target="_blank" rel="noopener noreferrer" class="SgceReportForm">
                <input type="hidden" name="Rango" value="Todas">
                <input type="hidden" name="CicloId" value="<?= (int)$CicloActivo['Id'] ?>">
                <div class="SgceFormField Full">
                    <label>Asignación</label>
                    <select name="AsignacionId" class="form-select" required>
                        <option value="">SELECCIONA...</option>
                        <?php foreach($Asignaciones as $A): ?><option value="<?= (int)$A['Id'] ?>"><?= HGlobal($A['Grado'].' '.$A['Grupo'].' '.$A['Turno'].' - '.$A['MateriaNombre'].' - '.$A['Maestro']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="SgceReportTwoCols">
                    <div class="SgceFormField">
                        <label>Fecha inicio</label>
                        <input type="date" name="FechaInicio" class="form-control" value="<?= HGlobal($CicloActivo['FechaInicio'] ?? '') ?>" required>
                    </div>
                    <div class="SgceFormField">
                        <label>Fecha fin</label>
                        <input type="date" name="FechaFin" class="form-control" value="<?= HGlobal($CicloActivo['FechaFin'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="SgceFormField Full">
                    <label>Formato</label>
                    <select name="Tipo" class="form-select"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select>
                </div>
                <button id="BtnExportarAsistenciaAsignacionVerdeMetalico" class="SgceReportBtn BtnReportExport BtnReporteVerdeMetalico" type="submit"><span class="SgceColorIcon" aria-hidden="true">📤</span><span>Exportar asistencias</span></button>
            </form>
        </section>

        <section class="SgceReportCard AccentGreen">
            <div class="SgceReportCardHead">
                <span class="SgceReportIcon"><span class="SgceColorIcon" aria-hidden="true">⭐</span></span>
                <div>
                    <h5>Calificaciones</h5>
                    <p>Promedios y registros por periodo, grupo, asignación o resumen general.</p>
                </div>
            </div>
            <form action="ExportarCalificaciones.php" method="GET" target="_blank" rel="noopener noreferrer" class="SgceReportForm">
                <div class="SgceFormField Full">
                    <label>Periodo</label>
                    <select name="PeriodoId" class="form-select"><?php foreach($Periodos as $P): ?><option value="<?= (int)$P['Id'] ?>"><?= HGlobal($P['CicloNombre'].' - '.$P['Nombre']) ?></option><?php endforeach; ?></select>
                </div>
                <div class="SgceReportTwoCols">
                    <div class="SgceFormField">
                        <label>Grupo</label>
                        <select name="GrupoId" class="form-select"><option value="">GENERAL / TODOS</option><?php foreach($Grupos as $G): ?><option value="<?= (int)$G['Id'] ?>"><?= HGlobal($G['Grado'].' '.$G['Grupo'].' '.$G['Turno']) ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="SgceFormField">
                        <label>Asignación</label>
                        <select name="AsignacionId" class="form-select"><option value="">GENERAL / TODAS</option><?php foreach($Asignaciones as $A): ?><option value="<?= (int)$A['Id'] ?>"><?= HGlobal($A['MateriaNombre'].' - '.$A['Grado'].' '.$A['Grupo']) ?></option><?php endforeach; ?></select>
                    </div>
                </div>
                <div class="SgceFormField Full">
                    <label>Formato</label>
                    <select name="Tipo" class="form-select"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select>
                </div>
                <p class="SgceReportHint"><i class="fa-solid fa-circle-info"></i> Si dejas grupo y asignación en general, se exporta el resumen completo del periodo.</p>
                <button id="BtnExportarCalificacionesVerdeMetalico" class="SgceReportBtn BtnReportExport BtnReporteVerdeMetalico" type="submit"><span class="SgceColorIcon" aria-hidden="true">⭐</span><span>Exportar calificaciones</span></button>
            </form>
        </section>

        <section class="SgceReportCard AccentOrange">
            <div class="SgceReportCardHead">
                <span class="SgceReportIcon"><span class="SgceColorIcon" aria-hidden="true">📄</span></span>
                <div>
                    <h5>Boleta individual</h5>
                    <p>Busca el alumno y genera su boleta del periodo seleccionado.</p>
                </div>
            </div>
            <div class="SgceBoletaBox">
                <form action="ReportesAdmin.php" method="GET" class="SgceReportForm SgceSearchForm">
                    <div class="SgceFormField Full">
                        <label>Filtrar por grupo</label>
                        <select name="GrupoAlumno" class="form-select"><option value="0">TODOS LOS GRUPOS</option><?php foreach($Grupos as $G): ?><option value="<?= (int)$G['Id'] ?>" <?= $GrupoAlumno===(int)$G['Id']?'selected':'' ?>><?= HGlobal($G['Grado'].' '.$G['Grupo'].' '.$G['Turno']) ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="SgceFormField Full">
                        <label>Buscar alumno</label>
                        <div class="input-group search-container"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span><input class="form-control" name="BuscarAlumno" value="<?= HGlobal($BuscarAlumno) ?>" placeholder="Nombre del alumno..."></div>
                    </div>
                    <button id="BtnBuscarAlumnoAzulMetalico" class="SgceReportBtn BtnReportSearch BtnReporteAzulMetalico" type="submit"><span class="SgceColorIcon" aria-hidden="true">🔎</span><span>Buscar alumno</span></button>
                </form>
                <form action="ExportarAlumno.php" method="GET" target="_blank" rel="noopener noreferrer" class="SgceReportForm SgceBoletaExportForm">
                    <div class="SgceReportTwoCols">
                        <div class="SgceFormField">
                            <label>Periodo</label>
                            <select name="PeriodoId" class="form-select"><?php foreach($Periodos as $P): ?><option value="<?= (int)$P['Id'] ?>"><?= HGlobal($P['CicloNombre'].' - '.$P['Nombre']) ?></option><?php endforeach; ?></select>
                        </div>
                        <div class="SgceFormField">
                            <label>Alumno</label>
                            <select name="AlumnoId" class="form-select" required>
                                <option value="">SELECCIONA...</option>
                                <?php foreach($Alumnos as $Al): ?><option value="<?= (int)$Al['Id'] ?>"><?= HGlobal($Al['NombreCompleto'].' - '.$Al['Grado'].' '.$Al['Grupo'].' '.$Al['Turno']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <p class="SgceReportHint"><i class="fa-solid fa-circle-info"></i> Primero busca por nombre o filtra por grupo para cargar alumnos.</p>
                    <button id="BtnGenerarBoletaRojoMetalico" class="SgceReportBtn BtnReportExport BtnReporteRojoMetalico" type="submit"><span class="SgceColorIcon" aria-hidden="true">📄</span><span>Generar boleta</span></button>
                </form>
            </div>
        </section>
    </div>
</div>

<script src="assets/js/ReportesAdmin.js?v=sgce"></script>
<script src="assets/js/sgce-shared.js?v=sgce"></script>
</body>
</html>
