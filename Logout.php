<?php
require 'Conexion.php';

if (isset($_COOKIE['AuthToken'])) {
    $Token = $_COOKIE['AuthToken'];
    
    $Stmt = $Pdo->prepare("UPDATE Usuarios SET SessionToken = NULL WHERE SessionToken = ?");
    $Stmt->execute([$Token]);

    setcookie('AuthToken', '', time() - 3600, '/');
}

header('Location: index.php');
exit;
?>