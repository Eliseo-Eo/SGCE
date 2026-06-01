<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
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
$Grado = SgceNormalizarMayusculas($Datos['Grado'] ?? '');
$Grupo = SgcePublicoNormalizarGrupo($Datos['Grupo'] ?? '');
$Turno = SgceNormalizarMayusculas($Datos['Turno'] ?? '');
$FechaInicio = SgcePublicoNormalizarFecha($Datos['FechaInicio'] ?? date('Y-m-d'), date('Y-m-d'));
$FechaFin = SgcePublicoNormalizarFecha($Datos['FechaFin'] ?? date('Y-m-d'), date('Y-m-d'));

$RateKey = SgcePublicoRateKey($NombreAlumno, $Grado, $Grupo, $Turno);
if (!SgcePublicoRateDisponible($Pdo, 'exportar_asistencia_publica', $RateKey)) {
    http_response_code(429);
    exit('Demasiados intentos. Espera unos minutos e intenta nuevamente.');
}
[$FechaInicio, $FechaFin] = SgcePublicoValidarRangoFechas($FechaInicio, $FechaFin, $Error, 60);
if ($Error !== '') { http_response_code(400); exit($Error); }

$DatosAlumno = SgcePublicoBuscarAlumno($Pdo, $NombreAlumno, $Grado, $Grupo, $Turno, $Error);
if (!$DatosAlumno) {
    SgcePublicoRegistrarFallo($Pdo, 'exportar_asistencia_publica', $RateKey, 8, 24, 15);
    http_response_code(404);
    exit($Error ?: SgcePublicoMensajeNoEncontrado());
}

$Alumno = $DatosAlumno['Alumno'];
$InfoGrupo = $DatosAlumno['Grupo'];
$Resumen = SgcePublicoResumenAsistencia($Pdo, (int)$Alumno['Id'], (int)$InfoGrupo['Id'], $FechaInicio, $FechaFin);

$FilasPdf = [];
foreach ($Resumen['Detalle'] as $D) {
    $FilasPdf[] = [$D['FechaTexto'], $D['MateriaNombre'], $D['Maestro'], SgcePublicoTextoEstado($D['Estado'])];
}

$GrupoTexto = trim(($InfoGrupo['Grado'] ?? '') . ' ' . ($InfoGrupo['Grupo'] ?? '') . ' ' . ($InfoGrupo['Turno'] ?? ''));
$Subtitulo = 'Alumno: ' . $Alumno['NombreCompleto'] . ' | Grupo: ' . $GrupoTexto . ' | Rango: ' . date('d/m/Y', strtotime($FechaInicio)) . ' al ' . date('d/m/Y', strtotime($FechaFin)) . ' | A ' . $Resumen['Conteos']['A'] . ' / F ' . $Resumen['Conteos']['F'] . ' / R ' . $Resumen['Conteos']['R'] . ' / J ' . $Resumen['Conteos']['J'];
RegistrarBitacora($Pdo, ['Id' => null, 'Rol' => 'publico'], 'EXPORTAR_ASISTENCIA_PUBLICA', 'Alumnos', (int)$Alumno['Id'], 'PDF PÚBLICO DE ASISTENCIA');
SgcePdfRespuestaTabla($Pdo, 'Reporte de asistencia individual', $Subtitulo, ['Fecha', 'Materia', 'Docente', 'Estado'], $FilasPdf, 'Asistencia_' . $Alumno['NombreCompleto'] . '_' . $FechaInicio . '_' . $FechaFin, 'L', [85, 230, 230, 120]);
