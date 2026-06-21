<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/services/ConductaService.php';
require_once dirname(__DIR__) . '/includes/SGCE_PublicConsultas.php';
require_once dirname(__DIR__) . '/includes/SGCE_Pdf.php';

SgcePublicoEnviarHeaders();
SgceEnviarHeadersNoCacheDescarga();

$ConsultaToken = SgcePublicoTokenDesdeGet();
$Error = '';

if ($ConsultaToken === '') {
    SgcePublicoRegistrarFallo($Pdo, 'exportar_asistencia_publica', 'TOKEN_INVALIDO', 4, 16, 15);
    http_response_code(403);
    exit('Consulta no válida. Realiza la búsqueda nuevamente.');
}

$ConsultaGuardada = SgcePublicoLeerTokenConsulta($ConsultaToken, 'asistencia');
if (!$ConsultaGuardada) {
    SgcePublicoRegistrarFallo($Pdo, 'exportar_asistencia_publica', 'TOKEN_EXPIRADO', 4, 16, 15);
    http_response_code(403);
    exit('La consulta expiró. Realiza la búsqueda nuevamente.');
}

$Datos = $ConsultaGuardada['Datos'] ?? [];
$NombreAlumno = SgceNormalizarMayusculas($Datos['NombreAlumno'] ?? '');
$ProgramaId = (int)($Datos['ProgramaId'] ?? 0);
$Grado = SgceNormalizarMayusculas($Datos['Grado'] ?? '');
$Grupo = SgcePublicoNormalizarGrupo($Datos['Grupo'] ?? '');
$Turno = SgceNormalizarMayusculas($Datos['Turno'] ?? '');
$FechaInicio = SgcePublicoNormalizarFecha($Datos['FechaInicio'] ?? date('Y-m-d'), date('Y-m-d'));
$FechaFin = SgcePublicoNormalizarFecha($Datos['FechaFin'] ?? date('Y-m-d'), date('Y-m-d'));

$RateKey = SgcePublicoRateKey($NombreAlumno, $ProgramaId, $Grado, $Grupo, $Turno);
if (!SgcePublicoRateDisponible($Pdo, 'exportar_asistencia_publica', $RateKey)) {
    http_response_code(429);
    exit('Demasiados intentos. Espera unos minutos e intenta nuevamente.');
}
[$FechaInicio, $FechaFin] = SgcePublicoValidarRangoFechas($FechaInicio, $FechaFin, $Error, 60);
if ($Error !== '') { http_response_code(400); exit($Error); }

$DatosAlumno = SgcePublicoBuscarAlumno($Pdo, $NombreAlumno, $ProgramaId, $Grado, $Grupo, $Turno, $Error);
if (!$DatosAlumno) {
    SgcePublicoRegistrarFallo($Pdo, 'exportar_asistencia_publica', $RateKey, 8, 24, 15);
    http_response_code(404);
    exit($Error ?: SgcePublicoMensajeNoEncontrado());
}

$Alumno = $DatosAlumno['Alumno'];
$InfoGrupo = $DatosAlumno['Grupo'];
$Resumen = SgcePublicoResumenAsistencia($Pdo, (int)$Alumno['Id'], (int)$InfoGrupo['Id'], $FechaInicio, $FechaFin);
$ConductaResumen = SgcePublicoResumenConducta($Pdo, (int)$Alumno['Id'], (int)$InfoGrupo['Id'], $FechaInicio, $FechaFin);

$FilasPdf = [];
foreach ($Resumen['Detalle'] as $D) {
    $FilasPdf[] = [$D['FechaTexto'], $D['MateriaNombre'], $D['Maestro'], SgcePublicoTextoEstado($D['Estado'])];
}
foreach (($ConductaResumen['Detalle'] ?? []) as $C) {
    $FilasPdf[] = [$C['FechaTexto'], $C['MateriaNombre'] ?: $C['Origen'], 'CONDUCTA: ' . SgceConductaTextoTipo((string)$C['Tipo']), SgceConductaTextoEstado((string)$C['Estado']) . ' - ' . ($C['MotivoCorto'] ?? '')];
}

$GrupoTexto = trim((($InfoGrupo['ProgramaNombre'] ?? '') !== '' ? ($InfoGrupo['ProgramaNombre'] . ' / ') : '') . ($InfoGrupo['Grado'] ?? '') . ' ' . ($InfoGrupo['Grupo'] ?? '') . ' ' . ($InfoGrupo['Turno'] ?? ''));
$Subtitulo = 'Alumno: ' . $Alumno['NombreCompleto'] . ' | Grupo: ' . $GrupoTexto . ' | Rango: ' . SgceFechaYmdFormato($FechaInicio) . ' al ' . SgceFechaYmdFormato($FechaFin) . ' | A ' . $Resumen['Conteos']['A'] . ' / F ' . $Resumen['Conteos']['F'] . ' / R ' . $Resumen['Conteos']['R'] . ' / J ' . $Resumen['Conteos']['J'];
if (!empty($Resumen['RegistrosTruncados'])) {
    $Subtitulo .= ' | Detalle limitado a ' . (int)$Resumen['LimiteDetalle'] . ' registros más recientes';
}
RegistrarBitacora($Pdo, ['Id' => null, 'Rol' => 'publico'], 'EXPORTAR_ASISTENCIA_PUBLICA', 'Alumnos', (int)$Alumno['Id'], 'PDF PÚBLICO DE ASISTENCIA Y CONDUCTA');
SgcePdfRespuestaTabla($Pdo, 'Reporte de asistencia y conducta individual', $Subtitulo, ['Fecha', 'Materia', 'Docente', 'Estado'], $FilasPdf, 'Asistencia_Conducta_' . $Alumno['NombreCompleto'] . '_' . $FechaInicio . '_' . $FechaFin, 'L', [85, 230, 230, 120]);
