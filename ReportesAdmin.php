<?php
/*
    Archivo: ReportesAdmin.php
    Descripción: Centro de reportes con filtros para exportar asistencia, calificaciones y boletas.
    Evita tener que exportar todo cuando solo se necesita un rango, grupo, materia o alumno.
*/
require 'Conexion.php';
$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !in_array($UserSession['Rol'], ['admin','director','secretario','coordinador','prefecto'], true)) {
    header('Location: index.php');
    exit;
}
$Grupos = $Pdo->query("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Activo=1 ORDER BY Turno, Grado, Grupo")->fetchAll();
$Asignaciones = $Pdo->query("SELECT Asg.Id, Asg.MateriaNombre, G.Grado, G.Grupo, G.Turno, U.NombreCompleto AS Maestro FROM Asignaciones Asg JOIN Grupos G ON Asg.GrupoId=G.Id JOIN Usuarios U ON Asg.MaestroId=U.Id WHERE Asg.Activo=1 ORDER BY G.Turno, G.Grado, G.Grupo, Asg.MateriaNombre")->fetchAll();
$Alumnos = $Pdo->query("SELECT Al.Id, Al.NombreCompleto, G.Grado, G.Grupo, G.Turno FROM Alumnos Al JOIN Grupos G ON Al.GrupoId=G.Id WHERE Al.Activo=1 ORDER BY G.Turno, G.Grado, G.Grupo, Al.NombreCompleto LIMIT 5000")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>SGCE | Reportes</title><link rel="icon" href="favicon.ico"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    



<!-- SGCE FIX10: Botones de regreso/cerrar sesión con borde tinto fuerte y estilo homologado -->



    <link rel="stylesheet" href="assets/css/sgce-base.css?v=50">
    <link rel="stylesheet" href="assets/css/sgce-shared.css?v=44">
    <link rel="stylesheet" href="assets/css/ReportesAdmin.css?v=44">
</head>
<body>
<div class="container py-4">
<div class="Top mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3"><div><h2 class="fw-bold"><i class="fa-solid fa-filter me-2"></i>CENTRO DE REPORTES</h2><p class="mb-0">EXPORTA SOLO LO QUE NECESITAS: POR FECHAS, GRUPO, ASIGNACIÓN O ALUMNO.</p></div><a href="Admin.php?Tab=inicio" class="btn btn-outline-light Btn SgceBtnInicio"><i class="fa-solid fa-arrow-left me-2"></i> VOLVER A INICIO</a></div>
<div class="row g-4">
<div class="col-lg-6"><div class="card Card p-4"><h5 class="fw-bold text-danger">ASISTENCIAS POR GRUPO</h5><form action="ExportarAsistencia.php" method="GET" target="_blank"><input type="hidden" name="Rango" value="Todas"><label class="fw-bold small text-muted">GRUPO</label><select name="GrupoId" class="form-select mb-3" required><option value="">SELECCIONA...</option><?php foreach($Grupos as $G): ?><option value="<?= (int)$G['Id'] ?>"><?= htmlspecialchars($G['Grado'].' '.$G['Grupo'].' '.$G['Turno']) ?></option><?php endforeach; ?></select><div class="row g-2"><div class="col"><label class="fw-bold small text-muted">FECHA INICIO</label><input type="date" name="FechaInicio" class="form-control"></div><div class="col"><label class="fw-bold small text-muted">FECHA FIN</label><input type="date" name="FechaFin" class="form-control"></div></div><label class="fw-bold small text-muted mt-3">FORMATO</label><select name="Tipo" class="form-select mb-3"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select><button class="btn Btn ReporteBtn w-100" style="background:linear-gradient(135deg,#7A0818,#A10D26) !important;color:#FFFFFF !important;border:3px solid #3B030A !important;box-shadow:0 12px 28px rgba(122,8,24,.28) !important;"><i class="fa-solid fa-file-export me-2"></i> EXPORTAR ASISTENCIAS</button></form></div></div>
<div class="col-lg-6"><div class="card Card p-4"><h5 class="fw-bold text-primary">ASISTENCIAS POR ASIGNACIÓN</h5><form action="ExportarAsistencia.php" method="GET" target="_blank"><input type="hidden" name="Rango" value="Todas"><label class="fw-bold small text-muted">ASIGNACIÓN</label><select name="AsignacionId" class="form-select mb-3" required><option value="">SELECCIONA...</option><?php foreach($Asignaciones as $A): ?><option value="<?= (int)$A['Id'] ?>"><?= htmlspecialchars($A['Grado'].' '.$A['Grupo'].' '.$A['Turno'].' - '.$A['MateriaNombre'].' - '.$A['Maestro']) ?></option><?php endforeach; ?></select><div class="row g-2"><div class="col"><label class="fw-bold small text-muted">FECHA INICIO</label><input type="date" name="FechaInicio" class="form-control"></div><div class="col"><label class="fw-bold small text-muted">FECHA FIN</label><input type="date" name="FechaFin" class="form-control"></div></div><label class="fw-bold small text-muted mt-3">FORMATO</label><select name="Tipo" class="form-select mb-3"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select><button class="btn Btn ReporteBtn w-100" style="background:linear-gradient(135deg,#7A0818,#A10D26) !important;color:#FFFFFF !important;border:3px solid #3B030A !important;box-shadow:0 12px 28px rgba(122,8,24,.28) !important;"><i class="fa-solid fa-file-export me-2"></i> EXPORTAR ASISTENCIAS</button></form></div></div>
<div class="col-lg-6"><div class="card Card p-4"><h5 class="fw-bold text-success">CALIFICACIONES</h5><form action="ExportarCalificaciones.php" method="GET" target="_blank"><label class="fw-bold small text-muted">GRUPO</label><select name="GrupoId" class="form-select mb-3"><option value="">NO USAR GRUPO</option><?php foreach($Grupos as $G): ?><option value="<?= (int)$G['Id'] ?>"><?= htmlspecialchars($G['Grado'].' '.$G['Grupo'].' '.$G['Turno']) ?></option><?php endforeach; ?></select><label class="fw-bold small text-muted">ASIGNACIÓN</label><select name="AsignacionId" class="form-select mb-3"><option value="">NO USAR ASIGNACIÓN</option><?php foreach($Asignaciones as $A): ?><option value="<?= (int)$A['Id'] ?>"><?= htmlspecialchars($A['MateriaNombre'].' - '.$A['Grado'].' '.$A['Grupo']) ?></option><?php endforeach; ?></select><label class="fw-bold small text-muted">FORMATO</label><select name="Tipo" class="form-select mb-3"><option value="Pdf">PDF</option><option value="Excel">EXCEL</option></select><button class="btn Btn ReporteBtn w-100" style="background:linear-gradient(135deg,#7A0818,#A10D26) !important;color:#FFFFFF !important;border:3px solid #3B030A !important;box-shadow:0 12px 28px rgba(122,8,24,.28) !important;"><i class="fa-solid fa-star me-2"></i> EXPORTAR CALIFICACIONES</button></form></div></div>
<div class="col-lg-6"><div class="card Card p-4"><h5 class="fw-bold text-warning">BOLETA INDIVIDUAL</h5><form action="ExportarAlumno.php" method="GET" target="_blank"><label class="fw-bold small text-muted">ALUMNO</label><select name="AlumnoId" class="form-select mb-3" required><option value="">SELECCIONA...</option><?php foreach($Alumnos as $Al): ?><option value="<?= (int)$Al['Id'] ?>"><?= htmlspecialchars($Al['NombreCompleto'].' - '.$Al['Grado'].' '.$Al['Grupo'].' '.$Al['Turno']) ?></option><?php endforeach; ?></select><button class="btn Btn ReporteBtn w-100" style="background:linear-gradient(135deg,#7A0818,#A10D26) !important;color:#FFFFFF !important;border:3px solid #3B030A !important;box-shadow:0 12px 28px rgba(122,8,24,.28) !important;"><i class="fa-solid fa-file-pdf me-2"></i> GENERAR BOLETA</button></form></div></div>
</div>
</div>



<!-- SGCE FIX12: Homologación final de botones superiores y reportes -->



<script src="assets/js/sgce-shared.js?v=44"></script>
</body>
</html>
