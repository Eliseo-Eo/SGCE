<?php

/*
    Archivo: HistorialAlumno.php
    Descripción: Expediente individual del alumno.
    Permite al administrador revisar calificaciones, asistencias y resumen general
    sin cargar listas completas innecesarias.
*/

require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !in_array($UserSession['Rol'], ['admin','director','secretario','coordinador','prefecto'], true)) {
    header('Location: index.php');
    exit;
}

$AlumnoId = intval($_GET['AlumnoId'] ?? 0);
$PeriodoId = SgcePeriodoActualId($Pdo, $_GET['PeriodoId'] ?? 0);
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

$StmtResumenAsis = $Pdo->prepare("\n    SELECT Estado, COUNT(*) AS Total\n    FROM Asistencias\n    WHERE AlumnoId = ?\n    GROUP BY Estado\n");
$StmtResumenAsis->execute([$AlumnoId]);
$Conteos = ['A'=>0,'F'=>0,'R'=>0,'J'=>0];
foreach ($StmtResumenAsis->fetchAll() as $Fila) {
    if (isset($Conteos[$Fila['Estado']])) {
        $Conteos[$Fila['Estado']] = (int)$Fila['Total'];
    }
}

$StmtPromedio = $Pdo->prepare("SELECT ROUND(AVG(Calificacion), 1) FROM Calificaciones WHERE AlumnoId = ?");
$StmtPromedio->execute([$AlumnoId]);
$Promedio = $StmtPromedio->fetchColumn();
$Promedio = $Promedio !== null ? $Promedio : '0.0';

$StmtCalificaciones = $Pdo->prepare("\n    SELECT Asg.MateriaNombre, U.NombreCompleto AS Maestro, C.Calificacion, C.FechaActualizacion\n    FROM Calificaciones C\n    JOIN Asignaciones Asg ON C.AsignacionId = Asg.Id\n    JOIN Usuarios U ON Asg.MaestroId = U.Id\n    WHERE C.AlumnoId = ?\n    ORDER BY Asg.MateriaNombre ASC\n");
$StmtCalificaciones->execute([$AlumnoId]);
$Calificaciones = $StmtCalificaciones->fetchAll();

$StmtAsistencias = $Pdo->prepare("\n    SELECT Asis.FechaDia, DATE_FORMAT(Asis.FechaDia, '%d/%m/%Y') AS FechaTexto, Asg.MateriaNombre, U.NombreCompleto AS Maestro, Asis.Estado\n    FROM Asistencias Asis\n    JOIN Asignaciones Asg ON Asis.AsignacionId = Asg.Id\n    JOIN Usuarios U ON Asg.MaestroId = U.Id\n    WHERE Asis.AlumnoId = ?\n    ORDER BY Asis.FechaDia DESC, Asg.MateriaNombre ASC\n    LIMIT 300\n");
$StmtAsistencias->execute([$AlumnoId]);
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
    
    



<!-- SGCE FIX10: Botones de regreso/cerrar sesión con borde tinto fuerte y estilo homologado -->



    <link rel="stylesheet" href="assets/css/sgce-base.css?v=50">
    <link rel="stylesheet" href="assets/css/sgce-shared.css?v=44">
    <link rel="stylesheet" href="assets/css/HistorialAlumno.css?v=44">
</head>
<body>
<div class="container-fluid px-4 py-4">
    <div class="Top mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="IconBox"><i class="fa-solid fa-folder-open"></i></div>
            <div>
                <h2 class="fw-black mb-1" style="font-weight:900;">EXPEDIENTE DEL ALUMNO</h2>
                <div><?= H($Alumno['NombreCompleto']) ?> · <?= H($Alumno['Grado'].' '.$Alumno['Grupo'].' '.$Alumno['Turno']) ?></div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2"><a href="ExportarAlumno.php?AlumnoId=<?= (int)$AlumnoId ?>" target="_blank" class="BtnBack BtnBoletaPdf"><i class="fa-solid fa-file-pdf"></i> BOLETA PDF</a><a href="Admin.php?Tab=alumnos" class="BtnBack SgceBtnInicio"><i class="fa-solid fa-arrow-left"></i> VOLVER A INICIO</a></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-2 col-6"><div class="Stat"><div class="text-muted fw-bold small">PROMEDIO</div><div class="fs-2 fw-bold"><?= H($Promedio) ?></div></div></div>
        <div class="col-md-2 col-6"><div class="Stat"><div class="text-muted fw-bold small">ASISTENCIAS</div><div class="fs-2 fw-bold text-success"><?= $Conteos['A'] ?></div></div></div>
        <div class="col-md-2 col-6"><div class="Stat"><div class="text-muted fw-bold small">FALTAS</div><div class="fs-2 fw-bold text-danger"><?= $Conteos['F'] ?></div></div></div>
        <div class="col-md-2 col-6"><div class="Stat"><div class="text-muted fw-bold small">RETARDOS</div><div class="fs-2 fw-bold text-warning"><?= $Conteos['R'] ?></div></div></div>
        <div class="col-md-2 col-6"><div class="Stat"><div class="text-muted fw-bold small">JUSTIFICANTES</div><div class="fs-2 fw-bold text-primary"><?= $Conteos['J'] ?></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="Card p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-star text-warning me-2"></i> CALIFICACIONES</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Materia</th><th>Docente</th><th>Calificación</th></tr></thead>
                        <tbody>
                            <?php foreach($Calificaciones as $C): ?>
                            <tr><td><?= H($C['MateriaNombre']) ?></td><td><?= H($C['Maestro']) ?></td><td><span class="badge bg-dark"><?= H(number_format((float)$C['Calificacion'],2)) ?></span></td></tr>
                            <?php endforeach; ?>
                            <?php if(empty($Calificaciones)): ?><tr><td colspan="3">SIN CALIFICACIONES CAPTURADAS.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="Card p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-calendar-check text-success me-2"></i> ÚLTIMAS ASISTENCIAS</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Fecha</th><th>Materia</th><th>Docente</th><th>Estado</th></tr></thead>
                        <tbody>
                            <?php foreach($Asistencias as $A): ?>
                            <tr><td><?= H($A['FechaTexto']) ?></td><td><?= H($A['MateriaNombre']) ?></td><td><?= H($A['Maestro']) ?></td><td><span class="badge bg-<?= ClaseEstado($A['Estado']) ?>"><?= TextoEstado($A['Estado']) ?></span></td></tr>
                            <?php endforeach; ?>
                            <?php if(empty($Asistencias)): ?><tr><td colspan="4">SIN ASISTENCIAS CAPTURADAS.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-muted small">SE MUESTRAN HASTA 300 REGISTROS RECIENTES PARA EVITAR CARGAS PESADAS.</div>
            </div>
        </div>
    </div>
</div>



<!-- SGCE FIX12: Homologación final de botones superiores y reportes -->



<script src="assets/js/sgce-shared.js?v=44"></script>
</body>
</html>
