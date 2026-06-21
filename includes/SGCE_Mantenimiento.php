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

function SgceAsistenciasAsegurarArchivo(PDO $Pdo): void {
    $Pdo->exec("CREATE TABLE IF NOT EXISTS AsistenciasArchivo LIKE Asistencias");
}

function SgceAsistenciaTablaParaCiclo(PDO $Pdo, int $CicloId): string {
    if ($CicloId <= 0) { return 'Asistencias'; }
    static $Cache = [];
    if (isset($Cache[$CicloId])) { return $Cache[$CicloId]; }
    try {
        if (!SgceDbTablaExiste($Pdo, 'AsistenciasArchivo')) { return $Cache[$CicloId] = 'Asistencias'; }
        $Stmt = $Pdo->prepare('SELECT Activo FROM CiclosEscolares WHERE Id = ? LIMIT 1');
        $Stmt->execute([$CicloId]);
        $Activo = $Stmt->fetchColumn();
        if ($Activo === false || (int)$Activo === 1) { return $Cache[$CicloId] = 'Asistencias'; }
        $StmtArchivo = $Pdo->prepare('SELECT 1 FROM AsistenciasArchivo WHERE CicloId = ? LIMIT 1');
        $StmtArchivo->execute([$CicloId]);
        return $Cache[$CicloId] = ($StmtArchivo->fetchColumn() ? 'AsistenciasArchivo' : 'Asistencias');
    } catch (Throwable $E) {
        return $Cache[$CicloId] = 'Asistencias';
    }
}

function SgceAsistenciasArchivarCiclosCerrados(PDO $Pdo, int $Lote = 5000): int {
    $Lote = max(100, min(50000, $Lote));
    if (!SgceDbTablaExiste($Pdo, 'Asistencias')) { return 0; }
    SgceAsistenciasAsegurarArchivo($Pdo);
    $Pdo->exec("INSERT IGNORE INTO AsistenciasArchivo
        (Id, CicloId, AsignacionId, AlumnoId, Fecha, Estado, FechaRegistro)
        SELECT Asi.Id, Asi.CicloId, Asi.AsignacionId, Asi.AlumnoId, Asi.Fecha, Asi.Estado, Asi.FechaRegistro
        FROM Asistencias Asi
        INNER JOIN CiclosEscolares C ON C.Id = Asi.CicloId AND C.Activo = 0
        ORDER BY Asi.CicloId ASC, Asi.Id ASC
        LIMIT {$Lote}");
    $Copiados = (int)$Pdo->query('SELECT ROW_COUNT()')->fetchColumn();
    $Pdo->exec("DELETE FROM Asistencias
        WHERE Id IN (
            SELECT Id FROM (
                SELECT Asi.Id
                FROM Asistencias Asi
                INNER JOIN CiclosEscolares C ON C.Id = Asi.CicloId AND C.Activo = 0
                INNER JOIN AsistenciasArchivo AA ON AA.Id = Asi.Id
                ORDER BY Asi.CicloId ASC, Asi.Id ASC
                LIMIT {$Lote}
            ) SgceAsistenciasArchivadas
        )");
    return $Copiados;
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

function SgceMantenimientoLimpiarBloqueosExpirados(PDO $Pdo): int {
    $Stmt = $Pdo->prepare("DELETE FROM IntentosSeguridad WHERE BloqueadoHasta IS NOT NULL AND BloqueadoHasta < NOW()");
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
        'AsistenciasArchivadas' => SgceAsistenciasArchivarCiclosCerrados($Pdo),
        'SesionesExpiradasLimpiadas' => SgceMantenimientoLimpiarSesionesExpiradas($Pdo),
        'IntentosSeguridadLimpiados' => SgceMantenimientoLimpiarIntentosSeguridad($Pdo, $DiasIntentos),
        'BloqueosExpiradosLimpiados' => SgceMantenimientoLimpiarBloqueosExpirados($Pdo),
        'RespaldosTemporalesEliminados' => SgceMantenimientoLimpiarRespaldosTemporales($DiasRespaldosTemporales),
        'Fecha' => date('Y-m-d H:i:s'),
    ];
}
