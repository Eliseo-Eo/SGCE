<?php

/*
    Archivo: Logout.php
    Descripción: Cierra la sesión del usuario.
    Limpia el token guardado en la base de datos y elimina la cookie de autenticación.
*/

require_once dirname(__DIR__) . '/config/Conexion.php';

// Cargo la conexión para poder limpiar el token de sesión en la base de datos.
$UsuarioActivoLogout = VerificarSesionCookie($Pdo);

// ELIMINAR TOKEN DE BASE DE DATOS

// Si existe cookie de sesión, la elimino también de la base de datos.
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

        // Registro el cierre de sesión antes de eliminar la cookie.
        if ($UsuarioActivoLogout) {
            RegistrarBitacora($Pdo, $UsuarioActivoLogout, 'CIERRE_SESION', 'Usuarios', $UsuarioActivoLogout['Id'], 'USUARIO CERRÓ SESIÓN');
        }
    }

    // ELIMINAR COOKIE

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

// REDIRECCIONAR

// Al terminar, regreso al login.
header('Location: index.php');

exit;

?>
