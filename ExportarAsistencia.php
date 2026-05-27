<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { die('Acceso denegado.'); }

$AsignacionId = (int)($_GET['AsignacionId'] ?? 0);
$GrupoId = (int)($_GET['GrupoId'] ?? 0);
$Tipo = (($_GET['Tipo'] ?? 'Excel') === 'Pdf') ? 'Pdf' : 'Excel';
$Rango = (($_GET['Rango'] ?? 'Todas') === 'Hoy') ? 'Hoy' : 'Todas';
$FechaInicio = trim((string)($_GET['FechaInicio'] ?? ''));
$FechaFin = trim((string)($_GET['FechaFin'] ?? ''));
$TieneRango = preg_match('/^\d{4}-\d{2}-\d{2}$/', $FechaInicio) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $FechaFin);
if ($AsignacionId <= 0 && $GrupoId <= 0) { die('Parámetros inválidos.'); }

function HAsis($Texto){ return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function ArchivoSeguroAsis($Texto){ $Texto=preg_replace('/[^A-Za-z0-9_\-]/','',str_replace(' ','_', (string)$Texto)); return $Texto!==''?$Texto:'Reporte'; }
function EstadoAsis($E){ return ['A'=>'ASISTENCIA','F'=>'FALTA','R'=>'RETARDO','J'=>'JUSTIFICANTE'][$E] ?? $E; }
function EstilosAsis($Landscape=false){ ?>
<style>
@page{size:letter <?= $Landscape?'landscape':'' ?>;margin:1.1cm}body{font-family:Arial,'Segoe UI',sans-serif;color:#1f2937;font-size:12px}.NoPrint{padding:12px;margin-bottom:16px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px}.Header{border-bottom:4px solid #7A0818;margin-bottom:16px;padding-bottom:10px}.Header h2{margin:0;color:#7A0818;font-weight:800}.Header p{margin:4px 0 0;color:#4b5563}table{width:100%;border-collapse:collapse}th{background:#7A0818;color:#fff;padding:8px;border:1px solid #d1d5db;text-transform:uppercase;font-size:11px}td{padding:7px;border:1px solid #e5e7eb}tbody tr:nth-child(even){background:#f9fafb}.Centro{text-align:center}.Fecha{background:#f3f4f6;font-weight:800;color:#7A0818}.Badge{display:inline-block;padding:4px 8px;border-radius:999px;font-weight:800}.A{background:#dcfce7;color:#166534}.F{background:#fee2e2;color:#991b1b}.R{background:#fef3c7;color:#92400e}.J{background:#dbeafe;color:#1e40af}@media print{.NoPrint{display:none}th{background:#7A0818!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
<?php }

$FiltroSql = '';$ParamsFecha=[];$TextoRango='TODAS LAS FECHAS';
if ($Rango === 'Hoy') { $FiltroSql=' AND Asis.FechaDia = CURDATE() '; $TextoRango='HOY'; }
if ($Rango !== 'Hoy' && $TieneRango) { $FiltroSql=' AND Asis.FechaDia BETWEEN ? AND ? '; $ParamsFecha=[$FechaInicio,$FechaFin]; $TextoRango=$FechaInicio.' A '.$FechaFin; }

$Modo = $GrupoId > 0 ? 'Grupo' : 'Asignacion';
if ($Modo === 'Grupo') {
    if (!SgcePuedeAdministrarReportes($UserSession)) { die('No tienes permiso.'); }
    $Stmt=$Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Id=? AND Activo=1 LIMIT 1");$Stmt->execute([$GrupoId]);$Info=$Stmt->fetch(); if(!$Info){die('Grupo no encontrado.');}
    $Sql="SELECT Asis.FechaDia, DATE_FORMAT(Asis.FechaDia,'%d/%m/%Y') AS FechaTexto, Al.NombreCompleto, Asg.MateriaNombre, U.NombreCompleto AS Maestro, Asis.Estado FROM Asistencias Asis JOIN Alumnos Al ON Asis.AlumnoId=Al.Id JOIN Asignaciones Asg ON Asis.AsignacionId=Asg.Id JOIN Usuarios U ON Asg.MaestroId=U.Id WHERE Asg.GrupoId=? $FiltroSql ORDER BY Asis.FechaDia DESC, Al.NombreCompleto ASC, Asg.MateriaNombre ASC";
    $TituloArchivo='Asistencia_Grupo_'.ArchivoSeguroAsis($Info['Grado'].$Info['Grupo'].'_'.$Info['Turno']);
    $Params=array_merge([$GrupoId],$ParamsFecha); $Cols=6; $Landscape=true;
} else {
    $Stmt=$Pdo->prepare("SELECT A.Id, A.MateriaNombre, A.MaestroId, G.Grado, G.Grupo, G.Turno FROM Asignaciones A JOIN Grupos G ON A.GrupoId=G.Id WHERE A.Id=? AND A.Activo=1 LIMIT 1");$Stmt->execute([$AsignacionId]);$Info=$Stmt->fetch(); if(!$Info){die('Asignación no encontrada.');}
    if($UserSession['Rol']==='maestro' && (int)$UserSession['Id'] !== (int)$Info['MaestroId']){die('No tienes permiso.');}
    $Sql="SELECT Asis.FechaDia, DATE_FORMAT(Asis.FechaDia,'%d/%m/%Y') AS FechaTexto, Al.NombreCompleto, Asis.Estado FROM Asistencias Asis JOIN Alumnos Al ON Asis.AlumnoId=Al.Id WHERE Asis.AsignacionId=? $FiltroSql ORDER BY Asis.FechaDia DESC, Al.NombreCompleto ASC";
    $TituloArchivo='Asistencia_'.ArchivoSeguroAsis($Info['MateriaNombre'].'_'.$Info['Grado'].$Info['Grupo']);
    $Params=array_merge([$AsignacionId],$ParamsFecha); $Cols=4; $Landscape=false;
}
if($Tipo==='Excel'){header('Content-Type: application/vnd.ms-excel; charset=utf-8');header("Content-Disposition: attachment; filename={$TituloArchivo}.xls");echo "\xEF\xBB\xBF";}
$Stmt=$Pdo->prepare($Sql);$Stmt->execute($Params);
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= HAsis($TituloArchivo) ?></title><?php EstilosAsis($Landscape); ?></head><body>
<?php if($Tipo==='Pdf'):?><div class="NoPrint"><button onclick="window.print()">Imprimir / guardar PDF</button></div><?php endif; ?>
<div class="Header"><h2>Reporte de asistencia</h2><p><?= $Modo==='Grupo' ? 'Grupo: '.HAsis($Info['Grado'].' '.$Info['Grupo'].' '.$Info['Turno']) : 'Asignación: '.HAsis($Info['MateriaNombre'].' · '.$Info['Grado'].' '.$Info['Grupo'].' '.$Info['Turno']) ?> · Rango: <?= HAsis($TextoRango) ?></p></div>
<table><thead><tr><?php if($Modo==='Grupo'):?><th>#</th><th>Alumno</th><th>Materia</th><th>Docente</th><th>Estado</th><th>Fecha</th><?php else:?><th>#</th><th>Alumno</th><th>Estado</th><th>Fecha</th><?php endif;?></tr></thead><tbody>
<?php $Fecha=null;$N=1;$Total=0; while($R=$Stmt->fetch()): if($Fecha!==$R['FechaDia']): $Fecha=$R['FechaDia'];$N=1;?><tr><td class="Fecha" colspan="<?= $Cols ?>">FECHA: <?= HAsis($R['FechaTexto']) ?></td></tr><?php endif; $Clase=HAsis($R['Estado']); ?><tr><td class="Centro"><?= $N++ ?></td><td><?= HAsis($R['NombreCompleto']) ?></td><?php if($Modo==='Grupo'):?><td><?= HAsis($R['MateriaNombre']) ?></td><td><?= HAsis($R['Maestro']) ?></td><?php endif;?><td class="Centro"><span class="Badge <?= $Clase ?>"><?= HAsis(EstadoAsis($R['Estado'])) ?></span></td><td class="Centro"><?= HAsis($R['FechaTexto']) ?></td></tr><?php $Total++; if($Total%500===0){flush();} endwhile; if($Total===0):?><tr><td colspan="<?= $Cols ?>" class="Centro">Sin registros de asistencia.</td></tr><?php endif;?></tbody></table>
</body></html>
