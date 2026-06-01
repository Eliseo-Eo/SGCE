<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceUsuarioContarAdminsActivosServicio(PDO $Pdo): int {
    return function_exists('SgceContarAdminsActivos') ? SgceContarAdminsActivos($Pdo) : (int)$Pdo->query("SELECT COUNT(*) FROM Usuarios WHERE Rol='admin' AND Activo=1")->fetchColumn();
}
