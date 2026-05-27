<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !SgcePuedeAdministrarReportes($UserSession)) { header('Location: index.php'); exit; }

$AlumnoId = (int)($_GET['AlumnoId'] ?? 0);
$PeriodoId = SgcePeriodoActualId($Pdo, $_GET['PeriodoId'] ?? 0);
if ($AlumnoId <= 0) { die('Alumno inválido.'); }
function HBol($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
$ConfigReporte = SgceObtenerConfiguracion($Pdo);

$StmtAlumno = $Pdo->prepare("SELECT Al.Id, Al.NombreCompleto, Al.GrupoId, G.Grado, G.Grupo, G.Turno FROM Alumnos Al LEFT JOIN Grupos G ON Al.GrupoId = G.Id WHERE Al.Id = ? AND Al.Activo = 1 LIMIT 1");
$StmtAlumno->execute([$AlumnoId]);
$Alumno = $StmtAlumno->fetch();
if (!$Alumno) { die('Alumno no encontrado.'); }

$StmtPeriodo = $Pdo->prepare("SELECT P.Nombre, C.Nombre AS Ciclo FROM PeriodosEvaluacion P JOIN CiclosEscolares C ON P.CicloId = C.Id WHERE P.Id = ? LIMIT 1");
$StmtPeriodo->execute([$PeriodoId]);
$Periodo = $StmtPeriodo->fetch() ?: ['Nombre'=>'PARCIAL ACTUAL','Ciclo'=>''];
$PeriodoInfo = SgcePeriodoInfo($Pdo, $PeriodoId);
$FechaInicioCiclo = $PeriodoInfo['FechaInicio'] ?? date('Y-01-01');
$FechaFinCiclo = $PeriodoInfo['FechaFin'] ?? date('Y-12-31');

$StmtCal = $Pdo->prepare("SELECT Asg.MateriaNombre, U.NombreCompleto AS Maestro, C.Calificacion FROM Asignaciones Asg JOIN Usuarios U ON Asg.MaestroId = U.Id LEFT JOIN Calificaciones C ON C.AsignacionId = Asg.Id AND C.AlumnoId = ? AND C.PeriodoId = ? WHERE Asg.GrupoId = ? AND Asg.Activo = 1 ORDER BY Asg.MateriaNombre ASC");
$StmtCal->execute([$AlumnoId, $PeriodoId, $Alumno['GrupoId'] ?? 0]);
$Calificaciones = $StmtCal->fetchAll();

$Promedio = null; $Cuenta = 0; $Suma = 0;
foreach ($Calificaciones as $Fila) { if ($Fila['Calificacion'] !== null) { $Suma += (float)$Fila['Calificacion']; $Cuenta++; } }
if ($Cuenta > 0) { $Promedio = round($Suma / $Cuenta, 2); }

$StmtAsis = $Pdo->prepare("SELECT Estado, COUNT(*) AS Total FROM Asistencias WHERE AlumnoId = ? AND FechaDia BETWEEN ? AND ? GROUP BY Estado");
$StmtAsis->execute([$AlumnoId, $FechaInicioCiclo, $FechaFinCiclo]);
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
@page{size:letter;margin:1.2cm}*{box-sizing:border-box}body{margin:0;background:#eef1f5;font-family:Arial,'Segoe UI',sans-serif;color:#1f2937;font-size:12px}.ReportSheet{width:100%;max-width:950px;margin:22px auto;padding:24px;background:#fff;border:1px solid #e5e7eb;border-radius:18px;box-shadow:0 18px 45px rgba(15,23,42,.10)}.Header{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;border-bottom:4px solid #7A0818;margin-bottom:18px;padding-bottom:14px}.Header h2{margin:0;color:#7A0818;font-size:25px;font-weight:900;letter-spacing:.2px}.SchoolName{margin:0 0 4px;color:#111827;font-size:13px;font-weight:900;text-transform:uppercase}.SchoolMeta{margin:0 0 5px;color:#6b7280;font-size:10.5px;font-weight:700;text-transform:uppercase}.Header p{margin:4px 0 0;color:#4b5563;font-weight:700}.HeaderTag{padding:7px 12px;border:1px solid #ead5da;background:#fff7f8;border-radius:999px;color:#7A0818;font-weight:900;white-space:nowrap}.Info{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:18px}.Box{min-height:58px;border:1px solid #e5e7eb;border-left:5px solid #7A0818;border-radius:14px;padding:11px 12px;background:linear-gradient(180deg,#fff,#f9fafb)}.Box strong{display:block;margin-bottom:4px;color:#7A0818;font-size:11px;text-transform:uppercase;letter-spacing:.35px}.Promedio{font-size:23px;font-weight:900;color:#7A0818}.TablaWrap{border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}table{width:100%;border-collapse:collapse}th{background:#7A0818;color:#fff;padding:10px 9px;border:1px solid #7A0818;text-transform:uppercase;font-size:11px;letter-spacing:.25px}td{padding:8px 9px;border:1px solid #e5e7eb}tbody tr:nth-child(even){background:#f9fafb}.Centro{text-align:center}.Firma{margin-top:58px;text-align:center}.FirmaLinea{width:310px;margin:auto;border-top:1px solid #374151;padding-top:8px;color:#374151}@media print{body{background:#fff}.ReportSheet{max-width:none;margin:0;padding:0;border:0;border-radius:0;box-shadow:none}th{background:#7A0818!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}.Box,tbody tr:nth-child(even),.HeaderTag{ -webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
<script>window.addEventListener('load',function(){setTimeout(function(){window.print();},450);});</script>
</head>
<body>
<div class="ReportSheet">
<div class="Header"><div><div class="SchoolName"><?= HBol($ConfigReporte['NombreEscuela']) ?></div><div class="SchoolMeta"><?= HBol(trim(($ConfigReporte['ClaveCentroTrabajo'] ? 'CCT: '.$ConfigReporte['ClaveCentroTrabajo'].' · ' : '').($ConfigReporte['MunicipioEstado'] ?? ''))) ?></div><h2>Boleta individual</h2><p>Periodo: <?= HBol($Periodo['Nombre'].' '.$Periodo['Ciclo']) ?></p></div><div class="HeaderTag">REPORTE PDF</div></div>
<div class="Info">
    <div class="Box"><strong>Alumno:</strong><br><?= HBol($Alumno['NombreCompleto']) ?></div>
    <div class="Box"><strong>Grupo:</strong><br><?= HBol(($Alumno['Grado'] ?? '').' '.($Alumno['Grupo'] ?? '').' '.($Alumno['Turno'] ?? '')) ?></div>
    <div class="Box"><strong>Promedio:</strong><br><span class="Promedio"><?= $Promedio !== null ? number_format($Promedio,2) : '-' ?></span></div>
    <div class="Box"><strong>Asistencia del ciclo:</strong><br>Asistencias: <?= $Conteos['A'] ?> · Faltas: <?= $Conteos['F'] ?> · Retardos: <?= $Conteos['R'] ?> · Justificantes: <?= $Conteos['J'] ?></div>
</div>
<div class="TablaWrap"><table><thead><tr><th>Materia</th><th>Docente</th><th>Calificación</th></tr></thead><tbody>
<?php foreach($Calificaciones as $C): ?><tr><td><?= HBol($C['MateriaNombre']) ?></td><td><?= HBol($C['Maestro']) ?></td><td class="Centro"><strong><?= $C['Calificacion'] !== null ? number_format((float)$C['Calificacion'],2) : '-' ?></strong></td></tr><?php endforeach; if(!$Calificaciones): ?><tr><td colspan="3" class="Centro">Sin materias asignadas.</td></tr><?php endif; ?>
</tbody></table></div>
<div class="Firma"><div class="FirmaLinea"><?= HBol($ConfigReporte['DirectorNombre'] ?: 'Firma y sello de la institución') ?></div></div>
</div>
</body>
</html>
