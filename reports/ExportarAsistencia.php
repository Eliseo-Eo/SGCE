<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/includes/SGCE_Pdf.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { http_response_code(403); exit('Acceso denegado.'); }

$AsignacionId = (int)($_GET['AsignacionId'] ?? 0);
$GrupoId = (int)($_GET['GrupoId'] ?? 0);
$Tipo = (($_GET['Tipo'] ?? 'Excel') === 'Pdf') ? 'Pdf' : 'Excel';
$FechaInicio = trim((string)($_GET['FechaInicio'] ?? ''));
$FechaFin = trim((string)($_GET['FechaFin'] ?? ''));
$RangoParam = (string)($_GET['Rango'] ?? '');

$Rango = ($RangoParam === 'Hoy' || ($RangoParam === '' && $FechaInicio === '' && $FechaFin === '')) ? 'Hoy' : 'Todas';
$CicloId = (int)($_GET['CicloId'] ?? 0);
if ($CicloId > 0 && ($FechaInicio === '' || $FechaFin === '')) {
    $StmtCicloFiltro = $Pdo->prepare('SELECT FechaInicio, FechaFin FROM CiclosEscolares WHERE Id = ? AND Activo = 1 LIMIT 1');
    $StmtCicloFiltro->execute([$CicloId]);
    $CicloFiltro = $StmtCicloFiltro->fetch();
    if ($CicloFiltro) { $FechaInicio = $CicloFiltro['FechaInicio']; $FechaFin = $CicloFiltro['FechaFin']; }
}
$TieneRango = preg_match('/^\d{4}-\d{2}-\d{2}$/', $FechaInicio) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $FechaFin);
if ($Rango !== 'Hoy' && !$TieneRango) { http_response_code(400); exit('Selecciona fecha de inicio y fecha fin válidas.'); }
if ($TieneRango && $FechaInicio > $FechaFin) { http_response_code(400); exit('La fecha de inicio no puede ser mayor que la fecha fin.'); }
if ($AsignacionId <= 0 && $GrupoId <= 0) { http_response_code(400); exit('Selecciona un grupo o una asignación.'); }

function HAsis($Texto){ return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function ArchivoSeguroAsis($Texto){ $Texto=preg_replace('/[^A-Za-z0-9_\-]/','',str_replace(' ','_', (string)$Texto)); return $Texto!==''?$Texto:'Reporte'; }
function EstadoAsis($E){ return ['A'=>'ASISTENCIA','F'=>'FALTA','R'=>'RETARDO','J'=>'JUSTIFICANTE'][$E] ?? $E; }
$ConfigReporte = SgceObtenerConfiguracion($Pdo);
$ColorReporte = SgceColorInstitucional($Pdo);
function EstilosAsis($Landscape=false){ global $ColorReporte; ?>
<style>
:root{--ReportColor:<?= HAsis($ColorReporte ?? '#97051E') ?>;}
@page{size:letter <?= $Landscape?'landscape':'' ?>;margin:1.1cm}*{box-sizing:border-box}body{margin:0;background:#eef1f5;font-family:Arial,'Segoe UI',sans-serif;color:#1f2937;font-size:12px}.ReportSheet{width:100%;max-width:<?= $Landscape?'1180':'950' ?>px;margin:22px auto;padding:24px;background:#fff;border:1px solid #e5e7eb;border-radius:18px;box-shadow:0 18px 45px rgba(15,23,42,.10)}.Header{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;border-bottom:4px solid var(--ReportColor);margin-bottom:16px;padding-bottom:12px}.Header h2{margin:0;color:var(--ReportColor);font-size:24px;font-weight:900}.SchoolName{margin:0 0 4px;color:#111827;font-size:13px;font-weight:900;text-transform:uppercase}.SchoolMeta{margin:0 0 5px;color:#6b7280;font-size:10.5px;font-weight:700;text-transform:uppercase}.Header p{margin:5px 0 0;color:#4b5563;font-weight:700}.HeaderTag{padding:7px 12px;border:1px solid #ead5da;background:#fff7f8;border-radius:999px;color:var(--ReportColor);font-weight:900;white-space:nowrap}.TablaWrap{border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}table{width:100%;border-collapse:collapse}th{background:var(--ReportColor);color:#fff;padding:9px 8px;border:1px solid var(--ReportColor);text-transform:uppercase;font-size:11px;letter-spacing:.25px}td{padding:7px 8px;border:1px solid #e5e7eb}tbody tr:nth-child(even){background:#f9fafb}.Centro{text-align:center}.Fecha{background:#f3f4f6;font-weight:900;color:var(--ReportColor)}.Badge{display:inline-block;min-width:100px;padding:4px 8px;border-radius:999px;font-weight:900;font-size:10px}.A{background:#dcfce7;color:#166534}.F{background:#fee2e2;color:#991b1b}.R{background:#fef3c7;color:#92400e}.J{background:#dbeafe;color:#1e40af}@media print{body{background:#fff}.ReportSheet{max-width:none;margin:0;padding:0;border:0;border-radius:0;box-shadow:none}th{background:var(--ReportColor)!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}.Badge,.Fecha,tbody tr:nth-child(even),.HeaderTag{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
<?php }

$FiltroSql = '';$ParamsFecha=[];$TextoRango='TODAS LAS FECHAS';
if ($Rango === 'Hoy') { $FiltroSql=' AND Asis.FechaDia = CURDATE() '; $TextoRango='HOY'; }
if ($Rango !== 'Hoy' && $TieneRango) { $FiltroSql=' AND Asis.FechaDia BETWEEN ? AND ? '; $ParamsFecha=[$FechaInicio,$FechaFin]; $TextoRango=$FechaInicio.' A '.$FechaFin; }

$Modo = $GrupoId > 0 ? 'Grupo' : 'Asignacion';
if ($Modo === 'Grupo') {
    if (!SgcePuedeAdministrarReportes($UserSession)) { http_response_code(403); exit('No tienes permiso.'); }
    $Stmt=$Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Id=? AND Activo=1 LIMIT 1");$Stmt->execute([$GrupoId]);$Info=$Stmt->fetch(); if(!$Info){ http_response_code(404); exit('Grupo no encontrado.'); }
    $Sql="SELECT Asis.FechaDia, DATE_FORMAT(Asis.FechaDia,'%d/%m/%Y') AS FechaTexto, Al.NombreCompleto, Asg.MateriaNombre, U.NombreCompleto AS Maestro, Asis.Estado FROM Asistencias Asis JOIN Alumnos Al ON Asis.AlumnoId=Al.Id JOIN Asignaciones Asg ON Asis.AsignacionId=Asg.Id JOIN Usuarios U ON Asg.MaestroId=U.Id WHERE Asg.GrupoId=? $FiltroSql ORDER BY Asis.FechaDia DESC, Al.NombreCompleto ASC, Asg.MateriaNombre ASC";
    $TituloArchivo='Asistencia_Grupo_'.ArchivoSeguroAsis($Info['Grado'].$Info['Grupo'].'_'.$Info['Turno']);
    $Params=array_merge([$GrupoId],$ParamsFecha); $Cols=6; $Landscape=true;
} else {
    $Stmt=$Pdo->prepare("SELECT A.Id, A.MateriaNombre, A.MaestroId, G.Grado, G.Grupo, G.Turno FROM Asignaciones A JOIN Grupos G ON A.GrupoId=G.Id WHERE A.Id=? AND A.Activo=1 LIMIT 1");$Stmt->execute([$AsignacionId]);$Info=$Stmt->fetch(); if(!$Info){ http_response_code(404); exit('Asignación no encontrada.'); }
    if(SgceTieneRol($UserSession, ['maestro'])){ if((int)$UserSession['Id'] !== (int)$Info['MaestroId']){ http_response_code(403); exit('No tienes permiso.'); } }
    elseif(!SgcePuedeAdministrarReportes($UserSession)){ http_response_code(403); exit('No tienes permiso.'); }
    $Sql="SELECT Asis.FechaDia, DATE_FORMAT(Asis.FechaDia,'%d/%m/%Y') AS FechaTexto, Al.NombreCompleto, Asis.Estado FROM Asistencias Asis JOIN Alumnos Al ON Asis.AlumnoId=Al.Id WHERE Asis.AsignacionId=? $FiltroSql ORDER BY Asis.FechaDia DESC, Al.NombreCompleto ASC";
    $TituloArchivo='Asistencia_'.ArchivoSeguroAsis($Info['MateriaNombre'].'_'.$Info['Grado'].$Info['Grupo']);
    $Params=array_merge([$AsignacionId],$ParamsFecha); $Cols=4; $Landscape=false;
}
if($Tipo==='Excel'){header('Content-Type: application/vnd.ms-excel; charset=utf-8');header("Content-Disposition: attachment; filename={$TituloArchivo}.xls");echo "\xEF\xBB\xBF";}
$Stmt=$Pdo->prepare($Sql);$Stmt->execute($Params);
if ($Tipo === 'Pdf') {
    $FilasPdf = [];
    while ($R = $Stmt->fetch()) {
        if ($Modo === 'Grupo') {
            $FilasPdf[] = [$R['FechaTexto'], $R['NombreCompleto'], $R['MateriaNombre'], $R['Maestro'], EstadoAsis($R['Estado'])];
        } else {
            $FilasPdf[] = [$R['FechaTexto'], $R['NombreCompleto'], EstadoAsis($R['Estado'])];
        }
    }
    $SubtituloPdf = ($Modo === 'Grupo')
        ? 'Grupo: ' . $Info['Grado'] . ' ' . $Info['Grupo'] . ' ' . $Info['Turno'] . ' | Rango: ' . $TextoRango
        : 'Asignación: ' . $Info['MateriaNombre'] . ' | Grupo: ' . $Info['Grado'] . ' ' . $Info['Grupo'] . ' ' . $Info['Turno'] . ' | Rango: ' . $TextoRango;
    if ($Modo === 'Grupo') {
        SgcePdfRespuestaTabla($Pdo, 'Reporte de asistencia', $SubtituloPdf, ['Fecha', 'Alumno', 'Materia', 'Docente', 'Estado'], $FilasPdf, $TituloArchivo, 'L', [78, 210, 150, 170, 90]);
    } else {
        SgcePdfRespuestaTabla($Pdo, 'Reporte de asistencia', $SubtituloPdf, ['Fecha', 'Alumno', 'Estado'], $FilasPdf, $TituloArchivo, 'P', [95, 340, 100]);
    }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= HAsis($TituloArchivo) ?></title><?php EstilosAsis($Landscape); ?></head><body>
<div class="ReportSheet">
<div class="Header"><div><div class="SchoolName"><?= HAsis($ConfigReporte['NombreEscuela']) ?></div><div class="SchoolMeta"><?= HAsis(trim(($ConfigReporte['ClaveCentroTrabajo'] ? 'CCT: '.$ConfigReporte['ClaveCentroTrabajo'].' · ' : '').($ConfigReporte['MunicipioEstado'] ?? ''))) ?></div><h2>Reporte de asistencia</h2><p><?= $Modo==='Grupo' ? 'Grupo: '.HAsis($Info['Grado'].' '.$Info['Grupo'].' '.$Info['Turno']) : 'Asignación: '.HAsis($Info['MateriaNombre'].' · '.$Info['Grado'].' '.$Info['Grupo'].' '.$Info['Turno']) ?> · Rango: <?= HAsis($TextoRango) ?></p></div><div class="HeaderTag"><?= HAsis($Tipo) ?></div></div>
<div class="TablaWrap"><table><thead><tr><?php if($Modo==='Grupo'):?><th>#</th><th>Alumno</th><th>Materia</th><th>Docente</th><th>Estado</th><th>Fecha</th><?php else:?><th>#</th><th>Alumno</th><th>Estado</th><th>Fecha</th><?php endif;?></tr></thead><tbody>
<?php $Fecha=null;$N=1;$Total=0; while($R=$Stmt->fetch()): if($Fecha!==$R['FechaDia']): $Fecha=$R['FechaDia'];$N=1;?><tr><td class="Fecha" colspan="<?= $Cols ?>">FECHA: <?= HAsis($R['FechaTexto']) ?></td></tr><?php endif; $Clase=HAsis($R['Estado']); ?><tr><td class="Centro"><?= $N++ ?></td><td><?= HAsis($R['NombreCompleto']) ?></td><?php if($Modo==='Grupo'):?><td><?= HAsis($R['MateriaNombre']) ?></td><td><?= HAsis($R['Maestro']) ?></td><?php endif;?><td class="Centro"><span class="Badge <?= $Clase ?>"><?= HAsis(EstadoAsis($R['Estado'])) ?></span></td><td class="Centro"><?= HAsis($R['FechaTexto']) ?></td></tr><?php $Total++; if($Total%500===0){flush();} endwhile; if($Total===0):?><tr><td colspan="<?= $Cols ?>" class="Centro">Sin registros de asistencia.</td></tr><?php endif;?></tbody></table></div>
</div>
</body></html>
