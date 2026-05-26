<?php

date_default_timezone_set('America/Mexico_City');

$Host = 'localhost';

$Db = 'ControlEscolar';

$User = 'Eo';

$Pass = 'Eo94?';

$Charset = 'utf8mb4';

$Dsn = "mysql:host=$Host;dbname=$Db;charset=$Charset";

$Options = [

    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    PDO::ATTR_EMULATE_PREPARES => false

];

try {

    $Pdo = new PDO(
        $Dsn,
        $User,
        $Pass,
        $Options
    );

} catch (PDOException $E) {

    die("Error De Conexión.");
}

// VERIFICAR SESIÓN

function VerificarSesionCookie($Pdo) {

    if (!isset($_COOKIE['AuthToken'])) {

        return false;
    }

    $Token = trim($_COOKIE['AuthToken']);

    if ($Token === '') {

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
        LIMIT 1
    ");

    $Stmt->execute([$Token]);

    return $Stmt->fetch();
}

?>