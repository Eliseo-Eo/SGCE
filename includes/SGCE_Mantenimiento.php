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

function SgceMantenimientoLimpiarSesionesExpiradas(PDO $Pdo): int {
    $Stmt = $Pdo->prepare("UPDATE Usuarios SET SessionToken = NULL, SessionTokenExpira = NULL WHERE SessionToken IS NOT NULL AND SessionTokenExpira IS NOT NULL AND SessionTokenExpira < NOW()");
    $Stmt->execute();
    return (int)$Stmt->rowCount();
}

function SgceMantenimientoLimpiarIntentosSeguridad(PDO $Pdo, int $Dias = 30): int {
    $Dias = max(1, min(365, $Dias));
    $Stmt = $Pdo->prepare("DELETE FROM IntentosSeguridad WHERE UltimoIntento < DATE_SUB(NOW(), INTERVAL ? DAY) AND (BloqueadoHasta IS NULL OR BloqueadoHasta < NOW())");
    $Stmt->bindValue(1, $Dias, PDO::PARAM_INT);
    $Stmt->execute();
    return (int)$Stmt->rowCount();
}

function SgceMantenimientoLimpiarRespaldosTemporales(int $Dias = 7): int {
    $Dias = max(1, min(90, $Dias));
    $Dir = defined('SGCE_BACKUP_DIR') ? SGCE_BACKUP_DIR : dirname(__DIR__) . '/storage/backups';
    if (!is_dir($Dir)) { return 0; }
    $Limite = time() - ($Dias * 86400);
    $Patrones = [
        'PreMigracion_Datos_*.sql',
        'PreMigracion_*.tmp',
        'RestorePreview_*.sql',
        'SGCE_TEMP_*.sql',
    ];
    $Eliminados = 0;
    foreach ($Patrones as $Patron) {
        foreach (glob(rtrim($Dir, '/\\') . DIRECTORY_SEPARATOR . $Patron) ?: [] as $Archivo) {
            if (is_file($Archivo) && (int)@filemtime($Archivo) < $Limite && @unlink($Archivo)) { $Eliminados++; }
        }
    }
    return $Eliminados;
}

function SgceMantenimientoDiario(PDO $Pdo, array $Opciones = []): array {
    $DiasBitacora = (int)($Opciones['DiasBitacora'] ?? 365);
    $DiasIntentos = (int)($Opciones['DiasIntentos'] ?? 30);
    $DiasRespaldosTemporales = (int)($Opciones['DiasRespaldosTemporales'] ?? 7);
    return [
        'BitacoraArchivada' => SgceBitacoraArchivarAntigua($Pdo, $DiasBitacora),
        'SesionesExpiradasLimpiadas' => SgceMantenimientoLimpiarSesionesExpiradas($Pdo),
        'IntentosSeguridadLimpiados' => SgceMantenimientoLimpiarIntentosSeguridad($Pdo, $DiasIntentos),
        'RespaldosTemporalesEliminados' => SgceMantenimientoLimpiarRespaldosTemporales($DiasRespaldosTemporales),
        'Fecha' => date('Y-m-d H:i:s'),
    ];
}
