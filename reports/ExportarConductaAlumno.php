<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/includes/SGCE_Pdf.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'reportes', 'No tienes permiso para exportar conducta de alumnos.');

$AlumnoId = (int)($_GET['AlumnoId'] ?? 0);
$CicloId = (int)($_GET['CicloId'] ?? 0);
if ($AlumnoId <= 0) { http_response_code(400); exit('Alumno inválido.'); }
if ($CicloId <= 0) { $Ciclo = SgceCicloActivo($Pdo); $CicloId = (int)($Ciclo['Id'] ?? 0); }

$StmtAlumno = $Pdo->prepare('SELECT Id, NombreCompleto FROM Alumnos WHERE Id = ? LIMIT 1');
$StmtAlumno->execute([$AlumnoId]);
$Alumno = $StmtAlumno->fetch();
if (!$Alumno || $CicloId <= 0) { http_response_code(404); exit('Alumno o ciclo no encontrado.'); }
$Ciclo = SgceCicloPorId($Pdo, $CicloId);
$Registros = SgceConductaHistorialAlumno($Pdo, $AlumnoId, $CicloId, 300, false);
$Filas = [];
foreach ($Registros as $R) {
    $Filas[] = [
        $R['FechaTexto'] ?? '',
        SgceConductaTextoTipo((string)$R['Tipo']),
        SgceConductaTextoSeveridad((string)$R['Severidad']),
        $R['MateriaNombre'] ?: ($R['Origen'] ?? ''),
        SgceConductaTextoEstado((string)$R['Estado']),
        $R['MotivoCorto'] ?? '',
    ];
}
RegistrarBitacora($Pdo, $UserSession, 'EXPORTAR_CONDUCTA_ALUMNO', 'Alumnos', $AlumnoId, 'HISTORIAL DE CONDUCTA DEL ALUMNO GENERADO');
$Subtitulo = 'Alumno: ' . $Alumno['NombreCompleto'] . ' | Ciclo: ' . ($Ciclo['Nombre'] ?? '') . ' | Reportes: ' . count($Filas);
SgcePdfRespuestaTabla($Pdo, 'Historial de conducta y disciplina', $Subtitulo, ['Fecha','Tipo','Severidad','Materia/Área','Estado','Motivo'], $Filas, 'Conducta_' . $Alumno['NombreCompleto'], 'L', [65, 90, 70, 130, 90, 300]);
