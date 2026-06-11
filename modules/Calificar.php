<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/services/CalificacionService.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || $UserSession['Rol'] !== 'maestro') { header('Location: index.php'); exit; }

$AsignacionId = (int)($_GET['AsignacionId'] ?? ($_POST['AsignacionId'] ?? 0));
$PeriodoId = SgcePeriodoActualId($Pdo, $_GET['PeriodoId'] ?? ($_POST['PeriodoId'] ?? 0));
$PeriodosDisponibles = SgcePeriodosDisponibles($Pdo);
$ConfigCalificacion = SgceCalificacionConfig($Pdo);
$TextoRangoCalificacion = SgceCalificacionTextoRango($Pdo);

$InfoClase = SgceCalificarObtenerAsignacionDocente($Pdo, $AsignacionId, (int)$UserSession['Id']);
if (!$InfoClase) { SgceSalirConError('Acceso denegado o grupo no encontrado en el ciclo activo.', 404); }

$CicloClaseId = (int)$InfoClase['CicloId'];
$PeriodoInfoCalificar = SgcePeriodoInfo($Pdo, $PeriodoId);
if (!$PeriodoInfoCalificar || (int)$PeriodoInfoCalificar['CicloId'] !== $CicloClaseId) { SgceSalirConError('El periodo seleccionado no pertenece al ciclo activo de esta asignación.', 400); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['GuardarNotes'])) {
    RequerirCsrfPost();
    $UrlError = 'Calificar.php?' . http_build_query(['AsignacionId' => $AsignacionId, 'PeriodoId' => $PeriodoId, 'Error' => 1]);
    $Notas = $_POST['Notas'] ?? [];
    if (!is_array($Notas)) { header('Location: ' . $UrlError); exit; }
    try {
        $Pdo->beginTransaction();
        SgceCalificarGuardarCalificaciones($Pdo, $AsignacionId, $PeriodoId, (int)$InfoClase['GrupoId'], $CicloClaseId, $Notas);
        $Pdo->commit();
        RegistrarBitacora($Pdo, $UserSession, 'GUARDAR_CALIFICACIONES', 'Calificaciones', $AsignacionId, 'CALIFICACIONES ACTUALIZADAS EN PERIODO ID ' . $PeriodoId);
    } catch (Throwable $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        header('Location: ' . $UrlError); exit;
    }
    header('Location: Calificar.php?' . http_build_query(['AsignacionId' => $AsignacionId, 'PeriodoId' => $PeriodoId, 'Success' => 1])); exit;
}

$Alumnos = SgceCalificarAlumnosConCalificacion($Pdo, $AsignacionId, $PeriodoId, (int)$InfoClase['GrupoId'], $CicloClaseId);
$ResumenCalificar = SgceCalificarResumen($Alumnos);
$TotalAlumnos = $ResumenCalificar['TotalAlumnos'];
$Calificados = $ResumenCalificar['Calificados'];
$PromedioGrupo = $ResumenCalificar['PromedioGrupo'];

require dirname(__DIR__) . '/views/calificaciones/CalificarGrupo.php';
