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

$PeriodoInfo = SgcePeriodoInfo($Pdo, $PeriodoId);
if (!$PeriodoInfo) { http_response_code(400); exit('Periodo inválido.'); }
$CicloId = (int)$PeriodoInfo['CicloId'];
$FechaInicioCiclo = $PeriodoInfo['FechaInicio'] ?? date('Y-01-01');
$FechaFinCiclo = $PeriodoInfo['FechaFin'] ?? date('Y-12-31');
$Periodo = ['Nombre' => $PeriodoInfo['Nombre'] ?? 'PARCIAL ACTUAL', 'Ciclo' => $PeriodoInfo['CicloNombre'] ?? ''];

$StmtAlumno = $Pdo->prepare("\n    SELECT Al.Id, Al.NombreCompleto, AI.GrupoId, AI.CicloId, AI.Estado AS EstadoInscripcion,\n           G.Grado, G.Grupo, G.Turno\n    FROM AlumnoInscripciones AI\n    INNER JOIN Alumnos Al ON Al.Id = AI.AlumnoId AND Al.Activo = 1\n    INNER JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId\n    WHERE AI.AlumnoId = ?\n      AND AI.CicloId = ?\n    LIMIT 1\n");
$StmtAlumno->execute([$AlumnoId, $CicloId]);
$Alumno = $StmtAlumno->fetch();
if (!$Alumno) { http_response_code(404); exit('Alumno no encontrado en el ciclo seleccionado.'); }

$StmtCal = $Pdo->prepare("\n    SELECT Asg.MateriaNombre, U.NombreCompleto AS Maestro, C.Calificacion\n    FROM Asignaciones Asg\n    JOIN Usuarios U ON Asg.MaestroId = U.Id\n    LEFT JOIN Calificaciones C ON C.AsignacionId = Asg.Id AND C.AlumnoId = ? AND C.PeriodoId = ?\n    WHERE Asg.CicloId = ?\n      AND Asg.GrupoId = ?\n      AND Asg.Activo = 1\n    ORDER BY Asg.MateriaNombre ASC\n");
$StmtCal->execute([$AlumnoId, $PeriodoId, $CicloId, (int)$Alumno['GrupoId']]);
$Calificaciones = $StmtCal->fetchAll();

$Promedio = null;
$Cuenta = 0;
$Suma = 0;
foreach ($Calificaciones as $Fila) {
    if ($Fila['Calificacion'] !== null) { $Suma += (float)$Fila['Calificacion']; $Cuenta++; }
}
if ($Cuenta > 0) { $Promedio = round($Suma / $Cuenta, 2); }

$StmtAsis = $Pdo->prepare("\n    SELECT Asis.Estado, COUNT(*) AS Total\n    FROM Asistencias Asis\n    INNER JOIN Asignaciones Asg ON Asg.Id = Asis.AsignacionId AND Asg.CicloId = Asis.CicloId\n    WHERE Asis.AlumnoId = ?\n      AND Asis.CicloId = ?\n      AND Asg.GrupoId = ?\n      AND Asis.FechaDia BETWEEN ? AND ?\n    GROUP BY Asis.Estado\n");
$StmtAsis->execute([$AlumnoId, $CicloId, (int)$Alumno['GrupoId'], $FechaInicioCiclo, $FechaFinCiclo]);
$Conteos = ['A' => 0, 'F' => 0, 'R' => 0, 'J' => 0];
foreach ($StmtAsis->fetchAll() as $Fila) {
    if (isset($Conteos[$Fila['Estado']])) { $Conteos[$Fila['Estado']] = (int)$Fila['Total']; }
}

RegistrarBitacora($Pdo, $UserSession, 'EXPORTAR_BOLETA', 'Alumnos', $AlumnoId, 'BOLETA INDIVIDUAL GENERADA POR CICLO');

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
