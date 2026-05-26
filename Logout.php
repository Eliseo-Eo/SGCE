<?php

/*
    Archivo: Logout.php
    Descripción: Cierra la sesión del usuario.
    Limpia el token guardado en la base de datos y elimina la cookie de autenticación.
*/

require 'Conexion.php';

// Cargo la conexión para poder limpiar el token de sesión en la base de datos.

// ELIMINAR TOKEN DE BASE DE DATOS

// Si existe cookie de sesión, la elimino también de la base de datos.
if (isset($_COOKIE['AuthToken'])) {

    $Token = trim($_COOKIE['AuthToken']);

    if ($Token !== '') {

        $Stmt = $Pdo->prepare("
            UPDATE Usuarios
            SET SessionToken = NULL
            WHERE SessionToken = ?
        ");

        $Stmt->execute([
            $Token
        ]);
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