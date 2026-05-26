<?php

require 'Conexion.php';

// ELIMINAR TOKEN DE BASE DE DATOS

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
            'samesite' => 'Lax'
        ]
    );
}

// REDIRECCIONAR

header('Location: index.php');

exit;

?>