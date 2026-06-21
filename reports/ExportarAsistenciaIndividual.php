<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/includes/SGCE_Pdf.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { http_response_code(403); exit('Acceso denegado.'); }
if (!SgcePuedeAdministrarReportes($UserSession)) { http_response_code(403); exit('No tienes permiso.'); }

$AlumnoId = (int)($_GET['AlumnoId'] ?? 0);
$CicloId = (int)($_GET['CicloId'] ?? 0);
$Tipo = (($_GET['Tipo'] ?? 'Pdf') === 'Excel') ? 'Excel' : 'Pdf';
$FechaInicio = trim((string)($_GET['FechaInicio'] ?? ''));
$FechaFin = trim((string)($_GET['FechaFin'] ?? ''));
if ($AlumnoId <= 0 || $CicloId <= 0) { http_response_code(400); exit('Selecciona alumno y ciclo.'); }
if (!SgceFechaYmdValida($FechaInicio) || !SgceFechaYmdValida($FechaFin)) { http_response_code(400); exit('Selecciona fechas válidas.'); }
if ($FechaInicio > $FechaFin) { http_response_code(400); exit('La fecha de inicio no puede ser mayor que la fecha fin.'); }
if (!SgceValidarRangoFechaYmd($FechaInicio, $FechaFin, 370)) { http_response_code(400); exit('El rango no puede superar 370 días.'); }

$StmtAlumno = $Pdo->prepare("SELECT Al.Id, Al.NombreCompleto, AI.GrupoId, AI.Estado, C.Nombre AS CicloNombre, G.Grado, G.Grupo, G.Turno
    FROM AlumnoInscripciones AI
    JOIN Alumnos Al ON Al.Id = AI.AlumnoId
    JOIN CiclosEscolares C ON C.Id = AI.CicloId
    JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId
    WHERE AI.AlumnoId = ? AND AI.CicloId = ? LIMIT 1");
$StmtAlumno->execute([$AlumnoId, $CicloId]);
$Alumno = $StmtAlumno->fetch();
if (!$Alumno) { http_response_code(404); exit('Alumno no encontrado en el ciclo seleccionado.'); }

$TablaAsistencia = SgceAsistenciaTablaParaCiclo($Pdo, $CicloId);
$Stmt = $Pdo->prepare("SELECT DATE_FORMAT(Asis.FechaDia,'%d/%m/%Y') AS FechaTexto, Asis.FechaDia, Asg.MateriaNombre, U.NombreCompleto AS Maestro, Asis.Estado
    FROM {$TablaAsistencia} Asis
    JOIN Asignaciones Asg ON Asg.Id = Asis.AsignacionId AND Asg.CicloId = Asis.CicloId
    JOIN Usuarios U ON U.Id = Asg.MaestroId
    WHERE Asis.AlumnoId = ? AND Asis.CicloId = ? AND Asis.FechaDia BETWEEN ? AND ?
    ORDER BY Asis.FechaDia DESC, Asg.MateriaNombre ASC");
$Stmt->execute([$AlumnoId, $CicloId, $FechaInicio, $FechaFin]);
$Rows = $Stmt->fetchAll();

$Estados = ['A'=>'ASISTENCIA','F'=>'FALTA','R'=>'RETARDO','J'=>'JUSTIFICANTE'];
$Conteos = ['A'=>0,'F'=>0,'R'=>0,'J'=>0];
foreach ($Rows as $R) { if (isset($Conteos[$R['Estado']])) { $Conteos[$R['Estado']]++; } }
function HAsInd($Texto){ return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function ArcAsInd($Texto){ $Texto = iconv('UTF-8','ASCII//TRANSLIT//IGNORE',(string)$Texto); $Texto = preg_replace('/[^A-Za-z0-9_\-]+/','_', $Texto); return trim($Texto,'_') ?: 'Asistencia_Individual'; }
$TituloArchivo = 'Asistencia_Individual_' . ArcAsInd($Alumno['NombreCompleto'] . '_' . $Alumno['CicloNombre']);
$GrupoTexto = trim(($Alumno['Grado'] ?? '') . ' ' . ($Alumno['Grupo'] ?? '') . ' ' . ($Alumno['Turno'] ?? ''));
$Subtitulo = 'Alumno: ' . $Alumno['NombreCompleto'] . ' | Grupo: ' . $GrupoTexto . ' | Ciclo: ' . $Alumno['CicloNombre'] . ' | Rango: ' . $FechaInicio . ' A ' . $FechaFin . ' | A ' . $Conteos['A'] . ' / F ' . $Conteos['F'] . ' / R ' . $Conteos['R'] . ' / J ' . $Conteos['J'];

if ($Tipo === 'Pdf') {
    $Filas = [];
    foreach ($Rows as $R) { $Filas[] = [$R['FechaTexto'], $R['MateriaNombre'], $R['Maestro'], $Estados[$R['Estado']] ?? $R['Estado']]; }
    SgcePdfRespuestaTabla($Pdo, 'Asistencia individual', $Subtitulo, ['Fecha','Materia','Docente','Estado'], $Filas, $TituloArchivo, 'L', [80,220,220,110]);
}

SgceHeaderDescarga($TituloArchivo . '.xls', 'application/vnd.ms-excel; charset=utf-8');
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= HAsInd($TituloArchivo) ?></title></head><body>
<h2>ASISTENCIA INDIVIDUAL</h2>
<p><?= HAsInd($Subtitulo) ?></p>
<table border="1"><thead><tr><th>Fecha</th><th>Materia</th><th>Docente</th><th>Estado</th></tr></thead><tbody>
<?php foreach($Rows as $R): ?><tr><td><?= HAsInd($R['FechaTexto']) ?></td><td><?= HAsInd($R['MateriaNombre']) ?></td><td><?= HAsInd($R['Maestro']) ?></td><td><?= HAsInd($Estados[$R['Estado']] ?? $R['Estado']) ?></td></tr><?php endforeach; if(!$Rows): ?><tr><td colspan="4">Sin registros de asistencia.</td></tr><?php endif; ?>
</tbody></table></body></html>
