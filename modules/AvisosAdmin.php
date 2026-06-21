<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }


require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/services/admin/AvisosAdminService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'avisos', 'No tienes permiso para gestionar avisos.');






extract(SgceAvisosAdminPreparar($Pdo, $UserSession), EXTR_OVERWRITE);
require dirname(__DIR__) . '/views/admin/avisos/Index.php';
