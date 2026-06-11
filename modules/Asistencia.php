<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/services/AsistenciaService.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !SgceTienePermiso($UserSession, 'asistencia')) { header('Location: index.php'); exit; }

$Hoy = date('Y-m-d');
$PuedeHistorico = SgcePuedeCorregirAsistenciaHistorica($UserSession);
$FechaConsulta = trim((string)($_GET['Fecha'] ?? ($_POST['Fecha'] ?? $Hoy)));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $FechaConsulta)) { $FechaConsulta = $Hoy; }
if (!$PuedeHistorico && $FechaConsulta !== $Hoy) { $FechaConsulta = $Hoy; }

$AsignacionId = (int)($_GET['id'] ?? ($_GET['AsignacionId'] ?? ($_POST['asignacion_id'] ?? 0)));
if ($AsignacionId <= 0) { SgceSalirConError('Asignación inválida.', 400); }

$InfoClase = SgceAsistenciaObtenerAsignacionActiva($Pdo, $AsignacionId);
if (!$InfoClase) { SgceSalirConError('Asignación no encontrada en el ciclo activo.', 404); }

$CicloClaseId = (int)$InfoClase['CicloId'];
if (!empty($InfoClase['FechaInicio']) && !empty($InfoClase['FechaFin']) && ($FechaConsulta < $InfoClase['FechaInicio'] || $FechaConsulta > $InfoClase['FechaFin'])) {
    SgceSalirConError('La fecha seleccionada no pertenece al ciclo escolar activo de esta asignación.', 400);
}

if (SgceTieneRol($UserSession, ['maestro']) && (int)$UserSession['Id'] !== (int)$InfoClase['MaestroId']) { SgceSalirConError('Acceso denegado.', 403); }

$Alumnos = SgceAsistenciaObtenerAlumnos($Pdo, (int)$InfoClase['GrupoId'], $CicloClaseId);
$YaSeRegistro = SgceAsistenciaExisteFecha($Pdo, $CicloClaseId, $AsignacionId, $FechaConsulta);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    RequerirCsrfPost();
    $UrlError = 'Asistencia.php?' . http_build_query(['id' => $AsignacionId, 'Fecha' => $FechaConsulta, 'Error' => 1]);
    if (!isset($_POST['estado']) || !is_array($_POST['estado'])) { header('Location: ' . $UrlError); exit; }
    try {
        $Pdo->beginTransaction();
        SgceAsistenciaGuardarPase($Pdo, $CicloClaseId, $AsignacionId, $FechaConsulta, $Alumnos, $_POST['estado']);
        $Pdo->commit();
        RegistrarBitacora($Pdo, $UserSession, $YaSeRegistro ? 'EDITAR_ASISTENCIA' : 'REGISTRAR_ASISTENCIA', 'Asistencias', $AsignacionId, ($YaSeRegistro ? 'PASE DE LISTA ACTUALIZADO: ' : 'PASE DE LISTA REGISTRADO: ') . $FechaConsulta);
        header('Location: Asistencia.php?' . http_build_query(['id' => $AsignacionId, 'Fecha' => $FechaConsulta, 'Success' => 1, 'Tipo' => $YaSeRegistro ? 'actualizada' : 'registrada']));
        exit;
    } catch (Throwable $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        header('Location: ' . $UrlError);
        exit;
    }
}

$EstadosRegistrados = SgceAsistenciaEstadosRegistrados($Pdo, $CicloClaseId, $AsignacionId, $FechaConsulta);
$ResumenAsistencia = SgceAsistenciaResumenAlumnos($Alumnos, $EstadosRegistrados);
$Mensaje = '';
if (isset($_GET['Success'])) { $Mensaje = SgceComponenteAlerta(SgceAsistenciaMensajeResultado(strtolower(trim((string)($_GET['Tipo'] ?? '')))), 'success', 'fa-circle-check'); }
if (isset($_GET['Error'])) { $Mensaje = SgceComponenteAlerta('Error al guardar la asistencia. Recarga la página e intenta nuevamente.', 'danger', 'fa-circle-xmark'); }

require dirname(__DIR__) . '/views/asistencia/PaseLista.php';
