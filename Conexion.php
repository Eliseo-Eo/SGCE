<?php

/*
    Archivo: Conexion.php
    Descripción: Configuración de conexión PDO con MySQL y funciones base de sesión.
    Las contraseñas se mantienen normales porque así se pidió para este proyecto.
*/

// Defino la zona horaria del sistema para fechas de asistencia y reportes.
date_default_timezone_set('America/Mexico_City');

$Host = 'localhost';
$Db = 'ControlEscolar';
$User = 'Eo';
$Pass = 'Eo94?';
$Charset = 'utf8mb4';

$Dsn = "mysql:host=$Host;dbname=$Db;charset=$Charset";

// Opciones de PDO para trabajar con errores controlados y resultados asociativos.
$Options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

// Intento realizar la conexión. Si falla, detengo el sistema con mensaje simple.
try {
    $Pdo = new PDO($Dsn, $User, $Pass, $Options);
} catch (PDOException $E) {
    die("Error De Conexión.");
}

// VERIFICAR SESIÓN
// Verifico si la cookie AuthToken pertenece a un usuario activo.
function VerificarSesionCookie($Pdo) {

    if (!isset($_COOKIE['AuthToken'])) {
        return false;
    }

    $Token = trim((string)$_COOKIE['AuthToken']);

    if ($Token === '' || !preg_match('/^[a-f0-9]{64}$/i', $Token)) {
        return false;
    }

    $Stmt = $Pdo->prepare("
        SELECT
            Id,
            Username,
            NombreCompleto,
            Rol
        FROM Usuarios
        WHERE SessionToken = ?
        AND Activo = 1
        LIMIT 1
    ");

    $Stmt->execute([$Token]);

    return $Stmt->fetch();
}

// Detecto si el sitio está trabajando bajo HTTPS para configurar cookies seguras.
function EsHttps() {
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );
}

?>
