<?php
if (!defined('SGCE_INSTALLER')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function InstalarNombreBaseValido($Nombre) {
    return preg_match('/^[A-Za-z0-9_]{1,64}$/', (string)$Nombre) === 1;
}

function InstalarDsnServidorMysql($Host) {
    return 'mysql:host=' . trim((string)$Host) . ';charset=utf8mb4';
}

function InstalarDsnBaseMysql($Host, $BaseDatos) {
    return 'mysql:host=' . trim((string)$Host) . ';dbname=' . trim((string)$BaseDatos) . ';charset=utf8mb4';
}

function InstalarCrearConexionMysql($Dsn, $Usuario, $Password) {
    return new PDO($Dsn, $Usuario, $Password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
}

function InstalarBaseDatosExiste(PDO $PdoServidor, $BaseDatos) {
    $Stmt = $PdoServidor->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ? LIMIT 1');
    $Stmt->execute([(string)$BaseDatos]);
    return (bool)$Stmt->fetchColumn();
}

function InstalarCrearBaseDatos(PDO $PdoServidor, $BaseDatos) {
    if (!InstalarNombreBaseValido($BaseDatos)) {
        throw new InstalarMensajeUsuario('El nombre de la base de datos solo puede usar letras, números y guion bajo.');
    }
    $PdoServidor->exec('CREATE DATABASE `' . $BaseDatos . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}


function InstalarRollbackInstalacionParcial(PDO $Pdo): void {
    $Tablas = $Pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (!$Tablas) { return; }
    $Pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($Tablas as $Tabla) {
        if (preg_match('/^[A-Za-z0-9_]+$/', (string)$Tabla)) {
            $Pdo->exec('DROP TABLE IF EXISTS `' . $Tabla . '`');
        }
    }
    $Pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}
