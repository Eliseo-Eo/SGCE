<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !SgcePuedePanelAdmin($UserSession)) { header('Location: index.php'); exit; }

$EsAdmin = SgceTieneRol($UserSession, ['admin']);
$EsAdministrativo = SgceTieneRol($UserSession, ['administrativo']);
$PuedeVerBitacora = SgcePuedeBitacora($UserSession);
$PuedeGestionarCatalogos = SgcePuedeGestionarCatalogos($UserSession);

$TabSolicitada = (string)($_GET['Tab'] ?? $_POST['Tab'] ?? 'inicio');
$TabActual = SgceTabAdminPermitida($TabSolicitada, $UserSession);
if ($TabSolicitada !== '' && $TabSolicitada !== $TabActual && in_array($TabSolicitada, ['bitacora'], true)) {
    SgceDenegarAcceso('Solo el administrador puede entrar a la bitácora del sistema.');
}
$_SESSION['Tab'] = $TabActual;

require __DIR__ . '/admin/AdminAcciones.php';
require __DIR__ . '/admin/AdminDatos.php';
require __DIR__ . '/admin/AdminVista.php';
