<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceBitacoraArchivarAntigua(PDO $Pdo, int $Dias = 365): int {
    $Dias = max(30, min(3650, $Dias));
    $Pdo->exec("CREATE TABLE IF NOT EXISTS BitacoraMovimientosArchivo LIKE BitacoraMovimientos");
    $StmtInsert = $Pdo->prepare("INSERT IGNORE INTO BitacoraMovimientosArchivo (Id, UsuarioId, Rol, Accion, TablaAfectada, RegistroId, Detalle, BusquedaTexto, Ip, FechaRegistro) SELECT Id, UsuarioId, Rol, Accion, TablaAfectada, RegistroId, Detalle, BusquedaTexto, Ip, FechaRegistro FROM BitacoraMovimientos WHERE FechaRegistro < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $StmtInsert->bindValue(1, $Dias, PDO::PARAM_INT);
    $StmtInsert->execute();
    $Archivados = (int)$StmtInsert->rowCount();
    $StmtDelete = $Pdo->prepare("DELETE FROM BitacoraMovimientos WHERE FechaRegistro < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $StmtDelete->bindValue(1, $Dias, PDO::PARAM_INT);
    $StmtDelete->execute();
    return $Archivados;
}
