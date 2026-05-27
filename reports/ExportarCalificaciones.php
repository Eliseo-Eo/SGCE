<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

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
$ConfigReporte = SgceObtenerConfiguracion($Pdo);
function EstilosReporteCal($Landscape = false) { ?>
<style>
@page{size:letter <?= $Landscape ? 'landscape' : '' ?>;margin:1.1cm}*{box-sizing:border-box}body{margin:0;background:#eef1f5;font-family:Arial,'Segoe UI',sans-serif;color:#1f2937;font-size:12px}.ReportSheet{width:100%;max-width:<?= $Landscape ? '1180' : '950' ?>px;margin:22px auto;padding:24px;background:#fff;border:1px solid #e5e7eb;border-radius:18px;box-shadow:0 18px 45px rgba(15,23,42,.10)}.Header{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;border-bottom:4px solid #7A0818;margin-bottom:16px;padding-bottom:12px}.Header h2{margin:0;color:#7A0818;font-size:24px;font-weight:900}.SchoolName{margin:0 0 4px;color:#111827;font-size:13px;font-weight:900;text-transform:uppercase}.SchoolMeta{margin:0 0 5px;color:#6b7280;font-size:10.5px;font-weight:700;text-transform:uppercase}.Header p{margin:5px 0 0;color:#4b5563;font-weight:700}.HeaderTag{padding:7px 12px;border:1px solid #ead5da;background:#fff7f8;border-radius:999px;color:#7A0818;font-weight:900;white-space:nowrap}.TablaWrap{border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}table{width:100%;border-collapse:collapse}th{background:#7A0818;color:#fff;padding:9px 8px;border:1px solid #7A0818;text-transform:uppercase;font-size:11px;letter-spacing:.25px}td{padding:7px 8px;border:1px solid #e5e7eb}tbody tr:nth-child(even){background:#f9fafb}.Centro{text-align:center}.Negrita{font-weight:800}.Firma{margin-top:55px;text-align:center}.FirmaLinea{width:290px;margin:auto;border-top:1px solid #374151;padding-top:7px;color:#374151}@media print{body{background:#fff}.ReportSheet{max-width:none;margin:0;padding:0;border:0;border-radius:0;box-shadow:none}th{background:#7A0818!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}tbody tr:nth-child(even),.HeaderTag{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
<?php }

$StmtPeriodo = $Pdo->prepare("SELECT P.Nombre, C.Nombre AS Ciclo FROM PeriodosEvaluacion P JOIN CiclosEscolares C ON P.CicloId = C.Id WHERE P.Id = ? LIMIT 1");
$StmtPeriodo->execute([$PeriodoId]);
$Periodo = $StmtPeriodo->fetch() ?: ['Nombre' => 'PARCIAL ACTUAL', 'Ciclo' => ''];

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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= HExpCal($TituloArchivo) ?></title><?php EstilosReporteCal(true); ?><?php if ($Tipo === 'Pdf'): ?><script>window.addEventListener('load',function(){setTimeout(function(){window.print();},450);});</script><?php endif; ?></head><body>
<div class="ReportSheet">
<div class="Header"><div><div class="SchoolName"><?= HExpCal($ConfigReporte['NombreEscuela']) ?></div><div class="SchoolMeta"><?= HExpCal(trim(($ConfigReporte['ClaveCentroTrabajo'] ? 'CCT: '.$ConfigReporte['ClaveCentroTrabajo'].' · ' : '').($ConfigReporte['MunicipioEstado'] ?? ''))) ?></div><h2>Reporte de calificaciones por grupo</h2><p>Grupo: <?= HExpCal($Grupo['Grado'].' '.$Grupo['Grupo'].' '.$Grupo['Turno']) ?> · Periodo: <?= HExpCal($Periodo['Nombre'].' '.$Periodo['Ciclo']) ?></p></div><div class="HeaderTag"><?= HExpCal($Tipo) ?></div></div>
<div class="TablaWrap"><table><thead><tr><th>#</th><th>Alumno</th><?php foreach($Asignaciones as $A): ?><th><?= HExpCal($A['MateriaNombre']) ?></th><?php endforeach; ?><th>Promedio</th></tr></thead><tbody>
<?php $N=1; foreach($Alumnos as $Al): $Suma=0; $Cuenta=0; ?><tr><td class="Centro"><?= $N++ ?></td><td><?= HExpCal($Al['NombreCompleto']) ?></td><?php foreach($Asignaciones as $A): $Val=$Calificaciones[(int)$Al['Id']][(int)$A['Id']] ?? null; if($Val!==null){$Suma+=(float)$Val;$Cuenta++;} ?><td class="Centro"><?= FormatoCal($Val) ?></td><?php endforeach; ?><td class="Centro Negrita"><?= $Cuenta>0 ? number_format($Suma/$Cuenta,2) : '-' ?></td></tr><?php endforeach; if(!$Alumnos): ?><tr><td colspan="<?= count($Asignaciones)+3 ?>" class="Centro">Sin alumnos registrados.</td></tr><?php endif; ?>
</tbody></table></div>
</div>
</body></html><?php
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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= HExpCal($TituloArchivo) ?></title><?php EstilosReporteCal(false); ?><?php if ($Tipo === 'Pdf'): ?><script>window.addEventListener('load',function(){setTimeout(function(){window.print();},450);});</script><?php endif; ?></head><body>
<div class="ReportSheet">
<div class="Header"><div><div class="SchoolName"><?= HExpCal($ConfigReporte['NombreEscuela']) ?></div><div class="SchoolMeta"><?= HExpCal(trim(($ConfigReporte['ClaveCentroTrabajo'] ? 'CCT: '.$ConfigReporte['ClaveCentroTrabajo'].' · ' : '').($ConfigReporte['MunicipioEstado'] ?? ''))) ?></div><h2>Reporte de calificaciones</h2><p>Materia: <?= HExpCal($Info['MateriaNombre']) ?> · Grupo: <?= HExpCal($Info['Grado'].' '.$Info['Grupo'].' '.$Info['Turno']) ?> · Docente: <?= HExpCal($Info['Maestro']) ?> · Periodo: <?= HExpCal($Periodo['Nombre'].' '.$Periodo['Ciclo']) ?></p></div><div class="HeaderTag"><?= HExpCal($Tipo) ?></div></div>
<div class="TablaWrap"><table><thead><tr><th>#</th><th>Alumno</th><th>Calificación</th></tr></thead><tbody><?php $N=1; foreach($Alumnos as $Al): ?><tr><td class="Centro"><?= $N++ ?></td><td><?= HExpCal($Al['NombreCompleto']) ?></td><td class="Centro Negrita"><?= FormatoCal($Al['Calificacion']) ?></td></tr><?php endforeach; if(!$Alumnos): ?><tr><td colspan="3" class="Centro">Sin alumnos registrados.</td></tr><?php endif; ?></tbody></table></div>
<div class="Firma"><div class="FirmaLinea">Nombre y firma del docente</div></div>
</div>
</body></html>
