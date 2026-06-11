<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

// Servicio de migración escolar blindada SGCE 1.0.140.
// Diagnóstico, respaldo restaurable desde UI, bloqueo, bitácora formal y validaciones previas.

function SgceMigracionAsegurarTabla(PDO $Pdo): void {
    if (function_exists('SgceDbTablaExiste') && SgceDbTablaExiste($Pdo, 'MigracionesCiclo')) { SgceMigracionAsegurarCandadoCompletadas($Pdo); return; }
    $Pdo->exec("CREATE TABLE IF NOT EXISTS MigracionesCiclo (
        Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        TipoMigracion ENUM('GRUPO','CICLO') NOT NULL DEFAULT 'CICLO',
        CicloOrigenId INT UNSIGNED NOT NULL,
        CicloDestinoId INT UNSIGNED NOT NULL,
        GrupoOrigenId INT UNSIGNED NOT NULL DEFAULT 0,
        UsuarioId INT UNSIGNED DEFAULT NULL,
        Estado ENUM('EN_PROCESO','COMPLETADA','ERROR','SIMULADA') NOT NULL DEFAULT 'EN_PROCESO',
        Confirmacion VARCHAR(140) DEFAULT NULL,
        RespaldoRuta VARCHAR(255) DEFAULT NULL,
        HuellaCompletada VARCHAR(160) DEFAULT NULL,
        GruposProcesados INT UNSIGNED NOT NULL DEFAULT 0,
        GruposCreados INT UNSIGNED NOT NULL DEFAULT 0,
        AlumnosPromovidos INT UNSIGNED NOT NULL DEFAULT 0,
        AlumnosEgresados INT UNSIGNED NOT NULL DEFAULT 0,
        AlumnosOmitidos INT UNSIGNED NOT NULL DEFAULT 0,
        Conflictos INT UNSIGNED NOT NULL DEFAULT 0,
        KardexCongelados INT UNSIGNED NOT NULL DEFAULT 0,
        AsignacionesCopiadas INT UNSIGNED NOT NULL DEFAULT 0,
        AsignacionesOmitidas INT UNSIGNED NOT NULL DEFAULT 0,
        ResumenJson LONGTEXT DEFAULT NULL,
        Mensaje TEXT DEFAULT NULL,
        FechaInicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FechaFin DATETIME DEFAULT NULL,
        CONSTRAINT fk_migraciones_ciclo_origen FOREIGN KEY (CicloOrigenId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_migraciones_ciclo_destino FOREIGN KEY (CicloDestinoId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_migraciones_usuario FOREIGN KEY (UsuarioId) REFERENCES Usuarios(Id) ON DELETE SET NULL ON UPDATE CASCADE,
        INDEX idx_migraciones_estado_fecha (Estado, FechaInicio),
        INDEX idx_migraciones_origen_destino (CicloOrigenId, CicloDestinoId, TipoMigracion, GrupoOrigenId, Estado),
        UNIQUE KEY uk_migraciones_completadas (HuellaCompletada),
        INDEX idx_migraciones_usuario_fecha (UsuarioId, FechaInicio)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    SgceMigracionAsegurarCandadoCompletadas($Pdo);
}

function SgceMigracionAsegurarCandadoCompletadas(PDO $Pdo): void {
    try {
        $Columnas = $Pdo->query("SHOW COLUMNS FROM MigracionesCiclo LIKE 'HuellaCompletada'")->fetchAll();
        if (!$Columnas) {
            $Pdo->exec("ALTER TABLE MigracionesCiclo ADD HuellaCompletada VARCHAR(160) DEFAULT NULL AFTER RespaldoRuta");
        }
        $StmtIndex = $Pdo->query("SHOW INDEX FROM MigracionesCiclo WHERE Key_name = 'uk_migraciones_completadas'");
        if (!$StmtIndex || !$StmtIndex->fetch()) {
            $Pdo->exec("ALTER TABLE MigracionesCiclo ADD UNIQUE KEY uk_migraciones_completadas (HuellaCompletada)");
        }
    } catch (Throwable $E) {
        // El diagnóstico de doble migración por código sigue activo si el ALTER no aplica en una base antigua.
    }
}

function SgceMigracionJson(array $Datos): string {
    $Json = json_encode($Datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $Json !== false ? $Json : '{}';
}

function SgceMigracionObtenerLock(PDO $Pdo, int $Timeout = 30): bool {
    try {
        $Stmt = $Pdo->prepare("SELECT GET_LOCK('SGCE_MIGRACION_CICLO', ?)");
        $Stmt->execute([max(1, min(120, $Timeout))]);
        return (int)$Stmt->fetchColumn() === 1;
    } catch (Throwable $E) {
        // Si el motor no soporta GET_LOCK en un entorno de prueba, no bloquea la ejecución.
        return true;
    }
}

function SgceMigracionLiberarLock(PDO $Pdo): void {
    try { $Pdo->query("SELECT RELEASE_LOCK('SGCE_MIGRACION_CICLO')"); } catch (Throwable $E) {}
}

function SgceMigracionPeriodosPorCicloOferta(PDO $Pdo, int $CicloId, int $OfertaId): array {
    if ($CicloId <= 0 || $OfertaId <= 0) { return []; }
    $Stmt = $Pdo->prepare('SELECT Id, CicloId, OfertaId, Nombre, Orden, Activo FROM PeriodosEvaluacion WHERE CicloId = ? AND OfertaId = ? AND Activo = 1 ORDER BY Orden ASC, Id ASC');
    $Stmt->execute([$CicloId, $OfertaId]);
    return $Stmt->fetchAll();
}

function SgceMigracionOfertasDeCiclo(PDO $Pdo, int $CicloId, int $GrupoId = 0): array {
    if ($CicloId <= 0) { return []; }
    $Sql = 'SELECT DISTINCT G.OfertaId, OE.Nombre AS OfertaNombre FROM Grupos G LEFT JOIN OfertasEducativas OE ON OE.Id = G.OfertaId WHERE G.CicloId = ? AND G.Activo = 1';
    $Params = [$CicloId];
    if ($GrupoId > 0) { $Sql .= ' AND G.Id = ?'; $Params[] = $GrupoId; }
    $Sql .= ' ORDER BY OE.Nombre, G.OfertaId';
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute($Params);
    return $Stmt->fetchAll();
}

function SgceMigracionCopiarPeriodosDesdeOrigen(PDO $Pdo, int $CicloOrigenId, int $CicloDestinoId): array {
    if ($CicloOrigenId <= 0 || $CicloDestinoId <= 0 || $CicloOrigenId === $CicloDestinoId) {
        throw new RuntimeException('Selecciona ciclos válidos para copiar periodos.');
    }
    $Origen = SgceCicloPorId($Pdo, $CicloOrigenId);
    $Destino = SgceCicloPorId($Pdo, $CicloDestinoId);
    if (!$Origen || !$Destino) { throw new RuntimeException('No se encontraron los ciclos seleccionados.'); }
    $Ofertas = SgceMigracionOfertasDeCiclo($Pdo, $CicloOrigenId);
    if (!$Ofertas) { throw new RuntimeException('El ciclo origen no tiene grupos/ofertas de donde tomar periodos.'); }

    $StmtBuscar = $Pdo->prepare('SELECT Id FROM PeriodosEvaluacion WHERE CicloId = ? AND OfertaId = ? AND Orden = ? LIMIT 1');
    $StmtInsertar = $Pdo->prepare('INSERT INTO PeriodosEvaluacion (CicloId, OfertaId, Nombre, Orden, Activo) VALUES (?, ?, ?, ?, 1)');
    $StmtActualizar = $Pdo->prepare('UPDATE PeriodosEvaluacion SET Nombre = ?, Activo = 1 WHERE Id = ?');
    $Creados = 0; $Actualizados = 0; $Omitidos = 0; $Detalle = [];

    foreach ($Ofertas as $Oferta) {
        $OfertaId = (int)$Oferta['OfertaId'];
        $PeriodosOrigen = SgceMigracionPeriodosPorCicloOferta($Pdo, $CicloOrigenId, $OfertaId);
        if (!$PeriodosOrigen) { $Omitidos++; $Detalle[] = 'Sin periodos origen en oferta ' . (string)($Oferta['OfertaNombre'] ?? $OfertaId); continue; }
        foreach ($PeriodosOrigen as $Periodo) {
            $Orden = (int)$Periodo['Orden'];
            $Nombre = (string)$Periodo['Nombre'];
            $StmtBuscar->execute([$CicloDestinoId, $OfertaId, $Orden]);
            $Id = (int)$StmtBuscar->fetchColumn();
            if ($Id > 0) { $StmtActualizar->execute([$Nombre, $Id]); $Actualizados++; }
            else { $StmtInsertar->execute([$CicloDestinoId, $OfertaId, $Nombre, $Orden]); $Creados++; }
        }
    }
    return ['Creados' => $Creados, 'Actualizados' => $Actualizados, 'Omitidos' => $Omitidos, 'Detalle' => $Detalle];
}

function SgceMigracionYaCompletada(PDO $Pdo, string $Tipo, int $CicloOrigenId, int $CicloDestinoId, int $GrupoOrigenId = 0): bool {
    SgceMigracionAsegurarTabla($Pdo);
    $Tipo = $Tipo === 'GRUPO' ? 'GRUPO' : 'CICLO';
    $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM MigracionesCiclo WHERE TipoMigracion = ? AND CicloOrigenId = ? AND CicloDestinoId = ? AND GrupoOrigenId = ? AND Estado = 'COMPLETADA'");
    $Stmt->execute([$Tipo, $CicloOrigenId, $CicloDestinoId, $GrupoOrigenId]);
    return (int)$Stmt->fetchColumn() > 0;
}

function SgceMigracionContarInscritos(PDO $Pdo, int $CicloId, int $GrupoId = 0): int {
    $Sql = "SELECT COUNT(*) FROM AlumnoInscripciones AI INNER JOIN Alumnos A ON A.Id = AI.AlumnoId AND A.Activo = 1 WHERE AI.CicloId = ? AND AI.Estado = 'INSCRITO'";
    $Params = [$CicloId];
    if ($GrupoId > 0) { $Sql .= ' AND AI.GrupoId = ?'; $Params[] = $GrupoId; }
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute($Params);
    return (int)$Stmt->fetchColumn();
}

function SgceMigracionDiagnosticar(PDO $Pdo, int $CicloOrigenId, int $CicloDestinoId, int $GrupoOrigenId = 0, bool $CopiarAsignaciones = false): array {
    SgceMigracionAsegurarTabla($Pdo);
    $Origen = $CicloOrigenId > 0 ? SgceCicloPorId($Pdo, $CicloOrigenId) : null;
    $Destino = $CicloDestinoId > 0 ? SgceCicloPorId($Pdo, $CicloDestinoId) : null;
    $Tipo = $GrupoOrigenId > 0 ? 'GRUPO' : 'CICLO';
    $Errores = [];
    $Advertencias = [];
    $DetalleGrupos = [];

    if (!$Origen) { $Errores[] = 'No se encontró el ciclo origen.'; }
    if (!$Destino) { $Errores[] = 'No se encontró el ciclo destino activo.'; }
    if ($Origen && (int)$Origen['Activo'] === 1) { $Errores[] = 'El ciclo origen todavía está activo. Primero activa el nuevo ciclo para cerrar el anterior.'; }
    if ($Destino && (int)$Destino['Activo'] !== 1) { $Errores[] = 'El ciclo destino debe estar activo.'; }
    if ($CicloOrigenId > 0 && $CicloOrigenId === $CicloDestinoId) { $Errores[] = 'El ciclo origen y destino no pueden ser el mismo.'; }
    if ($Origen && $Destino && SgceMigracionYaCompletada($Pdo, $Tipo, $CicloOrigenId, $CicloDestinoId, $GrupoOrigenId)) {
        $Errores[] = $Tipo === 'GRUPO' ? 'Este grupo ya fue migrado anteriormente hacia el ciclo activo.' : 'Este ciclo ya fue migrado anteriormente hacia el ciclo activo.';
    }

    $Grupos = [];
    if ($CicloOrigenId > 0) {
        if ($GrupoOrigenId > 0) {
            $Grupo = SgceGrupoObtenerPorId($Pdo, $GrupoOrigenId);
            if (!$Grupo || (int)$Grupo['CicloId'] !== $CicloOrigenId) { $Errores[] = 'El grupo origen no pertenece al ciclo seleccionado.'; }
            else { $Grupos = [$Grupo]; }
        } else {
            $Grupos = SgceGruposListarPorCiclo($Pdo, $CicloOrigenId, true);
        }
    }
    if (!$Grupos && $CicloOrigenId > 0) { $Errores[] = 'El ciclo origen no tiene grupos activos para migrar.'; }

    $Ofertas = SgceMigracionOfertasDeCiclo($Pdo, $CicloOrigenId, $GrupoOrigenId);
    $PeriodosOrigenTotal = 0; $PeriodosDestinoTotal = 0; $DestinoSinPeriodos = [];
    foreach ($Ofertas as $Oferta) {
        $OfertaId = (int)$Oferta['OfertaId'];
        $OrigenPeriodos = SgceMigracionPeriodosPorCicloOferta($Pdo, $CicloOrigenId, $OfertaId);
        $DestinoPeriodos = SgceMigracionPeriodosPorCicloOferta($Pdo, $CicloDestinoId, $OfertaId);
        $PeriodosOrigenTotal += count($OrigenPeriodos);
        $PeriodosDestinoTotal += count($DestinoPeriodos);
        if (!$DestinoPeriodos) { $DestinoSinPeriodos[] = (string)($Oferta['OfertaNombre'] ?? ('Oferta ' . $OfertaId)); }
        if (!$OrigenPeriodos) { $Advertencias[] = 'El ciclo origen no tiene periodos en la oferta ' . (string)($Oferta['OfertaNombre'] ?? $OfertaId) . '. El kardex se congelará sin periodos de referencia.'; }
    }
    if ($Ofertas && $DestinoSinPeriodos) {
        $Errores[] = 'El ciclo destino no tiene periodos de evaluación para: ' . implode(', ', $DestinoSinPeriodos) . '.';
    }

    $Promovidos = 0; $Egresados = 0; $Conflictos = 0; $GruposTerminales = 0; $GruposSinAlumnos = 0; $GruposConDestinoExistente = 0;
    $GruposDestinoPreparar = 0; $MateriasDestinoCopiables = 0; $GruposDestinoSinMaterias = 0; $AsignacionesCopiables = 0;
    foreach ($Grupos as $Grupo) {
        $Gid = (int)$Grupo['Id'];
        $Inscritos = SgceMigracionContarInscritos($Pdo, $CicloOrigenId, $Gid);
        if ($Inscritos <= 0) { $GruposSinAlumnos++; }
        $MateriasGrupo = SgceMigracionMateriasActivasGrupo($Pdo, $CicloOrigenId, $Gid);
        $MateriasDestinoCopiables += count($MateriasGrupo);
        if (!$MateriasGrupo) { $GruposDestinoSinMaterias++; }
        if ($CopiarAsignaciones) {
            $StmtAsig = $Pdo->prepare('SELECT COUNT(*) FROM Asignaciones WHERE CicloId = ? AND GrupoId = ? AND Activo = 1');
            $StmtAsig->execute([$CicloOrigenId, $Gid]);
            $AsignacionesCopiables += (int)$StmtAsig->fetchColumn();
        }
        $GruposDestinoPreparar++;
        $GrupoDestinoMismaEtapa = null;
        if ($Destino) {
            $GrupoDestinoMismaEtapa = SgceGrupoObtenerPorCicloEstructura($Pdo, $CicloDestinoId, (int)$Grupo['OfertaId'], (int)$Grupo['ProgramaId'], (int)$Grupo['EtapaId'], (string)$Grupo['Grupo'], (string)$Grupo['Turno']);
            if ($GrupoDestinoMismaEtapa) { $GruposConDestinoExistente++; }
        }
        $EtapaSiguiente = !empty($Grupo['EtapaId']) ? SgceEtapaSiguiente($Pdo, (int)$Grupo['EtapaId']) : null;
        $Terminal = !$EtapaSiguiente;
        if ($Terminal) { $GruposTerminales++; $Egresados += $Inscritos; }
        else { $Promovidos += $Inscritos; }
        $DetalleGrupos[] = [
            'Id' => $Gid,
            'Nombre' => trim((string)$Grupo['Grado'] . ' ' . (string)$Grupo['Grupo'] . ' ' . (string)$Grupo['Turno']),
            'Inscritos' => $Inscritos,
            'Materias' => count($MateriasGrupo),
            'Accion' => $Terminal ? 'EGRESAR' : 'PROMOVER',
            'DestinoExistente' => (bool)$GrupoDestinoMismaEtapa,
        ];
    }
    $TotalInscritos = $Promovidos + $Egresados;
    if ($Grupos && $TotalInscritos <= 0) { $Errores[] = 'El ciclo/grupo origen no tiene alumnos INSCRITOS para migrar.'; }
    if ($GruposSinAlumnos > 0) { $Advertencias[] = $GruposSinAlumnos . ' grupo(s) no tienen alumnos INSCRITOS; se preparará su estructura en el ciclo nuevo, pero no moverán alumnos.'; }
    if ($GruposDestinoSinMaterias > 0) { $Advertencias[] = $GruposDestinoSinMaterias . ' grupo(s) origen no tienen materias configuradas; su grupo equivalente en el ciclo nuevo quedará sin materias hasta que se registren manualmente.'; }
    if ($CopiarAsignaciones) { $Advertencias[] = 'Las asignaciones/docentes se copiarán como plantilla al mismo grado/semestre/módulo del ciclo nuevo, solo si la materia existe y el docente está activo.'; }

    $BackupDir = defined('SGCE_BACKUP_DIR') ? SGCE_BACKUP_DIR : dirname(__DIR__, 2) . '/storage/backups';
    if (!is_dir($BackupDir) && !@mkdir($BackupDir, 0775, true) && !is_dir($BackupDir)) { $Errores[] = 'No se puede crear la carpeta de respaldos obligatorios.'; }
    if (is_dir($BackupDir) && !is_writable($BackupDir)) { $Errores[] = 'La carpeta de respaldos obligatorios no tiene permisos de escritura.'; }

    $ConfirmacionEsperada = ($Origen && $Destino) ? ('MIGRAR ' . (string)$Origen['Nombre'] . ' A ' . (string)$Destino['Nombre']) : 'MIGRAR';

    return [
        'Tipo' => $Tipo,
        'Origen' => $Origen,
        'Destino' => $Destino,
        'CicloOrigenId' => $CicloOrigenId,
        'CicloDestinoId' => $CicloDestinoId,
        'GrupoOrigenId' => $GrupoOrigenId,
        'GruposOrigen' => count($Grupos),
        'GruposTerminales' => $GruposTerminales,
        'GruposSinAlumnos' => $GruposSinAlumnos,
        'GruposConDestinoExistente' => $GruposConDestinoExistente,
        'GruposDestinoPreparar' => $GruposDestinoPreparar,
        'GruposDestinoSinMaterias' => $GruposDestinoSinMaterias,
        'MateriasDestinoCopiables' => $MateriasDestinoCopiables,
        'AsignacionesCopiables' => $AsignacionesCopiables,
        'AlumnosInscritos' => $TotalInscritos,
        'AlumnosAPromover' => $Promovidos,
        'AlumnosAEgresar' => $Egresados,
        'ConflictosDetectados' => $Conflictos,
        'PeriodosOrigen' => $PeriodosOrigenTotal,
        'PeriodosDestino' => $PeriodosDestinoTotal,
        'DestinoSinPeriodos' => $DestinoSinPeriodos,
        'PuedeMigrar' => empty($Errores),
        'Errores' => $Errores,
        'Advertencias' => $Advertencias,
        'DetalleGrupos' => $DetalleGrupos,
        'ConfirmacionEsperada' => $ConfirmacionEsperada,
    ];
}

function SgceMigracionValidarDiagnostico(array $Diagnostico): void {
    if (!empty($Diagnostico['PuedeMigrar'])) { return; }
    $Errores = $Diagnostico['Errores'] ?? [];
    throw new RuntimeException($Errores ? implode(' ', array_map('strval', $Errores)) : 'La migración no cumple los requisitos mínimos.');
}

function SgceMigracionValidarConfirmacion(string $Confirmacion, array $Diagnostico): void {
    $Esperada = trim((string)($Diagnostico['ConfirmacionEsperada'] ?? 'MIGRAR'));
    $Confirmacion = trim(preg_replace('/\s+/u', ' ', SgceNormalizarMayusculas($Confirmacion)));
    $EsperadaNormalizada = trim(preg_replace('/\s+/u', ' ', SgceNormalizarMayusculas($Esperada)));
    if ($Confirmacion !== $EsperadaNormalizada) {
        throw new RuntimeException('Confirmación incorrecta. Escribe exactamente: ' . $Esperada);
    }
}

function SgceMigracionCrearRespaldoPrevio(PDO $Pdo, string $Tipo): string {
    $Tipo = $Tipo === 'GRUPO' ? 'Grupo' : 'Ciclo';
    $Dir = defined('SGCE_BACKUP_DIR') ? SGCE_BACKUP_DIR : dirname(__DIR__, 2) . '/storage/backups';
    if (!is_dir($Dir) && !@mkdir($Dir, 0775, true) && !is_dir($Dir)) { throw new RuntimeException('No se pudo crear la carpeta de respaldos.'); }
    if (!is_writable($Dir)) { throw new RuntimeException('La carpeta de respaldos no tiene permisos de escritura.'); }
    $Archivo = rtrim($Dir, '/\\') . '/PreMigracion_Datos_' . $Tipo . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.sql';
    if (!SgceCrearRespaldoSql($Pdo, $Archivo, true)) { throw new RuntimeException('No se pudo generar el respaldo obligatorio previo a migración.'); }
    return $Archivo;
}

function SgceMigracionRegistrarInicio(PDO $Pdo, array $UserSession, array $Diagnostico, string $Confirmacion, string $RespaldoRuta): int {
    SgceMigracionAsegurarTabla($Pdo);
    $Stmt = $Pdo->prepare("INSERT INTO MigracionesCiclo (TipoMigracion, CicloOrigenId, CicloDestinoId, GrupoOrigenId, UsuarioId, Estado, Confirmacion, RespaldoRuta, ResumenJson, Mensaje) VALUES (?, ?, ?, ?, NULLIF(?,0), 'EN_PROCESO', ?, ?, ?, ?)");
    $Stmt->execute([
        (string)($Diagnostico['Tipo'] ?? 'CICLO'),
        (int)($Diagnostico['CicloOrigenId'] ?? 0),
        (int)($Diagnostico['CicloDestinoId'] ?? 0),
        (int)($Diagnostico['GrupoOrigenId'] ?? 0),
        (int)($UserSession['Id'] ?? 0),
        $Confirmacion,
        $RespaldoRuta,
        SgceMigracionJson($Diagnostico),
        'Migración iniciada con diagnóstico previo validado.',
    ]);
    return (int)$Pdo->lastInsertId();
}

function SgceMigracionActualizarRegistro(PDO $Pdo, int $RegistroId, string $Estado, array $Resumen, string $Mensaje = ''): void {
    if ($RegistroId <= 0) { return; }
    $Estado = in_array($Estado, ['COMPLETADA','ERROR','SIMULADA','EN_PROCESO'], true) ? $Estado : 'ERROR';
    $AsignacionesOmitidas = array_key_exists('AsignacionesOmitidas', $Resumen)
        ? (int)$Resumen['AsignacionesOmitidas']
        : ((int)($Resumen['AsignacionesOmitidasDocente'] ?? 0) + (int)($Resumen['AsignacionesOmitidasMateria'] ?? 0) + (int)($Resumen['AsignacionesOmitidasDuplicado'] ?? 0));
    $Stmt = $Pdo->prepare("UPDATE MigracionesCiclo SET Estado = ?, FechaFin = CURRENT_TIMESTAMP, HuellaCompletada = CASE WHEN ? = 'COMPLETADA' THEN CONCAT(TipoMigracion, ':', CicloOrigenId, ':', CicloDestinoId, ':', GrupoOrigenId) ELSE NULL END, GruposProcesados = ?, GruposCreados = ?, AlumnosPromovidos = ?, AlumnosEgresados = ?, AlumnosOmitidos = ?, Conflictos = ?, KardexCongelados = ?, AsignacionesCopiadas = ?, AsignacionesOmitidas = ?, ResumenJson = ?, Mensaje = ? WHERE Id = ?");
    $Stmt->execute([
        $Estado,
        $Estado,
        (int)($Resumen['GruposProcesados'] ?? 0),
        (int)($Resumen['GruposCreados'] ?? $Resumen['GruposDestinoCreados'] ?? 0),
        (int)($Resumen['Promovidos'] ?? $Resumen['AlumnosPromovidos'] ?? 0),
        (int)($Resumen['Egresados'] ?? $Resumen['AlumnosEgresados'] ?? 0),
        (int)($Resumen['Omitidos'] ?? $Resumen['AlumnosOmitidos'] ?? 0),
        (int)($Resumen['Conflictos'] ?? 0),
        (int)($Resumen['KardexCongelados'] ?? 0),
        (int)($Resumen['AsignacionesCopiadas'] ?? 0),
        $AsignacionesOmitidas,
        SgceMigracionJson($Resumen),
        $Mensaje,
        $RegistroId,
    ]);
}

function SgceMigracionMateriaGrupoDestinoEquivalente(PDO $Pdo, int $GrupoDestinoId, int $CicloDestinoId, int $MateriaId, string $MateriaNombre): int {
    if ($GrupoDestinoId <= 0 || $CicloDestinoId <= 0) { return 0; }
    if ($MateriaId > 0) {
        $Stmt = $Pdo->prepare('SELECT Id FROM MateriasGrupo WHERE CicloId = ? AND GrupoId = ? AND MateriaId = ? AND Activo = 1 LIMIT 1');
        $Stmt->execute([$CicloDestinoId, $GrupoDestinoId, $MateriaId]);
        $Id = (int)$Stmt->fetchColumn();
        if ($Id > 0) { return $Id; }
    }
    $MateriaNombre = SgceNormalizarMayusculas($MateriaNombre);
    if ($MateriaNombre !== '') {
        $Stmt = $Pdo->prepare('SELECT Id FROM MateriasGrupo WHERE CicloId = ? AND GrupoId = ? AND MateriaNombre = ? AND Activo = 1 LIMIT 1');
        $Stmt->execute([$CicloDestinoId, $GrupoDestinoId, $MateriaNombre]);
        return (int)$Stmt->fetchColumn();
    }
    return 0;
}


function SgceMigracionMateriasActivasGrupo(PDO $Pdo, int $CicloId, int $GrupoId): array {
    if ($CicloId <= 0 || $GrupoId <= 0 || !SgceDbTablaExiste($Pdo, 'MateriasGrupo')) { return []; }
    $Stmt = $Pdo->prepare('SELECT Id, MateriaId, MateriaNombre, HorasSemana FROM MateriasGrupo WHERE CicloId = ? AND GrupoId = ? AND Activo = 1 ORDER BY MateriaNombre, Id');
    $Stmt->execute([$CicloId, $GrupoId]);
    return $Stmt->fetchAll();
}

function SgceMigracionBuscarGrupoPlantilla(PDO $Pdo, int $CicloOrigenId, int $OfertaId, int $ProgramaId, int $EtapaId, string $Grupo, string $Turno) {
    if ($CicloOrigenId <= 0 || $OfertaId <= 0 || $ProgramaId <= 0 || $EtapaId <= 0) { return null; }
    return SgceGrupoObtenerPorCicloEstructura($Pdo, $CicloOrigenId, $OfertaId, $ProgramaId, $EtapaId, $Grupo, $Turno);
}

function SgceMigracionCrearGrupoDestinoDesdePlantilla(PDO $Pdo, array $GrupoPlantilla, int $CicloDestinoId): array {
    $OfertaId = (int)($GrupoPlantilla['OfertaId'] ?? 0);
    $ProgramaId = (int)($GrupoPlantilla['ProgramaId'] ?? 0);
    $EtapaId = (int)($GrupoPlantilla['EtapaId'] ?? 0);
    if ($OfertaId <= 0 || $EtapaId <= 0) { throw new RuntimeException('La plantilla de grupo no tiene oferta o etapa académica válida.'); }
    if ($ProgramaId <= 0) { $ProgramaId = SgceProgramaGeneralId($Pdo, $OfertaId); }
    $Existente = SgceGrupoObtenerPorCicloEstructura($Pdo, $CicloDestinoId, $OfertaId, $ProgramaId, $EtapaId, (string)$GrupoPlantilla['Grupo'], (string)$GrupoPlantilla['Turno']);
    $GrupoDestinoId = SgceGrupoCrearOReactivar($Pdo, $CicloDestinoId, (string)$GrupoPlantilla['Grado'], (string)$GrupoPlantilla['Grupo'], (string)$GrupoPlantilla['Turno'], $EtapaId, $ProgramaId, $OfertaId);
    return [
        'GrupoDestinoId' => $GrupoDestinoId,
        'Creado' => !$Existente,
    ];
}

function SgceMigracionCopiarMateriasDesdeGrupo(PDO $Pdo, int $GrupoOrigenId, int $GrupoDestinoId, int $CicloOrigenId, int $CicloDestinoId): array {
    $Resultado = ['Copiadas' => 0, 'Existentes' => 0, 'Omitidas' => 0, 'TotalOrigen' => 0, 'Detalle' => []];
    if ($GrupoOrigenId <= 0 || $GrupoDestinoId <= 0 || $CicloOrigenId <= 0 || $CicloDestinoId <= 0) { return $Resultado; }
    $Materias = SgceMigracionMateriasActivasGrupo($Pdo, $CicloOrigenId, $GrupoOrigenId);
    $Resultado['TotalOrigen'] = count($Materias);
    foreach ($Materias as $Materia) {
        $MateriaNombre = (string)($Materia['MateriaNombre'] ?? '');
        $MateriaId = (int)($Materia['MateriaId'] ?? 0);
        $HorasSemana = (int)($Materia['HorasSemana'] ?? 0);
        try {
            $Existia = SgceMigracionMateriaGrupoDestinoEquivalente($Pdo, $GrupoDestinoId, $CicloDestinoId, $MateriaId, $MateriaNombre) > 0;
            $NuevoId = SgceMateriaGrupoCrearOReactivar($Pdo, $GrupoDestinoId, $MateriaNombre, $HorasSemana, $CicloDestinoId);
            if ($NuevoId > 0) {
                if ($Existia) { $Resultado['Existentes']++; }
                else { $Resultado['Copiadas']++; }
            } else {
                $Resultado['Omitidas']++;
                $Resultado['Detalle'][] = 'No se pudo crear materia: ' . $MateriaNombre;
            }
        } catch (Throwable $E) {
            $Resultado['Omitidas']++;
            $Resultado['Detalle'][] = $MateriaNombre . ': ' . $E->getMessage();
        }
    }
    return $Resultado;
}

function SgceMigracionCopiarAsignacionesDesdeGrupo(PDO $Pdo, int $GrupoOrigenId, int $GrupoDestinoId, int $CicloOrigenId, int $CicloDestinoId): array {
    $Resultado = ['Copiadas' => 0, 'OmitidasDocente' => 0, 'OmitidasMateria' => 0, 'OmitidasDuplicado' => 0, 'TotalOrigen' => 0];
    if ($GrupoOrigenId <= 0 || $GrupoDestinoId <= 0 || $CicloOrigenId <= 0 || $CicloDestinoId <= 0) { return $Resultado; }
    $StmtAsignaciones = $Pdo->prepare("SELECT A.MaestroId, A.MateriaNombre, A.MateriaId, A.HorasSemana, U.Activo AS MaestroActivo
        FROM Asignaciones A
        INNER JOIN Usuarios U ON U.Id = A.MaestroId AND U.Rol = 'maestro'
        WHERE A.CicloId = ? AND A.GrupoId = ? AND A.Activo = 1
        ORDER BY A.MateriaNombre, A.Id");
    $StmtAsignaciones->execute([$CicloOrigenId, $GrupoOrigenId]);
    $Asignaciones = $StmtAsignaciones->fetchAll();
    $Resultado['TotalOrigen'] = count($Asignaciones);
    $StmtInsertAsignacion = $Pdo->prepare('INSERT IGNORE INTO Asignaciones (CicloId, MaestroId, GrupoId, MateriaGrupoId, MateriaId, MateriaNombre, MateriaBusqueda, HorasSemana, Activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
    foreach ($Asignaciones as $Asig) {
        if ((int)$Asig['MaestroActivo'] !== 1) { $Resultado['OmitidasDocente']++; continue; }
        $MateriaGrupoDestinoId = SgceMigracionMateriaGrupoDestinoEquivalente($Pdo, $GrupoDestinoId, $CicloDestinoId, (int)$Asig['MateriaId'], (string)$Asig['MateriaNombre']);
        if ($MateriaGrupoDestinoId <= 0) { $Resultado['OmitidasMateria']++; continue; }
        $MateriaDestino = SgceMateriaGrupoObtener($Pdo, $MateriaGrupoDestinoId);
        if (!$MateriaDestino) { $Resultado['OmitidasMateria']++; continue; }
        $MateriaBusquedaDestino = function_exists('SgceTextoBusquedaNormalizado') ? SgceTextoBusquedaNormalizado((string)$MateriaDestino['MateriaNombre']) : (string)$MateriaDestino['MateriaNombre'];
        $StmtInsertAsignacion->execute([$CicloDestinoId, (int)$Asig['MaestroId'], $GrupoDestinoId, $MateriaGrupoDestinoId, (int)$MateriaDestino['MateriaId'], (string)$MateriaDestino['MateriaNombre'], $MateriaBusquedaDestino, (int)$MateriaDestino['HorasSemana']]);
        if ($StmtInsertAsignacion->rowCount() > 0) {
            $NuevaAsignacionId = (int)$Pdo->lastInsertId();
            SgceRegistrarDocenteAsignacionActual($Pdo, $NuevaAsignacionId, (int)$Asig['MaestroId'], 0, 'TITULAR', 'ASIGNACIÓN COPIADA COMO PLANTILLA AL NUEVO CICLO');
            $Resultado['Copiadas']++;
        } else {
            $Resultado['OmitidasDuplicado']++;
        }
    }
    return $Resultado;
}

function SgceMigracionAsegurarEstructuraCicloDestino(PDO $Pdo, int $CicloOrigenId, int $CicloDestinoId, bool $CopiarAsignaciones = false): array {
    $Resumen = [
        'GruposDestinoPreparados' => 0,
        'GruposDestinoCreados' => 0,
        'GruposDestinoExistentes' => 0,
        'GruposDestinoSinMaterias' => 0,
        'MateriasCopiadas' => 0,
        'MateriasExistentes' => 0,
        'MateriasOmitidas' => 0,
        'AsignacionesCopiadas' => 0,
        'AsignacionesOmitidasDocente' => 0,
        'AsignacionesOmitidasMateria' => 0,
        'AsignacionesOmitidasDuplicado' => 0,
        'Detalle' => [],
        'MapaGruposMismaEtapa' => [],
    ];
    $GruposOrigen = SgceGruposListarPorCiclo($Pdo, $CicloOrigenId, true);
    foreach ($GruposOrigen as $GrupoPlantilla) {
        $GrupoOrigenId = (int)$GrupoPlantilla['Id'];
        $GrupoDestino = SgceMigracionCrearGrupoDestinoDesdePlantilla($Pdo, $GrupoPlantilla, $CicloDestinoId);
        $GrupoDestinoId = (int)$GrupoDestino['GrupoDestinoId'];
        $Resumen['MapaGruposMismaEtapa'][$GrupoOrigenId] = $GrupoDestinoId;
        $Resumen['GruposDestinoPreparados']++;
        if (!empty($GrupoDestino['Creado'])) { $Resumen['GruposDestinoCreados']++; }
        else { $Resumen['GruposDestinoExistentes']++; }

        $Materias = SgceMigracionCopiarMateriasDesdeGrupo($Pdo, $GrupoOrigenId, $GrupoDestinoId, $CicloOrigenId, $CicloDestinoId);
        $Resumen['MateriasCopiadas'] += (int)$Materias['Copiadas'];
        $Resumen['MateriasExistentes'] += (int)$Materias['Existentes'];
        $Resumen['MateriasOmitidas'] += (int)$Materias['Omitidas'];
        if ((int)$Materias['TotalOrigen'] <= 0) {
            $Resumen['GruposDestinoSinMaterias']++;
            $Resumen['Detalle'][] = 'Sin materias plantilla: ' . trim((string)$GrupoPlantilla['Grado'] . ' ' . (string)$GrupoPlantilla['Grupo'] . ' ' . (string)$GrupoPlantilla['Turno']);
        }
        foreach (($Materias['Detalle'] ?? []) as $DetalleMateria) { $Resumen['Detalle'][] = $DetalleMateria; }

        if ($CopiarAsignaciones) {
            $Asignaciones = SgceMigracionCopiarAsignacionesDesdeGrupo($Pdo, $GrupoOrigenId, $GrupoDestinoId, $CicloOrigenId, $CicloDestinoId);
            $Resumen['AsignacionesCopiadas'] += (int)$Asignaciones['Copiadas'];
            $Resumen['AsignacionesOmitidasDocente'] += (int)$Asignaciones['OmitidasDocente'];
            $Resumen['AsignacionesOmitidasMateria'] += (int)$Asignaciones['OmitidasMateria'];
            $Resumen['AsignacionesOmitidasDuplicado'] += (int)$Asignaciones['OmitidasDuplicado'];
        }
    }
    return $Resumen;
}
function SgceMigrarGrupoSiguienteCicloInterno(PDO $Pdo, int $GrupoOrigenId, int $CicloDestinoId, bool $CopiarAsignaciones = false, bool $PermitirGrupoVacio = false, bool $PrepararMateriasDestino = true): array {
    $Origen = SgceGrupoObtenerPorId($Pdo, $GrupoOrigenId);
    $DestinoCiclo = SgceCicloPorId($Pdo, $CicloDestinoId);
    if (!$Origen) { throw new RuntimeException('El grupo origen no existe.'); }
    if (!$DestinoCiclo || (int)$DestinoCiclo['Activo'] !== 1) { throw new RuntimeException('Debe existir un ciclo destino activo.'); }
    if ((int)$Origen['CicloId'] === $CicloDestinoId) { throw new RuntimeException('El grupo origen ya pertenece al ciclo activo.'); }
    if ((int)$Origen['CicloActivo'] === 1) { throw new RuntimeException('No se puede migrar un grupo de un ciclo que todavía está activo. Primero crea/activa el nuevo ciclo.'); }

    $PeriodosDestino = SgceMigracionPeriodosPorCicloOferta($Pdo, $CicloDestinoId, (int)$Origen['OfertaId']);
    if (!$PeriodosDestino) { throw new RuntimeException('El ciclo destino no tiene periodos para la oferta del grupo origen.'); }

    $StmtLockGrupoOrigen = $Pdo->prepare('SELECT Id FROM Grupos WHERE Id = ? FOR UPDATE');
    $StmtLockGrupoOrigen->execute([$GrupoOrigenId]);
    $StmtLockCicloDestino = $Pdo->prepare('SELECT Id FROM CiclosEscolares WHERE Id = ? AND Activo = 1 FOR UPDATE');
    $StmtLockCicloDestino->execute([$CicloDestinoId]);

    $Resultado = [
        'GrupoOrigen' => $Origen,
        'GrupoDestinoId' => null,
        'NuevoGrado' => null,
        'Promovidos' => 0,
        'Egresados' => 0,
        'Omitidos' => 0,
        'Conflictos' => 0,
        'AsignacionesCopiadas' => 0,
        'AsignacionesOmitidasDocente' => 0,
        'AsignacionesOmitidasMateria' => 0,
        'AsignacionesOmitidasDuplicado' => 0,
        'MateriasCopiadas' => 0,
        'MateriasExistentes' => 0,
        'MateriasOmitidas' => 0,
        'GruposSinPlantillaMaterias' => 0,
        'KardexCongelados' => 0,
        'GrupoCreado' => false,
        'GrupoOmitidoSinAlumnos' => false,
    ];

    $Alumnos = SgceAlumnosPorGrupoCiclo($Pdo, $GrupoOrigenId, (int)$Origen['CicloId'], ['INSCRITO']);
    if (!$Alumnos) {
        $Resultado['GrupoOmitidoSinAlumnos'] = true;
        if ($PermitirGrupoVacio) { return $Resultado; }
        throw new RuntimeException('El grupo origen no tiene alumnos INSCRITOS para migrar.');
    }

    $EtapaOrigenId = (int)($Origen['EtapaId'] ?? 0);
    $EtapaSiguiente = $EtapaOrigenId > 0 ? SgceEtapaSiguiente($Pdo, $EtapaOrigenId) : null;

    if (!$EtapaSiguiente) {
        $StmtEgresar = $Pdo->prepare("UPDATE AlumnoInscripciones SET Estado = 'EGRESADO' WHERE AlumnoId = ? AND CicloId = ? AND GrupoId = ? AND Estado = 'INSCRITO'");
        $StmtAlumnoNull = $Pdo->prepare('UPDATE Alumnos SET GrupoId = NULL WHERE Id = ? AND GrupoId = ?');
        foreach ($Alumnos as $Alumno) {
            $AlumnoId = (int)$Alumno['Id'];
            $StmtEgresar->execute([$AlumnoId, (int)$Origen['CicloId'], $GrupoOrigenId]);
            if (SgceKardexCongelarAlumnoCiclo($Pdo, $AlumnoId, (int)$Origen['CicloId'], 0, true)) { $Resultado['KardexCongelados']++; }
            $StmtAlumnoNull->execute([$AlumnoId, $GrupoOrigenId]);
            $Resultado['Egresados']++;
        }
        return $Resultado;
    }

    $NuevoGrado = (string)$EtapaSiguiente['Nombre'];
    $OfertaId = (int)($EtapaSiguiente['OfertaId'] ?? ($Origen['OfertaId'] ?? 0));
    $ProgramaId = (int)($Origen['ProgramaId'] ?? 0);
    if ($ProgramaId <= 0) { $ProgramaId = SgceProgramaGeneralId($Pdo, $OfertaId); }
    $EtapaDestinoId = (int)($EtapaSiguiente['Id'] ?? 0);
    if ($EtapaDestinoId <= 0) { throw new RuntimeException('La etapa académica destino no es válida. Revisa la estructura académica.'); }

    $GrupoExistente = SgceGrupoObtenerPorCicloEstructura($Pdo, $CicloDestinoId, $OfertaId, $ProgramaId, $EtapaDestinoId, (string)$Origen['Grupo'], (string)$Origen['Turno']);
    $GrupoDestinoId = SgceGrupoCrearOReactivar($Pdo, $CicloDestinoId, $NuevoGrado, (string)$Origen['Grupo'], (string)$Origen['Turno'], $EtapaDestinoId, $ProgramaId, $OfertaId);
    $Resultado['GrupoDestinoId'] = $GrupoDestinoId;
    $Resultado['NuevoGrado'] = $NuevoGrado;
    $Resultado['GrupoCreado'] = !$GrupoExistente;

    $StmtPromoverOrigen = $Pdo->prepare("UPDATE AlumnoInscripciones SET Estado = 'PROMOVIDO' WHERE AlumnoId = ? AND CicloId = ? AND GrupoId = ? AND Estado = 'INSCRITO'");
    $StmtActualizarAlumno = $Pdo->prepare('UPDATE Alumnos SET GrupoId = ?, Activo = 1 WHERE Id = ?');
    foreach ($Alumnos as $Alumno) {
        $AlumnoId = (int)$Alumno['Id'];
        if (SgceAlumnoTieneInscripcion($Pdo, $AlumnoId, $CicloDestinoId)) {
            $Resultado['Conflictos']++;
            continue;
        }
        if (SgceAlumnoInscribirEnCiclo($Pdo, $AlumnoId, $CicloDestinoId, $GrupoDestinoId, 'INSCRITO')) {
            $StmtPromoverOrigen->execute([$AlumnoId, (int)$Origen['CicloId'], $GrupoOrigenId]);
            if (SgceKardexCongelarAlumnoCiclo($Pdo, $AlumnoId, (int)$Origen['CicloId'], 0, true)) { $Resultado['KardexCongelados']++; }
            $StmtActualizarAlumno->execute([$GrupoDestinoId, $AlumnoId]);
            $Resultado['Promovidos']++;
        } else {
            $Resultado['Omitidos']++;
        }
    }

    if ($PrepararMateriasDestino && $GrupoDestinoId > 0) {
        $GrupoPlantillaMaterias = SgceMigracionBuscarGrupoPlantilla($Pdo, (int)$Origen['CicloId'], $OfertaId, $ProgramaId, $EtapaDestinoId, (string)$Origen['Grupo'], (string)$Origen['Turno']);
        if ($GrupoPlantillaMaterias) {
            $Materias = SgceMigracionCopiarMateriasDesdeGrupo($Pdo, (int)$GrupoPlantillaMaterias['Id'], $GrupoDestinoId, (int)$Origen['CicloId'], $CicloDestinoId);
            $Resultado['MateriasCopiadas'] += (int)$Materias['Copiadas'];
            $Resultado['MateriasExistentes'] += (int)$Materias['Existentes'];
            $Resultado['MateriasOmitidas'] += (int)$Materias['Omitidas'];
            if ($CopiarAsignaciones) {
                $Asignaciones = SgceMigracionCopiarAsignacionesDesdeGrupo($Pdo, (int)$GrupoPlantillaMaterias['Id'], $GrupoDestinoId, (int)$Origen['CicloId'], $CicloDestinoId);
                $Resultado['AsignacionesCopiadas'] += (int)$Asignaciones['Copiadas'];
                $Resultado['AsignacionesOmitidasDocente'] += (int)$Asignaciones['OmitidasDocente'];
                $Resultado['AsignacionesOmitidasMateria'] += (int)$Asignaciones['OmitidasMateria'];
                $Resultado['AsignacionesOmitidasDuplicado'] += (int)$Asignaciones['OmitidasDuplicado'];
            }
        } else {
            $Resultado['GruposSinPlantillaMaterias']++;
            if ($CopiarAsignaciones) { $Resultado['AsignacionesOmitidasMateria']++; }
        }
    }

    return $Resultado;
}


function SgceMigrarCicloCompleto(PDO $Pdo, int $CicloOrigenId, int $CicloDestinoId, bool $CopiarAsignaciones = false): array {
    $Origen = SgceCicloPorId($Pdo, $CicloOrigenId);
    $Destino = SgceCicloPorId($Pdo, $CicloDestinoId);
    if (!$Origen || (int)$Origen['Activo'] === 1) { throw new RuntimeException('Selecciona un ciclo origen cerrado/inactivo.'); }
    if (!$Destino || (int)$Destino['Activo'] !== 1) { throw new RuntimeException('Debe existir un ciclo destino activo.'); }
    if ($CicloOrigenId === $CicloDestinoId) { throw new RuntimeException('El ciclo origen y destino no pueden ser el mismo.'); }

    // Primero se prepara el ciclo nuevo completo: todos los grupos, materias por grupo
    // y, si el administrador lo autoriza, las asignaciones/docentes como plantilla.
    // Esto conserva intacto el ciclo anterior y evita ciclos nuevos sin materias.
    $Preparacion = SgceMigracionAsegurarEstructuraCicloDestino($Pdo, $CicloOrigenId, $CicloDestinoId, $CopiarAsignaciones);

    $Resumen = [
        'GruposProcesados' => 0,
        'Promovidos' => 0,
        'Egresados' => 0,
        'Conflictos' => 0,
        'Omitidos' => 0,
        'AsignacionesCopiadas' => (int)$Preparacion['AsignacionesCopiadas'],
        'AsignacionesOmitidasDocente' => (int)$Preparacion['AsignacionesOmitidasDocente'],
        'AsignacionesOmitidasMateria' => (int)$Preparacion['AsignacionesOmitidasMateria'],
        'AsignacionesOmitidasDuplicado' => (int)$Preparacion['AsignacionesOmitidasDuplicado'],
        'AsignacionesOmitidas' => 0,
        'KardexCongelados' => 0,
        'GruposCreados' => (int)$Preparacion['GruposDestinoCreados'],
        'GruposDestinoPreparados' => (int)$Preparacion['GruposDestinoPreparados'],
        'GruposDestinoExistentes' => (int)$Preparacion['GruposDestinoExistentes'],
        'GruposDestinoSinMaterias' => (int)$Preparacion['GruposDestinoSinMaterias'],
        'MateriasCopiadas' => (int)$Preparacion['MateriasCopiadas'],
        'MateriasExistentes' => (int)$Preparacion['MateriasExistentes'],
        'MateriasOmitidas' => (int)$Preparacion['MateriasOmitidas'],
        'GruposOmitidosSinAlumnos' => 0,
    ];

    foreach (SgceGruposListarPorCiclo($Pdo, $CicloOrigenId, true) as $Grupo) {
        $R = SgceMigrarGrupoSiguienteCicloInterno($Pdo, (int)$Grupo['Id'], $CicloDestinoId, false, true, false);
        $Resumen['GruposProcesados']++;
        $Resumen['Promovidos'] += (int)$R['Promovidos'];
        $Resumen['Egresados'] += (int)$R['Egresados'];
        $Resumen['Conflictos'] += (int)$R['Conflictos'];
        $Resumen['Omitidos'] += (int)$R['Omitidos'];
        $Resumen['KardexCongelados'] += (int)($R['KardexCongelados'] ?? 0);
        $Resumen['GruposOmitidosSinAlumnos'] += !empty($R['GrupoOmitidoSinAlumnos']) ? 1 : 0;
    }
    $Resumen['AsignacionesOmitidas'] = $Resumen['AsignacionesOmitidasDocente'] + $Resumen['AsignacionesOmitidasMateria'] + $Resumen['AsignacionesOmitidasDuplicado'];
    if (($Resumen['Promovidos'] + $Resumen['Egresados']) <= 0) { throw new RuntimeException('La migración no movió alumnos porque no había alumnos INSCRITOS en el ciclo origen.'); }
    return $Resumen;
}

function SgceMigracionEjecutarGrupoBlindado(PDO $Pdo, array $UserSession, int $GrupoOrigenId, int $CicloDestinoId, bool $CopiarAsignaciones, string $Confirmacion): array {
    $Grupo = SgceGrupoObtenerPorId($Pdo, $GrupoOrigenId);
    $CicloOrigenId = (int)($Grupo['CicloId'] ?? 0);
    $Diagnostico = SgceMigracionDiagnosticar($Pdo, $CicloOrigenId, $CicloDestinoId, $GrupoOrigenId, $CopiarAsignaciones);
    SgceMigracionValidarDiagnostico($Diagnostico);
    SgceMigracionValidarConfirmacion($Confirmacion, $Diagnostico);
    if (!SgceMigracionObtenerLock($Pdo, 30)) { throw new RuntimeException('Ya hay una migración en proceso. Intenta nuevamente en unos minutos.'); }
    $RegistroId = 0;
    try {
        $Respaldo = SgceMigracionCrearRespaldoPrevio($Pdo, 'GRUPO');
        $RegistroId = SgceMigracionRegistrarInicio($Pdo, $UserSession, $Diagnostico, $Confirmacion, $Respaldo);
        $Pdo->beginTransaction();
        $R = SgceMigrarGrupoSiguienteCicloInterno($Pdo, $GrupoOrigenId, $CicloDestinoId, $CopiarAsignaciones, false);
        $Pdo->commit();
        $R['GruposProcesados'] = 1;
        $R['GruposCreados'] = !empty($R['GrupoCreado']) ? 1 : 0;
        $R['AsignacionesOmitidas'] = (int)($R['AsignacionesOmitidasDocente'] ?? 0) + (int)($R['AsignacionesOmitidasMateria'] ?? 0);
        SgceMigracionActualizarRegistro($Pdo, $RegistroId, 'COMPLETADA', $R, 'Migración de grupo completada correctamente.');
        return $R + ['RegistroMigracionId' => $RegistroId, 'RespaldoRuta' => $Respaldo];
    } catch (Throwable $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        if ($RegistroId > 0) { SgceMigracionActualizarRegistro($Pdo, $RegistroId, 'ERROR', ['Conflictos' => 1], $E->getMessage()); }
        throw $E;
    } finally {
        SgceMigracionLiberarLock($Pdo);
    }
}

function SgceMigracionEjecutarCicloBlindado(PDO $Pdo, array $UserSession, int $CicloOrigenId, int $CicloDestinoId, bool $CopiarAsignaciones, string $Confirmacion): array {
    $Diagnostico = SgceMigracionDiagnosticar($Pdo, $CicloOrigenId, $CicloDestinoId, 0, $CopiarAsignaciones);
    SgceMigracionValidarDiagnostico($Diagnostico);
    SgceMigracionValidarConfirmacion($Confirmacion, $Diagnostico);
    if (!SgceMigracionObtenerLock($Pdo, 30)) { throw new RuntimeException('Ya hay una migración en proceso. Intenta nuevamente en unos minutos.'); }
    $RegistroId = 0;
    try {
        $Respaldo = SgceMigracionCrearRespaldoPrevio($Pdo, 'CICLO');
        $RegistroId = SgceMigracionRegistrarInicio($Pdo, $UserSession, $Diagnostico, $Confirmacion, $Respaldo);
        $Pdo->beginTransaction();
        $R = SgceMigrarCicloCompleto($Pdo, $CicloOrigenId, $CicloDestinoId, $CopiarAsignaciones);
        $Pdo->commit();
        SgceMigracionActualizarRegistro($Pdo, $RegistroId, 'COMPLETADA', $R, 'Migración de ciclo completada correctamente.');
        return $R + ['RegistroMigracionId' => $RegistroId, 'RespaldoRuta' => $Respaldo];
    } catch (Throwable $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        if ($RegistroId > 0) { SgceMigracionActualizarRegistro($Pdo, $RegistroId, 'ERROR', ['Conflictos' => 1], $E->getMessage()); }
        throw $E;
    } finally {
        SgceMigracionLiberarLock($Pdo);
    }
}
