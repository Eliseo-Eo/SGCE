<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/includes/SGCE_Pdf.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'reportes', 'No tienes permiso para exportar boletas administrativas.');

$AlumnoId = (int)($_GET['AlumnoId'] ?? 0);
$PeriodoId = SgcePeriodoActualId($Pdo, $_GET['PeriodoId'] ?? 0);
if ($AlumnoId <= 0) { http_response_code(400); exit('Alumno inválido.'); }

$StmtAlumno = $Pdo->prepare("SELECT Al.Id, Al.NombreCompleto, Al.GrupoId, G.Grado, G.Grupo, G.Turno FROM Alumnos Al LEFT JOIN Grupos G ON Al.GrupoId = G.Id WHERE Al.Id = ? AND Al.Activo = 1 LIMIT 1");
$StmtAlumno->execute([$AlumnoId]);
$Alumno = $StmtAlumno->fetch();
if (!$Alumno) { http_response_code(404); exit('Alumno no encontrado.'); }

$StmtPeriodo = $Pdo->prepare("SELECT P.Nombre, C.Nombre AS Ciclo FROM PeriodosEvaluacion P JOIN CiclosEscolares C ON P.CicloId = C.Id WHERE P.Id = ? LIMIT 1");
$StmtPeriodo->execute([$PeriodoId]);
$Periodo = $StmtPeriodo->fetch() ?: ['Nombre' => 'PARCIAL ACTUAL', 'Ciclo' => ''];
$PeriodoInfo = SgcePeriodoInfo($Pdo, $PeriodoId);
$FechaInicioCiclo = $PeriodoInfo['FechaInicio'] ?? date('Y-01-01');
$FechaFinCiclo = $PeriodoInfo['FechaFin'] ?? date('Y-12-31');

$StmtCal = $Pdo->prepare("SELECT Asg.MateriaNombre, U.NombreCompleto AS Maestro, C.Calificacion FROM Asignaciones Asg JOIN Usuarios U ON Asg.MaestroId = U.Id LEFT JOIN Calificaciones C ON C.AsignacionId = Asg.Id AND C.AlumnoId = ? AND C.PeriodoId = ? WHERE Asg.GrupoId = ? AND Asg.Activo = 1 ORDER BY Asg.MateriaNombre ASC");
$StmtCal->execute([$AlumnoId, $PeriodoId, $Alumno['GrupoId'] ?? 0]);
$Calificaciones = $StmtCal->fetchAll();

$Promedio = null;
$Cuenta = 0;
$Suma = 0;
foreach ($Calificaciones as $Fila) {
    if ($Fila['Calificacion'] !== null) { $Suma += (float)$Fila['Calificacion']; $Cuenta++; }
}
if ($Cuenta > 0) { $Promedio = round($Suma / $Cuenta, 2); }

$StmtAsis = $Pdo->prepare("SELECT Estado, COUNT(*) AS Total FROM Asistencias WHERE AlumnoId = ? AND FechaDia BETWEEN ? AND ? GROUP BY Estado");
$StmtAsis->execute([$AlumnoId, $FechaInicioCiclo, $FechaFinCiclo]);
$Conteos = ['A' => 0, 'F' => 0, 'R' => 0, 'J' => 0];
foreach ($StmtAsis->fetchAll() as $Fila) {
    if (isset($Conteos[$Fila['Estado']])) { $Conteos[$Fila['Estado']] = (int)$Fila['Total']; }
}

RegistrarBitacora($Pdo, $UserSession, 'EXPORTAR_BOLETA', 'Alumnos', $AlumnoId, 'BOLETA INDIVIDUAL GENERADA');

$FilasPdf = [];
foreach ($Calificaciones as $C) {
    $FilasPdf[] = [
        (string)$C['MateriaNombre'],
        (string)$C['Maestro'],
        $C['Calificacion'] !== null ? number_format((float)$C['Calificacion'], 2) : '-'
    ];
}
$GrupoTexto = trim(($Alumno['Grado'] ?? '') . ' ' . ($Alumno['Grupo'] ?? '') . ' ' . ($Alumno['Turno'] ?? ''));
$SubtituloPdf = 'Alumno: ' . $Alumno['NombreCompleto'] . ' | Grupo: ' . $GrupoTexto . ' | Periodo: ' . $Periodo['Nombre'] . ' ' . $Periodo['Ciclo'] . ' | Promedio: ' . ($Promedio !== null ? number_format($Promedio, 2) : '-') . ' | Asistencia: A ' . $Conteos['A'] . ' / F ' . $Conteos['F'] . ' / R ' . $Conteos['R'] . ' / J ' . $Conteos['J'];
SgcePdfRespuestaTabla($Pdo, 'Boleta individual', $SubtituloPdf, ['Materia', 'Docente', 'Calificación'], $FilasPdf, 'Boleta_' . $Alumno['NombreCompleto'], 'P', [245, 210, 85]);
