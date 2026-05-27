<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !SgcePuedeAdministrarReportes($UserSession)) { header('Location: index.php'); exit; }

$AlumnoId = (int)($_GET['AlumnoId'] ?? 0);
$PeriodoId = SgcePeriodoActualId($Pdo, $_GET['PeriodoId'] ?? 0);
if ($AlumnoId <= 0) { die('Alumno inválido.'); }
function HBol($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }

$StmtAlumno = $Pdo->prepare("SELECT Al.Id, Al.NombreCompleto, Al.GrupoId, G.Grado, G.Grupo, G.Turno FROM Alumnos Al LEFT JOIN Grupos G ON Al.GrupoId = G.Id WHERE Al.Id = ? AND Al.Activo = 1 LIMIT 1");
$StmtAlumno->execute([$AlumnoId]);
$Alumno = $StmtAlumno->fetch();
if (!$Alumno) { die('Alumno no encontrado.'); }

$StmtPeriodo = $Pdo->prepare("SELECT P.Nombre, C.Nombre AS Ciclo FROM PeriodosEvaluacion P JOIN CiclosEscolares C ON P.CicloId = C.Id WHERE P.Id = ? LIMIT 1");
$StmtPeriodo->execute([$PeriodoId]);
$Periodo = $StmtPeriodo->fetch() ?: ['Nombre'=>'PERIODO ACTUAL','Ciclo'=>''];

$StmtCal = $Pdo->prepare("SELECT Asg.MateriaNombre, U.NombreCompleto AS Maestro, C.Calificacion FROM Asignaciones Asg JOIN Usuarios U ON Asg.MaestroId = U.Id LEFT JOIN Calificaciones C ON C.AsignacionId = Asg.Id AND C.AlumnoId = ? AND C.PeriodoId = ? WHERE Asg.GrupoId = ? AND Asg.Activo = 1 ORDER BY Asg.MateriaNombre ASC");
$StmtCal->execute([$AlumnoId, $PeriodoId, $Alumno['GrupoId'] ?? 0]);
$Calificaciones = $StmtCal->fetchAll();

$Promedio = null; $Cuenta = 0; $Suma = 0;
foreach ($Calificaciones as $Fila) { if ($Fila['Calificacion'] !== null) { $Suma += (float)$Fila['Calificacion']; $Cuenta++; } }
if ($Cuenta > 0) { $Promedio = round($Suma / $Cuenta, 2); }

$StmtAsis = $Pdo->prepare("SELECT Estado, COUNT(*) AS Total FROM Asistencias WHERE AlumnoId = ? GROUP BY Estado");
$StmtAsis->execute([$AlumnoId]);
$Conteos = ['A'=>0,'F'=>0,'R'=>0,'J'=>0];
foreach ($StmtAsis->fetchAll() as $Fila) { if (isset($Conteos[$Fila['Estado']])) { $Conteos[$Fila['Estado']] = (int)$Fila['Total']; } }

RegistrarBitacora($Pdo, $UserSession, 'EXPORTAR_BOLETA', 'Alumnos', $AlumnoId, 'BOLETA INDIVIDUAL GENERADA');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Boleta <?= HBol($Alumno['NombreCompleto']) ?></title>
<style>
@page{size:letter;margin:1.5cm}body{font-family:Arial,'Segoe UI',sans-serif;color:#1f2937;font-size:12px}.NoPrint{padding:12px;margin-bottom:16px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px}.Header{border-bottom:4px solid #7A0818;margin-bottom:18px;padding-bottom:12px}.Header h2{margin:0;color:#7A0818;font-size:24px}.Info{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:18px}.Box{border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#f9fafb}.Box strong{color:#7A0818}table{width:100%;border-collapse:collapse;margin-top:10px}th{background:#7A0818;color:#fff;padding:9px;border:1px solid #d1d5db;text-transform:uppercase}td{padding:8px;border:1px solid #e5e7eb}tbody tr:nth-child(even){background:#f9fafb}.Centro{text-align:center}.Promedio{font-size:22px;font-weight:900;color:#7A0818}.Firma{margin-top:65px;text-align:center}.FirmaLinea{width:300px;margin:auto;border-top:1px solid #374151;padding-top:8px}@media print{.NoPrint{display:none}th{background:#7A0818!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
</head>
<body>
<div class="NoPrint"><button onclick="window.print()">Imprimir / guardar PDF</button></div>
<div class="Header"><h2>Boleta individual</h2><p>Periodo: <?= HBol($Periodo['Nombre'].' '.$Periodo['Ciclo']) ?></p></div>
<div class="Info">
    <div class="Box"><strong>Alumno:</strong><br><?= HBol($Alumno['NombreCompleto']) ?></div>
    <div class="Box"><strong>Grupo:</strong><br><?= HBol(($Alumno['Grado'] ?? '').' '.($Alumno['Grupo'] ?? '').' '.($Alumno['Turno'] ?? '')) ?></div>
    <div class="Box"><strong>Promedio:</strong><br><span class="Promedio"><?= $Promedio !== null ? number_format($Promedio,2) : '-' ?></span></div>
    <div class="Box"><strong>Asistencia acumulada:</strong><br>Asistencias: <?= $Conteos['A'] ?> · Faltas: <?= $Conteos['F'] ?> · Retardos: <?= $Conteos['R'] ?> · Justificantes: <?= $Conteos['J'] ?></div>
</div>
<table><thead><tr><th>Materia</th><th>Docente</th><th>Calificación</th></tr></thead><tbody>
<?php foreach($Calificaciones as $C): ?><tr><td><?= HBol($C['MateriaNombre']) ?></td><td><?= HBol($C['Maestro']) ?></td><td class="Centro"><strong><?= $C['Calificacion'] !== null ? number_format((float)$C['Calificacion'],2) : '-' ?></strong></td></tr><?php endforeach; if(!$Calificaciones): ?><tr><td colspan="3" class="Centro">Sin materias asignadas.</td></tr><?php endif; ?>
</tbody></table>
<div class="Firma"><div class="FirmaLinea">Firma y sello de la institución</div></div>
</body>
</html>
