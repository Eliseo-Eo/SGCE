<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/services/ConductaService.php';
require_once dirname(__DIR__) . '/includes/SGCE_Pdf.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
if (!SgcePuedeGestionarConducta($UserSession)) { SgceDenegarAcceso('No tienes permiso para exportar conducta y disciplina.'); }

$CicloActivo = SgceCicloActivo($Pdo);
$CicloId = (int)($CicloActivo['Id'] ?? 0);
$Filtro = SgceConductaFiltros($_GET);
if (($Filtro['FechaInicio'] ?? '') > ($Filtro['FechaFin'] ?? '')) {
    [$Filtro['FechaInicio'], $Filtro['FechaFin']] = [$Filtro['FechaFin'], $Filtro['FechaInicio']];
}
$Registros = SgceConductaListar($Pdo, $CicloId, $Filtro, 500, 0);
$Filas = [];
foreach ($Registros as $R) {
    $Filas[] = [
        $R['FechaTexto'] ?? '',
        $R['AlumnoNombre'] ?? '',
        trim(($R['Grado'] ?? '') . ' ' . ($R['Grupo'] ?? '') . ' ' . ($R['Turno'] ?? '')),
        $R['MateriaNombre'] ?: ($R['Origen'] ?? ''),
        SgceConductaTextoSeveridad((string)$R['Severidad']),
        SgceConductaTextoEstado((string)$R['Estado']),
        $R['MotivoCorto'] ?? '',
    ];
}
RegistrarBitacora($Pdo, $UserSession, 'EXPORTAR_CONDUCTA', 'ConductaRegistros', null, 'PDF DE CONDUCTA Y DISCIPLINA EXPORTADO');
$Subtitulo = 'Ciclo: ' . ($CicloActivo['Nombre'] ?? '') . ' | Rango: ' . ($Filtro['FechaInicio'] ?? '') . ' al ' . ($Filtro['FechaFin'] ?? '') . ' | Registros: ' . count($Filas);
SgcePdfRespuestaTabla($Pdo, 'Reporte de conducta y disciplina', $Subtitulo, ['Fecha','Alumno','Grupo','Materia/Origen','Severidad','Estado','Motivo'], $Filas, 'Conducta_Disciplina_' . date('Ymd_His'), 'L', [55, 155, 70, 110, 70, 80, 190]);
