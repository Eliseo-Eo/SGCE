<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

/*
    Archivo: Logout.php
    Descripción: Cierra la sesión del usuario.
    Limpia el token guardado en la base de datos y elimina la cookie de autenticación.
*/

require_once dirname(__DIR__) . '/config/Conexion.php';

// Limpieza del token de sesión activo.
$UsuarioActivoLogout = VerificarSesionCookie($Pdo);

// LIMPIAR TOKEN DE BASE DE DATOS

// Si existe cookie de sesión, se limpia también en la base de datos.
if (isset($_COOKIE['AuthToken'])) {

    $Token = trim($_COOKIE['AuthToken']);

    if ($Token !== '') {

        $Stmt = $Pdo->prepare("
            UPDATE Usuarios
            SET SessionToken = NULL, SessionTokenExpira = NULL
            WHERE SessionToken = ?
        ");

        $Stmt->execute([
            $Token
        ]);

        // Registro de cierre de sesión.
        if ($UsuarioActivoLogout) {
            RegistrarBitacora($Pdo, $UsuarioActivoLogout, 'CIERRE_SESION', 'Usuarios', $UsuarioActivoLogout['Id'], 'USUARIO CERRÓ SESIÓN');
        }
    }

    // LIMPIAR COOKIE

    setcookie(
        'AuthToken',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => EsHttps()
        ]
    );
}

// Redirección final al login.
header('Location: index.php');

exit;

?>
