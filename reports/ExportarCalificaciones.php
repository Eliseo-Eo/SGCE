<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/includes/SGCE_Pdf.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { http_response_code(403); exit('Acceso denegado.'); }

$AsignacionId = (int)($_GET['AsignacionId'] ?? 0);
$GrupoId = (int)($_GET['GrupoId'] ?? 0);
$Tipo = (($_GET['Tipo'] ?? 'Excel') === 'Pdf') ? 'Pdf' : 'Excel';
$PeriodoId = SgcePeriodoActualId($Pdo, $_GET['PeriodoId'] ?? 0);

function HExpCal($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function ArchivoSeguroCal($Texto) {
    $Texto = preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace(' ', '_', (string)$Texto));
    return $Texto !== '' ? $Texto : 'Reporte';
}
function FormatoCal($Valor) { return $Valor !== null && $Valor !== '' ? number_format((float)$Valor, 2) : '-'; }
function SgceCalificacionesPrepararSalidaMasiva(): void {
    @set_time_limit(180);
    @ini_set('memory_limit', '512M');
    @ini_set('zlib.output_compression', '0');
    if (!headers_sent()) { header('X-Accel-Buffering: no'); }
}
SgceCalificacionesPrepararSalidaMasiva();
function SgceCalificacionesValidarPdfMasivo(int $Unidades, int $Limite = 5000): void {
    if ($Unidades <= $Limite) { return; }
    http_response_code(413);
    exit('El reporte es demasiado grande para PDF. Usa Excel o reduce el filtro.');
}

function SgceCalificacionesEmitirExcel($TituloArchivo) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $TituloArchivo . '.xls');
    echo "\xEF\xBB\xBF";
}

$ConfigReporte = SgceObtenerConfiguracion($Pdo);
$ColorReporte = SgceColorInstitucional($Pdo);
function EstilosReporteCal($Landscape = false) { global $ColorReporte; ?>
<style>
:root{--ReportColor:<?= HExpCal($ColorReporte ?? '#97051E') ?>;}
@page{size:letter <?= $Landscape ? 'landscape' : '' ?>;margin:1.1cm}*{box-sizing:border-box}body{margin:0;background:#eef1f5;font-family:Arial,'Segoe UI',sans-serif;color:#1f2937;font-size:12px}.ReportSheet{width:100%;max-width:<?= $Landscape ? '1180' : '950' ?>px;margin:22px auto;padding:24px;background:#fff;border:1px solid #e5e7eb;border-radius:18px;box-shadow:0 18px 45px rgba(15,23,42,.10)}.Header{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;border-bottom:4px solid var(--ReportColor);margin-bottom:16px;padding-bottom:12px}.Header h2{margin:0;color:var(--ReportColor);font-size:24px;font-weight:900}.SchoolName{margin:0 0 4px;color:#111827;font-size:13px;font-weight:900;text-transform:uppercase}.SchoolMeta{margin:0 0 5px;color:#6b7280;font-size:10.5px;font-weight:700;text-transform:uppercase}.Header p{margin:5px 0 0;color:#4b5563;font-weight:700}.HeaderTag{padding:7px 12px;border:1px solid #ead5da;background:#fff7f8;border-radius:999px;color:var(--ReportColor);font-weight:900;white-space:nowrap}.TablaWrap{border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}table{width:100%;border-collapse:collapse}th{background:var(--ReportColor);color:#fff;padding:9px 8px;border:1px solid var(--ReportColor);text-transform:uppercase;font-size:11px;letter-spacing:.25px}td{padding:7px 8px;border:1px solid #e5e7eb}tbody tr:nth-child(even){background:#f9fafb}.Centro{text-align:center}.Negrita{font-weight:800}.Muted{color:#6b7280}.Firma{margin-top:55px;text-align:center}.FirmaLinea{width:290px;margin:auto;border-top:1px solid #374151;padding-top:7px;color:#374151}@media print{body{background:#fff}.ReportSheet{max-width:none;margin:0;padding:0;border:0;border-radius:0;box-shadow:none}th{background:var(--ReportColor)!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}tbody tr:nth-child(even),.HeaderTag{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
<?php }

$StmtPeriodo = $Pdo->prepare('SELECT P.Id, P.Nombre, P.CicloId, P.OfertaId, C.Nombre AS Ciclo FROM PeriodosEvaluacion P JOIN CiclosEscolares C ON P.CicloId = C.Id WHERE P.Id = ? LIMIT 1');
$StmtPeriodo->execute([$PeriodoId]);
$Periodo = $StmtPeriodo->fetch();
if (!$Periodo) { http_response_code(400); exit('Periodo no válido.'); }

$Modo = 'General';
if ($AsignacionId > 0) { $Modo = 'Asignacion'; }
elseif ($GrupoId > 0) { $Modo = 'Grupo'; }

if ($Modo === 'Grupo' || $Modo === 'General') {
    if (!SgcePuedeAdministrarReportes($UserSession)) { http_response_code(403); exit('No tienes permiso.'); }
}

if ($Modo === 'General') {
    $TituloArchivo = 'Calificaciones_General_' . ArchivoSeguroCal($Periodo['Ciclo'] . '_' . $Periodo['Nombre']);
    $Sql = "SELECT G.Grado, G.Grupo, G.Turno, A.MateriaNombre, U.NombreCompleto AS Maestro,
            COUNT(DISTINCT Al.Id) AS Alumnos,
            COUNT(C.Calificacion) AS Capturadas,
            ROUND(AVG(C.Calificacion), 2) AS Promedio
        FROM Asignaciones A
        INNER JOIN Grupos G ON G.Id = A.GrupoId AND G.CicloId = A.CicloId
        INNER JOIN Usuarios U ON U.Id = A.MaestroId
        LEFT JOIN AlumnoInscripciones AI ON AI.CicloId = A.CicloId AND AI.GrupoId = G.Id AND AI.Estado = 'INSCRITO'
        LEFT JOIN Alumnos Al ON Al.Id = AI.AlumnoId AND Al.Activo = 1
        LEFT JOIN Calificaciones C ON C.AsignacionId = A.Id AND C.AlumnoId = Al.Id AND C.PeriodoId = ?
        WHERE A.CicloId = ? AND G.OfertaId = ? AND A.Activo = 1 AND G.Activo = 1 AND U.Activo = 1
        GROUP BY A.Id, G.Grado, G.Grupo, G.Turno, A.MateriaNombre, U.NombreCompleto
        ORDER BY G.Turno, G.Grado, G.Grupo, A.MateriaNombre";
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute([$PeriodoId, (int)$Periodo['CicloId'], (int)$Periodo['OfertaId']]);
    $Rows = $Stmt->fetchAll();

    if ($Tipo === 'Excel') { SgceCalificacionesEmitirExcel($TituloArchivo); }
    if ($Tipo === 'Pdf') {
        $FilasPdf = [];
        $Npdf = 1;
        foreach ($Rows as $R) {
            $FilasPdf[] = [(string)$Npdf++, $R['Grado'] . $R['Grupo'] . ' ' . $R['Turno'], $R['MateriaNombre'], $R['Maestro'], (string)(int)$R['Alumnos'], (string)(int)$R['Capturadas'], FormatoCal($R['Promedio'])];
        }
        SgceCalificacionesValidarPdfMasivo(count($FilasPdf), 1200);
        $SubtituloPdf = 'Periodo: ' . $Periodo['Nombre'] . ' ' . $Periodo['Ciclo'] . ' | Resumen general por asignación';
        SgcePdfRespuestaTabla($Pdo, 'Reporte general de calificaciones', $SubtituloPdf, ['#', 'Grupo', 'Materia', 'Docente', 'Alumnos', 'Capturadas', 'Promedio'], $FilasPdf, $TituloArchivo, 'L', [34, 88, 160, 170, 70, 80, 80]);
    }
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= HExpCal($TituloArchivo) ?></title><?php EstilosReporteCal(true); ?></head><body>
<div class="ReportSheet">
<div class="Header"><div><div class="SchoolName"><?= HExpCal($ConfigReporte['NombreEscuela']) ?></div><div class="SchoolMeta"><?= HExpCal(trim(($ConfigReporte['ClaveCentroTrabajo'] ? 'CCT: '.$ConfigReporte['ClaveCentroTrabajo'].' · ' : '').($ConfigReporte['MunicipioEstado'] ?? ''))) ?></div><h2>Reporte general de calificaciones</h2><p>Periodo: <?= HExpCal($Periodo['Nombre'].' '.$Periodo['Ciclo']) ?> · Resumen por asignación</p></div><div class="HeaderTag"><?= HExpCal($Tipo) ?></div></div>
<div class="TablaWrap"><table><thead><tr><th>#</th><th>Grupo</th><th>Materia</th><th>Docente</th><th>Alumnos</th><th>Capturadas</th><th>Promedio</th></tr></thead><tbody>
<?php $N=1; foreach($Rows as $R): ?><tr><td class="Centro"><?= $N++ ?></td><td class="Centro Negrita"><?= HExpCal($R['Grado'].$R['Grupo'].' '.$R['Turno']) ?></td><td><?= HExpCal($R['MateriaNombre']) ?></td><td><?= HExpCal($R['Maestro']) ?></td><td class="Centro"><?= (int)$R['Alumnos'] ?></td><td class="Centro"><?= (int)$R['Capturadas'] ?></td><td class="Centro Negrita"><?= FormatoCal($R['Promedio']) ?></td></tr><?php endforeach; if(!$Rows): ?><tr><td colspan="7" class="Centro">Sin asignaciones registradas.</td></tr><?php endif; ?>
</tbody></table></div>
</div>
</body></html><?php
    exit;
}

if ($Modo === 'Grupo') {
    $StmtGrupo = $Pdo->prepare('SELECT Id, CicloId, Grado, Grupo, Turno FROM Grupos WHERE Id = ? AND CicloId = ? AND OfertaId = ? AND Activo = 1 LIMIT 1');
    $StmtGrupo->execute([$GrupoId, (int)$Periodo['CicloId'], (int)$Periodo['OfertaId']]);
    $Grupo = $StmtGrupo->fetch();
    if (!$Grupo) { http_response_code(404); exit('Grupo no encontrado.'); }

    $StmtAsignaciones = $Pdo->prepare('SELECT A.Id, A.MateriaNombre, U.NombreCompleto AS Maestro FROM Asignaciones A JOIN Usuarios U ON A.MaestroId = U.Id WHERE A.CicloId = ? AND A.GrupoId = ? AND A.Activo = 1 AND U.Activo = 1 ORDER BY A.MateriaNombre ASC');
    $StmtAsignaciones->execute([(int)$Periodo['CicloId'], $GrupoId]);
    $Asignaciones = $StmtAsignaciones->fetchAll();

    $StmtAlumnos = $Pdo->prepare("SELECT Al.Id, Al.NombreCompleto FROM AlumnoInscripciones AI INNER JOIN Alumnos Al ON Al.Id = AI.AlumnoId AND Al.Activo = 1 WHERE AI.CicloId = ? AND AI.GrupoId = ? AND AI.Estado = 'INSCRITO' ORDER BY Al.NombreCompleto ASC");
    $StmtAlumnos->execute([(int)$Periodo['CicloId'], $GrupoId]);
    $Alumnos = $StmtAlumnos->fetchAll();

    $Calificaciones = [];
    if ($Asignaciones && $Alumnos) {
        $StmtCal = $Pdo->prepare('SELECT AlumnoId, AsignacionId, Calificacion FROM Calificaciones WHERE PeriodoId = ? AND AsignacionId IN (SELECT Id FROM Asignaciones WHERE CicloId = ? AND GrupoId = ? AND Activo = 1)');
        $StmtCal->execute([$PeriodoId, (int)$Periodo['CicloId'], $GrupoId]);
        foreach ($StmtCal->fetchAll() as $Row) { $Calificaciones[(int)$Row['AlumnoId']][(int)$Row['AsignacionId']] = $Row['Calificacion']; }
    }

    $TituloArchivo = 'Calificaciones_Grupo_' . ArchivoSeguroCal($Grupo['Grado'].$Grupo['Grupo'].'_'.$Grupo['Turno']);
    if ($Tipo === 'Excel') { SgceCalificacionesEmitirExcel($TituloArchivo); }
    if ($Tipo === 'Pdf') {
        $ColumnasPdf = ['#', 'Alumno'];
        foreach ($Asignaciones as $A) { $ColumnasPdf[] = $A['MateriaNombre']; }
        $ColumnasPdf[] = 'Promedio';
        $FilasPdf = [];
        $Npdf = 1;
        foreach ($Alumnos as $Al) {
            $FilaPdf = [(string)$Npdf++, $Al['NombreCompleto']];
            $SumaPdf = 0; $CuentaPdf = 0;
            foreach ($Asignaciones as $A) {
                $Val = $Calificaciones[(int)$Al['Id']][(int)$A['Id']] ?? null;
                if ($Val !== null) { $SumaPdf += (float)$Val; $CuentaPdf++; }
                $FilaPdf[] = FormatoCal($Val);
            }
            $FilaPdf[] = $CuentaPdf > 0 ? number_format($SumaPdf / $CuentaPdf, 2) : '-';
            $FilasPdf[] = $FilaPdf;
        }
        SgceCalificacionesValidarPdfMasivo(count($Alumnos) * max(1, count($Asignaciones)), 6000);
        $Disponible = 720;
        $AnchosPdf = [34, 190];
        $Restantes = max(1, count($ColumnasPdf) - 3);
        $AnchoMateria = max(55, min(90, ($Disponible - 34 - 190 - 70) / $Restantes));
        for ($I = 0; $I < $Restantes; $I++) { $AnchosPdf[] = $AnchoMateria; }
        $AnchosPdf[] = 70;
        $SubtituloPdf = 'Grupo: ' . $Grupo['Grado'] . ' ' . $Grupo['Grupo'] . ' ' . $Grupo['Turno'] . ' | Periodo: ' . $Periodo['Nombre'] . ' ' . $Periodo['Ciclo'];
        SgcePdfRespuestaTabla($Pdo, 'Reporte de calificaciones por grupo', $SubtituloPdf, $ColumnasPdf, $FilasPdf, $TituloArchivo, 'L', $AnchosPdf);
    }
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= HExpCal($TituloArchivo) ?></title><?php EstilosReporteCal(true); ?></head><body>
<div class="ReportSheet">
<div class="Header"><div><div class="SchoolName"><?= HExpCal($ConfigReporte['NombreEscuela']) ?></div><div class="SchoolMeta"><?= HExpCal(trim(($ConfigReporte['ClaveCentroTrabajo'] ? 'CCT: '.$ConfigReporte['ClaveCentroTrabajo'].' · ' : '').($ConfigReporte['MunicipioEstado'] ?? ''))) ?></div><h2>Reporte de calificaciones por grupo</h2><p>Grupo: <?= HExpCal($Grupo['Grado'].' '.$Grupo['Grupo'].' '.$Grupo['Turno']) ?> · Periodo: <?= HExpCal($Periodo['Nombre'].' '.$Periodo['Ciclo']) ?></p></div><div class="HeaderTag"><?= HExpCal($Tipo) ?></div></div>
<div class="TablaWrap"><table><thead><tr><th>#</th><th>Alumno</th><?php foreach($Asignaciones as $A): ?><th><?= HExpCal($A['MateriaNombre']) ?></th><?php endforeach; ?><th>Promedio</th></tr></thead><tbody>
<?php $N=1; foreach($Alumnos as $Al): $Suma=0; $Cuenta=0; ?><tr><td class="Centro"><?= $N++ ?></td><td><?= HExpCal($Al['NombreCompleto']) ?></td><?php foreach($Asignaciones as $A): $Val=$Calificaciones[(int)$Al['Id']][(int)$A['Id']] ?? null; if($Val!==null){$Suma+=(float)$Val;$Cuenta++;} ?><td class="Centro"><?= FormatoCal($Val) ?></td><?php endforeach; ?><td class="Centro Negrita"><?= $Cuenta>0 ? number_format($Suma/$Cuenta,2) : '-' ?></td></tr><?php endforeach; if(!$Alumnos): ?><tr><td colspan="<?= count($Asignaciones)+3 ?>" class="Centro">Sin alumnos registrados.</td></tr><?php endif; ?>
</tbody></table></div>
</div>
</body></html><?php
    exit;
}

$Stmt = $Pdo->prepare('SELECT A.Id, A.CicloId, A.MateriaNombre, A.MaestroId, G.Grado, G.Grupo, G.Turno, G.Id AS GrupoId, U.NombreCompleto AS Maestro FROM Asignaciones A JOIN Grupos G ON A.GrupoId = G.Id AND G.CicloId = A.CicloId JOIN Usuarios U ON A.MaestroId = U.Id WHERE A.Id = ? AND A.CicloId = ? AND G.OfertaId = ? AND A.Activo = 1 AND G.Activo = 1 AND U.Activo = 1 LIMIT 1');
$Stmt->execute([$AsignacionId, (int)$Periodo['CicloId'], (int)$Periodo['OfertaId']]);
$Info = $Stmt->fetch();
if (!$Info) { http_response_code(404); exit('Asignación no encontrada.'); }
if (SgceTieneRol($UserSession, ['maestro'])) {
    if ((int)$UserSession['Id'] !== (int)$Info['MaestroId']) { http_response_code(403); exit('No tienes permiso.'); }
} elseif (!SgcePuedeAdministrarReportes($UserSession)) {
    http_response_code(403); exit('No tienes permiso.');
}

$StmtAlumnos = $Pdo->prepare("SELECT Al.NombreCompleto, C.Calificacion FROM AlumnoInscripciones AI INNER JOIN Alumnos Al ON Al.Id = AI.AlumnoId AND Al.Activo = 1 LEFT JOIN Calificaciones C ON C.AlumnoId = Al.Id AND C.AsignacionId = ? AND C.PeriodoId = ? WHERE AI.CicloId = ? AND AI.GrupoId = ? AND AI.Estado = 'INSCRITO' ORDER BY Al.NombreCompleto ASC");
$StmtAlumnos->execute([$AsignacionId, $PeriodoId, (int)$Periodo['CicloId'], $Info['GrupoId']]);
$Alumnos = $StmtAlumnos->fetchAll();
$TituloArchivo = 'Calificaciones_' . ArchivoSeguroCal($Info['MateriaNombre'].'_'.$Info['Grado'].$Info['Grupo']);
if ($Tipo === 'Excel') { SgceCalificacionesEmitirExcel($TituloArchivo); }
if ($Tipo === 'Pdf') {
    $FilasPdf = [];
    $Npdf = 1;
    foreach ($Alumnos as $Al) { $FilasPdf[] = [(string)$Npdf++, $Al['NombreCompleto'], FormatoCal($Al['Calificacion'])]; }
    SgceCalificacionesValidarPdfMasivo(count($FilasPdf), 1500);
    $SubtituloPdf = 'Materia: ' . $Info['MateriaNombre'] . ' | Grupo: ' . $Info['Grado'] . ' ' . $Info['Grupo'] . ' ' . $Info['Turno'] . ' | Docente: ' . $Info['Maestro'] . ' | Periodo: ' . $Periodo['Nombre'] . ' ' . $Periodo['Ciclo'];
    SgcePdfRespuestaTabla($Pdo, 'Reporte de calificaciones', $SubtituloPdf, ['#', 'Alumno', 'Calificación'], $FilasPdf, $TituloArchivo, 'P', [45, 390, 100]);
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= HExpCal($TituloArchivo) ?></title><?php EstilosReporteCal(false); ?></head><body>
<div class="ReportSheet">
<div class="Header"><div><div class="SchoolName"><?= HExpCal($ConfigReporte['NombreEscuela']) ?></div><div class="SchoolMeta"><?= HExpCal(trim(($ConfigReporte['ClaveCentroTrabajo'] ? 'CCT: '.$ConfigReporte['ClaveCentroTrabajo'].' · ' : '').($ConfigReporte['MunicipioEstado'] ?? ''))) ?></div><h2>Reporte de calificaciones</h2><p>Materia: <?= HExpCal($Info['MateriaNombre']) ?> · Grupo: <?= HExpCal($Info['Grado'].' '.$Info['Grupo'].' '.$Info['Turno']) ?> · Periodo: <?= HExpCal($Periodo['Nombre'].' '.$Periodo['Ciclo']) ?></p></div><div class="HeaderTag"><?= HExpCal($Tipo) ?></div></div>
<div class="TablaWrap"><table><thead><tr><th>#</th><th>Alumno</th><th>Calificación</th></tr></thead><tbody>
<?php $N=1; foreach($Alumnos as $Al): ?><tr><td class="Centro"><?= $N++ ?></td><td><?= HExpCal($Al['NombreCompleto']) ?></td><td class="Centro Negrita"><?= FormatoCal($Al['Calificacion']) ?></td></tr><?php endforeach; if(!$Alumnos): ?><tr><td colspan="3" class="Centro">Sin alumnos registrados.</td></tr><?php endif; ?>
</tbody></table></div>
</div>
</body></html>
