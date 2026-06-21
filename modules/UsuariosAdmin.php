<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/services/admin/UsuariosAdminService.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'usuarios', 'Solo el administrador puede gestionar usuarios y roles.');

extract(SgceUsuariosAdminPreparar($Pdo, $UserSession), EXTR_OVERWRITE);
require dirname(__DIR__) . '/views/admin/usuarios/Index.php';
