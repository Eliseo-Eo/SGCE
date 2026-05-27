<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { die('Acceso denegado.'); }

$AsignacionId = (int)($_GET['AsignacionId'] ?? 0);
$GrupoId = (int)($_GET['GrupoId'] ?? 0);
$Tipo = (($_GET['Tipo'] ?? 'Excel') === 'Pdf') ? 'Pdf' : 'Excel';
$PeriodoId = SgcePeriodoActualId($Pdo, $_GET['PeriodoId'] ?? 0);

if ($AsignacionId <= 0 && $GrupoId <= 0) { die('Parámetros inválidos.'); }

function HExpCal($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function ArchivoSeguroCal($Texto) {
    $Texto = preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace(' ', '_', (string)$Texto));
    return $Texto !== '' ? $Texto : 'Reporte';
}
function FormatoCal($Valor) { return $Valor !== null && $Valor !== '' ? number_format((float)$Valor, 2) : '-'; }
function EstilosReporteCal($Landscape = false) { ?>
<style>
@page{size:letter <?= $Landscape ? 'landscape' : '' ?>;margin:1.2cm}body{font-family:Arial,'Segoe UI',sans-serif;color:#1f2937;font-size:12px}.NoPrint{padding:12px;margin-bottom:16px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px}.Header{border-bottom:4px solid #7A0818;margin-bottom:16px;padding-bottom:10px}.Header h2{margin:0;color:#7A0818;font-weight:800}.Header p{margin:4px 0 0;color:#4b5563}table{width:100%;border-collapse:collapse}th{background:#7A0818;color:#fff;padding:8px;border:1px solid #d1d5db;text-transform:uppercase;font-size:11px}td{padding:7px;border:1px solid #e5e7eb}tbody tr:nth-child(even){background:#f9fafb}.Centro{text-align:center}.Negrita{font-weight:700}.Firma{margin-top:55px;text-align:center}.FirmaLinea{width:280px;margin:auto;border-top:1px solid #374151;padding-top:7px}@media print{.NoPrint{display:none}th{background:#7A0818!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}tbody tr:nth-child(even){background:#f9fafb!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
<?php }

$StmtPeriodo = $Pdo->prepare("SELECT P.Nombre, C.Nombre AS Ciclo FROM PeriodosEvaluacion P JOIN CiclosEscolares C ON P.CicloId = C.Id WHERE P.Id = ? LIMIT 1");
$StmtPeriodo->execute([$PeriodoId]);
$Periodo = $StmtPeriodo->fetch() ?: ['Nombre' => 'PERIODO ACTUAL', 'Ciclo' => ''];

if ($GrupoId > 0) {
    if (!SgcePuedeAdministrarReportes($UserSession)) { die('No tienes permiso.'); }
    $StmtGrupo = $Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Id = ? AND Activo = 1 LIMIT 1");
    $StmtGrupo->execute([$GrupoId]);
    $Grupo = $StmtGrupo->fetch();
    if (!$Grupo) { die('Grupo no encontrado.'); }

    $StmtAsignaciones = $Pdo->prepare("SELECT A.Id, A.MateriaNombre, U.NombreCompleto AS Maestro FROM Asignaciones A JOIN Usuarios U ON A.MaestroId = U.Id WHERE A.GrupoId = ? AND A.Activo = 1 ORDER BY A.MateriaNombre ASC");
    $StmtAsignaciones->execute([$GrupoId]);
    $Asignaciones = $StmtAsignaciones->fetchAll();

    $StmtAlumnos = $Pdo->prepare("SELECT Id, NombreCompleto FROM Alumnos WHERE GrupoId = ? AND Activo = 1 ORDER BY NombreCompleto ASC");
    $StmtAlumnos->execute([$GrupoId]);
    $Alumnos = $StmtAlumnos->fetchAll();

    $Calificaciones = [];
    if ($Asignaciones && $Alumnos) {
        $StmtCal = $Pdo->prepare("SELECT AlumnoId, AsignacionId, Calificacion FROM Calificaciones WHERE PeriodoId = ? AND AsignacionId IN (SELECT Id FROM Asignaciones WHERE GrupoId = ? AND Activo = 1)");
        $StmtCal->execute([$PeriodoId, $GrupoId]);
        foreach ($StmtCal->fetchAll() as $Row) { $Calificaciones[(int)$Row['AlumnoId']][(int)$Row['AsignacionId']] = $Row['Calificacion']; }
    }

    $TituloArchivo = 'Calificaciones_Grupo_' . ArchivoSeguroCal($Grupo['Grado'].$Grupo['Grupo'].'_'.$Grupo['Turno']);
    if ($Tipo === 'Excel') { header('Content-Type: application/vnd.ms-excel; charset=utf-8'); header("Content-Disposition: attachment; filename={$TituloArchivo}.xls"); echo "\xEF\xBB\xBF"; }
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= HExpCal($TituloArchivo) ?></title><?php EstilosReporteCal(true); ?></head><body>
<?php if ($Tipo === 'Pdf'): ?><div class="NoPrint"><button onclick="window.print()">Imprimir / guardar PDF</button></div><?php endif; ?>
<div class="Header"><h2>Reporte de calificaciones por grupo</h2><p>Grupo: <?= HExpCal($Grupo['Grado'].' '.$Grupo['Grupo'].' '.$Grupo['Turno']) ?> · Periodo: <?= HExpCal($Periodo['Nombre'].' '.$Periodo['Ciclo']) ?></p></div>
<table><thead><tr><th>#</th><th>Alumno</th><?php foreach($Asignaciones as $A): ?><th><?= HExpCal($A['MateriaNombre']) ?></th><?php endforeach; ?><th>Promedio</th></tr></thead><tbody>
<?php $N=1; foreach($Alumnos as $Al): $Suma=0; $Cuenta=0; ?><tr><td class="Centro"><?= $N++ ?></td><td><?= HExpCal($Al['NombreCompleto']) ?></td><?php foreach($Asignaciones as $A): $Val=$Calificaciones[(int)$Al['Id']][(int)$A['Id']] ?? null; if($Val!==null){$Suma+=(float)$Val;$Cuenta++;} ?><td class="Centro"><?= FormatoCal($Val) ?></td><?php endforeach; ?><td class="Centro Negrita"><?= $Cuenta>0 ? number_format($Suma/$Cuenta,2) : '-' ?></td></tr><?php endforeach; if(!$Alumnos): ?><tr><td colspan="<?= count($Asignaciones)+3 ?>" class="Centro">Sin alumnos registrados.</td></tr><?php endif; ?>
</tbody></table></body></html><?php
    exit;
}

$Stmt = $Pdo->prepare("SELECT A.Id, A.MateriaNombre, A.MaestroId, G.Grado, G.Grupo, G.Turno, G.Id AS GrupoId, U.NombreCompleto AS Maestro FROM Asignaciones A JOIN Grupos G ON A.GrupoId = G.Id JOIN Usuarios U ON A.MaestroId = U.Id WHERE A.Id = ? AND A.Activo = 1 LIMIT 1");
$Stmt->execute([$AsignacionId]);
$Info = $Stmt->fetch();
if (!$Info) { die('Asignación no encontrada.'); }
if ($UserSession['Rol'] === 'maestro' && (int)$UserSession['Id'] !== (int)$Info['MaestroId']) { die('No tienes permiso.'); }

$StmtAlumnos = $Pdo->prepare("SELECT Al.NombreCompleto, C.Calificacion FROM Alumnos Al LEFT JOIN Calificaciones C ON C.AlumnoId = Al.Id AND C.AsignacionId = ? AND C.PeriodoId = ? WHERE Al.GrupoId = ? AND Al.Activo = 1 ORDER BY Al.NombreCompleto ASC");
$StmtAlumnos->execute([$AsignacionId, $PeriodoId, $Info['GrupoId']]);
$Alumnos = $StmtAlumnos->fetchAll();
$TituloArchivo = 'Calificaciones_' . ArchivoSeguroCal($Info['MateriaNombre'].'_'.$Info['Grado'].$Info['Grupo']);
if ($Tipo === 'Excel') { header('Content-Type: application/vnd.ms-excel; charset=utf-8'); header("Content-Disposition: attachment; filename={$TituloArchivo}.xls"); echo "\xEF\xBB\xBF"; }
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= HExpCal($TituloArchivo) ?></title><?php EstilosReporteCal(false); ?></head><body>
<?php if ($Tipo === 'Pdf'): ?><div class="NoPrint"><button onclick="window.print()">Imprimir / guardar PDF</button></div><?php endif; ?>
<div class="Header"><h2>Reporte de calificaciones</h2><p>Materia: <?= HExpCal($Info['MateriaNombre']) ?> · Grupo: <?= HExpCal($Info['Grado'].' '.$Info['Grupo'].' '.$Info['Turno']) ?> · Docente: <?= HExpCal($Info['Maestro']) ?> · Periodo: <?= HExpCal($Periodo['Nombre'].' '.$Periodo['Ciclo']) ?></p></div>
<table><thead><tr><th>#</th><th>Alumno</th><th>Calificación</th></tr></thead><tbody><?php $N=1; foreach($Alumnos as $Al): ?><tr><td class="Centro"><?= $N++ ?></td><td><?= HExpCal($Al['NombreCompleto']) ?></td><td class="Centro Negrita"><?= FormatoCal($Al['Calificacion']) ?></td></tr><?php endforeach; if(!$Alumnos): ?><tr><td colspan="3" class="Centro">Sin alumnos registrados.</td></tr><?php endif; ?></tbody></table>
<div class="Firma"><div class="FirmaLinea">Nombre y firma del docente</div></div>
</body></html>
