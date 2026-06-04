<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/includes/SGCE_Importacion.php';
require_once dirname(__DIR__) . '/services/importacion/ImportarAlumnosService.php';
require_once dirname(__DIR__) . '/services/importacion/ImportarGruposService.php';
require_once dirname(__DIR__) . '/services/importacion/ImportarMateriasService.php';
require_once dirname(__DIR__) . '/services/importacion/ImportarDocentesService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !SgcePuedeImportarCatalogos($UserSession)) {
    header('Location: index.php');
    exit;
}

RequerirCsrfPost();

function RedirectAdminImportar($Tab, $Mensaje, $EsError = false): void {
    global $UserSession;
    $_SESSION['Mensaje'] = $Mensaje;
    if ($EsError) {
        $_SESSION['MensajeTipo'] = 'danger';
    }
    header('Location: Admin.php?Tab=' . urlencode(SgceTabAdminPermitida($Tab, $UserSession)));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Admin.php');
    exit;
}

$Tab = SgceTabAdminPermitida($_POST['Tab'] ?? 'maestros', $UserSession);
$CicloActivoImportacion = SgceCicloActivo($Pdo);
$CicloActivoImportacionId = (int)($CicloActivoImportacion['Id'] ?? 0);

if (isset($_POST['ImportarAlumnos'])) {
    SgceImportarAlumnosService($Pdo, $UserSession, $CicloActivoImportacionId);
}

if (isset($_POST['ImportarGrupos'])) {
    SgceImportarGruposService($Pdo, $UserSession, $CicloActivoImportacionId);
}

if (isset($_POST['ImportarMateriasGrupo'])) {
    SgceImportarMateriasService($Pdo, $UserSession, $CicloActivoImportacionId);
}

if (isset($_POST['ImportarDocentes'])) {
    SgceImportarDocentesService($Pdo, $UserSession, $CicloActivoImportacionId);
}

RedirectAdminImportar($Tab, 'Operación de importación no reconocida.', true);
