<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !SgcePuedeAdministrarReportes($UserSession)) { header('Location: index.php'); exit; }

$Grupos = $Pdo->query("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Activo = 1 ORDER BY Turno, Grado, Grupo")->fetchAll();
$Asignaciones = $Pdo->query("SELECT Asg.Id, Asg.MateriaNombre, G.Grado, G.Grupo, G.Turno, U.NombreCompleto AS Maestro FROM Asignaciones Asg JOIN Grupos G ON Asg.GrupoId = G.Id JOIN Usuarios U ON Asg.MaestroId = U.Id WHERE Asg.Activo = 1 AND G.Activo = 1 AND U.Activo = 1 ORDER BY G.Turno, G.Grado, G.Grupo, Asg.MateriaNombre")->fetchAll();
$Periodos = SgcePeriodosDisponibles($Pdo);

$BuscarAlumno = trim((string)($_GET['BuscarAlumno'] ?? ''));
$GrupoAlumno = (int)($_GET['GrupoAlumno'] ?? 0);
$Alumnos = [];
if ($BuscarAlumno !== '' || $GrupoAlumno > 0) {
    $Where = ['Al.Activo = 1'];
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
<link rel="icon" href="favicon.ico">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.css?v=final">
</head>
<body>
<div class="container py-4">
    <div class="Top mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="fw-bold"><i class="fa-solid fa-filter me-2"></i>CENTRO DE REPORTES</h2>
            <p class="mb-0">Exporta asistencia, calificaciones y boletas sin cargar datos innecesarios.</p>
        </div>
        <a href="Admin.php?Tab=inicio" class="btn btn-outline-light Btn SgceBtnInicio"><i class="fa-solid fa-house me-2"></i> VOLVER A INICIO</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card Card p-4">
                <h5 class="fw-bold text-danger"><i class="fa-solid fa-calendar-check me-2"></i>ASISTENCIAS POR GRUPO</h5>
                <form action="ExportarAsistencia.php" method="GET" target="_blank">
                    <input type="hidden" name="Rango" value="Todas">
                    <label class="fw-bold small text-muted">GRUPO</label>
                    <select name="GrupoId" class="form-select mb-3" required>
                        <option value="">SELECCIONA...</option>
                        <?php foreach($Grupos as $G): ?><option value="<?= (int)$G['Id'] ?>"><?= HGlobal($G['Grado'].' '.$G['Grupo'].' '.$G['Turno']) ?></option><?php endforeach; ?>
                    </select>
                    <div class="row g-2">
                        <div class="col"><label class="fw-bold small text-muted">FECHA INICIO</label><input type="date" name="FechaInicio" class="form-control"></div>
                        <div class="col"><label class="fw-bold small text-muted">FECHA FIN</label><input type="date" name="FechaFin" class="form-control"></div>
                    </div>
                    <label class="fw-bold small text-muted mt-3">FORMATO</label>
                    <select name="Tipo" class="form-select mb-3"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select>
                    <button class="btn Btn ReporteBtn w-100"><i class="fa-solid fa-file-export me-2"></i> EXPORTAR ASISTENCIAS</button>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card Card p-4">
                <h5 class="fw-bold text-primary"><i class="fa-solid fa-user-check me-2"></i>ASISTENCIAS POR ASIGNACIÓN</h5>
                <form action="ExportarAsistencia.php" method="GET" target="_blank">
                    <input type="hidden" name="Rango" value="Todas">
                    <label class="fw-bold small text-muted">ASIGNACIÓN</label>
                    <select name="AsignacionId" class="form-select mb-3" required>
                        <option value="">SELECCIONA...</option>
                        <?php foreach($Asignaciones as $A): ?><option value="<?= (int)$A['Id'] ?>"><?= HGlobal($A['Grado'].' '.$A['Grupo'].' '.$A['Turno'].' - '.$A['MateriaNombre'].' - '.$A['Maestro']) ?></option><?php endforeach; ?>
                    </select>
                    <div class="row g-2">
                        <div class="col"><label class="fw-bold small text-muted">FECHA INICIO</label><input type="date" name="FechaInicio" class="form-control"></div>
                        <div class="col"><label class="fw-bold small text-muted">FECHA FIN</label><input type="date" name="FechaFin" class="form-control"></div>
                    </div>
                    <label class="fw-bold small text-muted mt-3">FORMATO</label>
                    <select name="Tipo" class="form-select mb-3"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select>
                    <button class="btn Btn ReporteBtn w-100"><i class="fa-solid fa-file-export me-2"></i> EXPORTAR ASISTENCIAS</button>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card Card p-4">
                <h5 class="fw-bold text-success"><i class="fa-solid fa-star me-2"></i>CALIFICACIONES</h5>
                <form action="ExportarCalificaciones.php" method="GET" target="_blank">
                    <label class="fw-bold small text-muted">PERIODO</label>
                    <select name="PeriodoId" class="form-select mb-3"><?php foreach($Periodos as $P): ?><option value="<?= (int)$P['Id'] ?>"><?= HGlobal($P['CicloNombre'].' - '.$P['Nombre']) ?></option><?php endforeach; ?></select>
                    <label class="fw-bold small text-muted">GRUPO</label>
                    <select name="GrupoId" class="form-select mb-3"><option value="">NO USAR GRUPO</option><?php foreach($Grupos as $G): ?><option value="<?= (int)$G['Id'] ?>"><?= HGlobal($G['Grado'].' '.$G['Grupo'].' '.$G['Turno']) ?></option><?php endforeach; ?></select>
                    <label class="fw-bold small text-muted">ASIGNACIÓN</label>
                    <select name="AsignacionId" class="form-select mb-3"><option value="">NO USAR ASIGNACIÓN</option><?php foreach($Asignaciones as $A): ?><option value="<?= (int)$A['Id'] ?>"><?= HGlobal($A['MateriaNombre'].' - '.$A['Grado'].' '.$A['Grupo']) ?></option><?php endforeach; ?></select>
                    <label class="fw-bold small text-muted">FORMATO</label>
                    <select name="Tipo" class="form-select mb-3"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select>
                    <button class="btn Btn ReporteBtn w-100"><i class="fa-solid fa-star me-2"></i> EXPORTAR CALIFICACIONES</button>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card Card p-4">
                <h5 class="fw-bold text-warning"><i class="fa-solid fa-file-lines me-2"></i>BOLETA INDIVIDUAL</h5>
                <form action="ReportesAdmin.php" method="GET" class="mb-3">
                    <label class="fw-bold small text-muted">FILTRAR POR GRUPO</label>
                    <select name="GrupoAlumno" class="form-select mb-2"><option value="0">TODOS LOS GRUPOS</option><?php foreach($Grupos as $G): ?><option value="<?= (int)$G['Id'] ?>" <?= $GrupoAlumno===(int)$G['Id']?'selected':'' ?>><?= HGlobal($G['Grado'].' '.$G['Grupo'].' '.$G['Turno']) ?></option><?php endforeach; ?></select>
                    <label class="fw-bold small text-muted">BUSCAR ALUMNO</label>
                    <div class="input-group search-container"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span><input class="form-control" name="BuscarAlumno" value="<?= HGlobal($BuscarAlumno) ?>" placeholder="Nombre del alumno..."></div>
                    <button class="btn Btn ReporteBtn w-100 mt-3"><i class="fa-solid fa-search me-2"></i> BUSCAR</button>
                </form>
                <form action="ExportarAlumno.php" method="GET" target="_blank">
                    <label class="fw-bold small text-muted">PERIODO</label>
                    <select name="PeriodoId" class="form-select mb-3"><?php foreach($Periodos as $P): ?><option value="<?= (int)$P['Id'] ?>"><?= HGlobal($P['CicloNombre'].' - '.$P['Nombre']) ?></option><?php endforeach; ?></select>
                    <label class="fw-bold small text-muted">ALUMNO</label>
                    <select name="AlumnoId" class="form-select mb-3" required>
                        <option value="">SELECCIONA...</option>
                        <?php foreach($Alumnos as $Al): ?><option value="<?= (int)$Al['Id'] ?>"><?= HGlobal($Al['NombreCompleto'].' - '.$Al['Grado'].' '.$Al['Grupo'].' '.$Al['Turno']) ?></option><?php endforeach; ?>
                    </select>
                    <p class="text-muted small mb-3">Para no cargar miles de alumnos, primero busca por nombre o filtra por grupo.</p>
                    <button class="btn Btn ReporteBtn w-100"><i class="fa-solid fa-file-pdf me-2"></i> GENERAR BOLETA</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/sgce-shared.js"></script>
</body>
</html>
