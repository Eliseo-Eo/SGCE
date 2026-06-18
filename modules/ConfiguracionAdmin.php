<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'configuracion', 'Solo el administrador puede modificar la configuración del sistema.');
RequerirCsrfPost();

extract(SgceConfiguracionAdminPreparar($Pdo, $UserSession), EXTR_OVERWRITE);
require dirname(__DIR__) . '/views/admin/configuracion/Index.php';
