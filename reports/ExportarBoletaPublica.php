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
    SgcePublicoRegistrarFallo($Pdo, 'exportar_boleta_publica', 'TOKEN_INVALIDO', 4, 16, 15);
    http_response_code(403);
    exit('Consulta no válida. Realiza la búsqueda nuevamente.');
}

$ConsultaGuardada = SgcePublicoLeerTokenConsulta($ConsultaToken, 'calificaciones');
if (!$ConsultaGuardada) {
    SgcePublicoRegistrarFallo($Pdo, 'exportar_boleta_publica', 'TOKEN_EXPIRADO', 4, 16, 15);
    http_response_code(403);
    exit('La consulta expiró. Realiza la búsqueda nuevamente.');
}

$Datos = $ConsultaGuardada['Datos'] ?? [];
$NombreAlumno = SgceNormalizarMayusculas($Datos['NombreAlumno'] ?? '');
$ProgramaId = (int)($Datos['ProgramaId'] ?? 0);
$Grado = SgceNormalizarMayusculas($Datos['Grado'] ?? '');
$Grupo = SgcePublicoNormalizarGrupo($Datos['Grupo'] ?? '');
$Turno = SgceNormalizarMayusculas($Datos['Turno'] ?? '');

$RateKey = SgcePublicoRateKey($NombreAlumno, $ProgramaId, $Grado, $Grupo, $Turno);
if (!SgcePublicoRateDisponible($Pdo, 'exportar_boleta_publica', $RateKey)) {
    http_response_code(429);
    exit('Demasiados intentos. Espera unos minutos e intenta nuevamente.');
}

$DatosAlumno = SgcePublicoBuscarAlumno($Pdo, $NombreAlumno, $ProgramaId, $Grado, $Grupo, $Turno, $Error);
if (!$DatosAlumno) {
    SgcePublicoRegistrarFallo($Pdo, 'exportar_boleta_publica', $RateKey, 8, 24, 15);
    http_response_code(404);
    exit($Error ?: SgcePublicoMensajeNoEncontrado());
}

$Alumno = $DatosAlumno['Alumno'];
$InfoGrupo = $DatosAlumno['Grupo'];
$DatosCal = SgcePublicoCalificacionesCiclo($Pdo, (int)$Alumno['Id'], (int)$InfoGrupo['Id']);

$Columnas = ['Materia'];
foreach ($DatosCal['Periodos'] as $P) { $Columnas[] = $P['Nombre']; }
$Columnas[] = 'Promedio';

$FilasPdf = [];
foreach ($DatosCal['Filas'] as $Fila) {
    $Row = [$Fila['MateriaNombre']];
    foreach ($DatosCal['Periodos'] as $P) {
        $Row[] = SgcePublicoFormatoCalificacion($Fila['Valores'][(int)$P['Id']] ?? null);
    }
    $Row[] = SgcePublicoFormatoCalificacion($Fila['PromedioMateria']);
    $FilasPdf[] = $Row;
}

$GrupoTexto = trim((($InfoGrupo['ProgramaNombre'] ?? '') !== '' ? ($InfoGrupo['ProgramaNombre'] . ' / ') : '') . ($InfoGrupo['Grado'] ?? '') . ' ' . ($InfoGrupo['Grupo'] ?? '') . ' ' . ($InfoGrupo['Turno'] ?? ''));
$Promedio = $DatosCal['PromedioGeneral'] !== null ? number_format((float)$DatosCal['PromedioGeneral'], 2) : '-';
$Subtitulo = 'Alumno: ' . $Alumno['NombreCompleto'] . ' | Grupo: ' . $GrupoTexto . ' | Ciclo: ' . (($DatosCal['Ciclo']['Nombre'] ?? '') ?: 'Sin ciclo activo') . ' | Promedio general: ' . $Promedio;
$Anchos = [230];
for ($I = 0; $I < count($DatosCal['Periodos']); $I++) { $Anchos[] = 92; }
$Anchos[] = 92;

RegistrarBitacora($Pdo, ['Id' => null, 'Rol' => 'publico'], 'EXPORTAR_BOLETA_PUBLICA', 'Alumnos', (int)$Alumno['Id'], 'BOLETA PÚBLICA GENERADA');
SgcePdfRespuestaTabla($Pdo, 'Boleta individual', $Subtitulo, $Columnas, $FilasPdf, 'Boleta_' . $Alumno['NombreCompleto'], 'L', $Anchos);
