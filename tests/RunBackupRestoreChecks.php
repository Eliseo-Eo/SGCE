<?php
$Root = dirname(__DIR__);
$Host = getenv('SGCE_TEST_DB_HOST') ?: '';
$User = getenv('SGCE_TEST_DB_USER') ?: '';
$Pass = getenv('SGCE_TEST_DB_PASS') ?: '';
$Port = getenv('SGCE_TEST_DB_PORT') ?: '3306';

if ($Host === '' || $User === '') {
    echo "SGCE BACKUP RESTORE CHECKS: SKIP\nConfigura SGCE_TEST_DB_HOST y SGCE_TEST_DB_USER para ejecutar prueba real de respaldo/restauración.\n";
    exit(0);
}

$SourceDb = 'sgce_backup_src_' . date('Ymd_His') . '_' . random_int(1000, 9999);
$TargetDb = 'sgce_backup_dst_' . date('Ymd_His') . '_' . random_int(1000, 9999);
$Errores = [];
$Revisiones = 0;

function SgceBRStatements(string $Sql): array {
    $Sql = preg_replace('/^\xEF\xBB\xBF/', '', $Sql);
    $Partes = [];
    $Buffer = '';
    $EnCadena = false;
    $Comilla = '';
    $Largo = strlen($Sql);
    for ($I = 0; $I < $Largo; $I++) {
        $C = $Sql[$I];
        $Prev = $I > 0 ? $Sql[$I - 1] : '';
        if (($C === "'" || $C === '"') && $Prev !== '\\') {
            if (!$EnCadena) { $EnCadena = true; $Comilla = $C; }
            elseif ($Comilla === $C) { $EnCadena = false; $Comilla = ''; }
        }
        if ($C === ';' && !$EnCadena) {
            $Stmt = trim($Buffer);
            if ($Stmt !== '') { $Partes[] = $Stmt; }
            $Buffer = '';
            continue;
        }
        $Buffer .= $C;
    }
    $Stmt = trim($Buffer);
    if ($Stmt !== '') { $Partes[] = $Stmt; }
    return $Partes;
}

function SgceBRCargarSchema(PDO $Pdo, string $Root): int {
    $Total = 0;
    foreach (SgceBRStatements(file_get_contents($Root . '/install/SGCE.sql')) as $Stmt) {
        $Pdo->exec($Stmt);
        $Total++;
    }
    return $Total;
}

function SgceBRInsertarEscenario(PDO $Pdo): void {
    $Hash = password_hash('Tecnica_101', PASSWORD_DEFAULT);
    $Pdo->exec("INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES ('2026-2027','2026-08-01','2027-07-31',1)");
    $CicloId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO OfertasEducativas (Nombre, NivelEducativo, TipoPeriodizacion, TotalEtapas, EtiquetaEtapa, UsaProgramas, Activo) VALUES ('Secundaria General','SECUNDARIA','ANUAL',3,'AÑO',0,1)");
    $OfertaId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO ConfiguracionesAcademicas (OfertaId, CantidadPeriodosEvaluacion, NombreBasePeriodo, UsaPlaneaciones) VALUES ($OfertaId,3,'TRIMESTRE',1)");
    $Pdo->exec("INSERT INTO ProgramasEducativos (OfertaId, Nombre, Activo) VALUES ($OfertaId,'GENERAL',1)");
    $ProgramaId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO EtapasAcademicas (OfertaId, Nombre, Orden, EsTerminal, Activo) VALUES ($OfertaId,'1 AÑO',1,0,1)");
    $EtapaId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO Usuarios (Username, Password, NombreCompleto, NombreBusqueda, Rol, Activo) VALUES ('admin','$Hash','ADMINISTRADOR PRUEBA','ADMINISTRADOR PRUEBA','admin',1)");
    $AdminId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO Usuarios (Username, Password, NombreCompleto, NombreBusqueda, Rol, Activo) VALUES ('docente','$Hash','DOCENTE PRUEBA','DOCENTE PRUEBA','maestro',1)");
    $MaestroId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO Grupos (CicloId, OfertaId, ProgramaId, EtapaId, Grado, Grupo, Turno, Activo) VALUES ($CicloId,$OfertaId,$ProgramaId,$EtapaId,'1','A','VESPERTINO',1)");
    $GrupoId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO Alumnos (NombreCompleto, NombreBusqueda, GrupoId, Activo) VALUES ('ALUMNO RESPALDO RESTAURACION','ALUMNO RESPALDO RESTAURACION',$GrupoId,1)");
    $AlumnoId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO AlumnoInscripciones (AlumnoId, CicloId, GrupoId, OfertaId, ProgramaId, EtapaId, Estado) VALUES ($AlumnoId,$CicloId,$GrupoId,$OfertaId,$ProgramaId,$EtapaId,'INSCRITO')");
    $Pdo->exec("INSERT INTO MateriasCatalogo (Nombre, NombreBusqueda, Activo) VALUES ('ESPAÑOL RESPALDO','ESPANOL RESPALDO',1)");
    $MateriaId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO MateriasGrupo (CicloId, OfertaId, ProgramaId, EtapaId, GrupoId, MateriaId, MateriaNombre, MateriaBusqueda, HorasSemana, Activo) VALUES ($CicloId,$OfertaId,$ProgramaId,$EtapaId,$GrupoId,$MateriaId,'ESPAÑOL RESPALDO','ESPANOL RESPALDO',5,1)");
    $MateriaGrupoId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO Asignaciones (CicloId, MaestroId, GrupoId, MateriaGrupoId, MateriaId, MateriaNombre, MateriaBusqueda, HorasSemana, Activo) VALUES ($CicloId,$MaestroId,$GrupoId,$MateriaGrupoId,$MateriaId,'ESPAÑOL RESPALDO','ESPANOL RESPALDO',5,1)");
    $AsignacionId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO PeriodosEvaluacion (CicloId, OfertaId, Nombre, Orden, Activo) VALUES ($CicloId,$OfertaId,'TRIMESTRE 1',1,1)");
    $PeriodoId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO Calificaciones (AlumnoId, AsignacionId, PeriodoId, Calificacion) VALUES ($AlumnoId,$AsignacionId,$PeriodoId,9.00)");
    $Pdo->exec("INSERT INTO Asistencias (CicloId, AsignacionId, AlumnoId, Fecha, Estado) VALUES ($CicloId,$AsignacionId,$AlumnoId,'2026-09-02 08:00:00','A')");
    $Busqueda = 'ADMINISTRADOR PRUEBA PRUEBA_RESPALDO TEST RESPALDO Y RESTAURACION';
    $Pdo->exec("INSERT INTO BitacoraMovimientos (UsuarioId, Rol, Accion, TablaAfectada, RegistroId, Detalle, BusquedaTexto, Ip) VALUES ($AdminId,'admin','PRUEBA_RESPALDO','TEST',1,'RESPALDO Y RESTAURACION','$Busqueda','127.0.0.1')");
}

function SgceBRColumnasInsertables(PDO $Pdo, string $Tabla): array {
    $Columnas = [];
    foreach ($Pdo->query("SHOW COLUMNS FROM `$Tabla`")->fetchAll(PDO::FETCH_ASSOC) as $Col) {
        $Extra = strtolower((string)($Col['Extra'] ?? ''));
        if (str_contains($Extra, 'generated')) { continue; }
        $Columnas[] = $Col['Field'];
    }
    return $Columnas;
}

function SgceBRDumpDatos(PDO $Pdo): string {
    $Sql = "SET FOREIGN_KEY_CHECKS=0;\n";
    $Tablas = array_map(static fn($R) => array_values($R)[0], $Pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_NUM));
    foreach ($Tablas as $Tabla) {
        $Columnas = SgceBRColumnasInsertables($Pdo, $Tabla);
        if (!$Columnas) { continue; }
        $Select = '`' . implode('`,`', $Columnas) . '`';
        $Filas = $Pdo->query("SELECT $Select FROM `$Tabla`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($Filas as $Fila) {
            $Valores = [];
            foreach ($Columnas as $Columna) {
                $Valores[] = $Fila[$Columna] === null ? 'NULL' : $Pdo->quote((string)$Fila[$Columna]);
            }
            $Sql .= "INSERT INTO `$Tabla` ($Select) VALUES (" . implode(',', $Valores) . ");\n";
        }
    }
    $Sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $Sql;
}

try {
    $Admin = new PDO("mysql:host=$Host;port=$Port;charset=utf8mb4", $User, $Pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $Admin->exec("CREATE DATABASE `$SourceDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $Admin->exec("CREATE DATABASE `$TargetDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $Source = new PDO("mysql:host=$Host;port=$Port;dbname=$SourceDb;charset=utf8mb4", $User, $Pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $Target = new PDO("mysql:host=$Host;port=$Port;dbname=$TargetDb;charset=utf8mb4", $User, $Pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $Revisiones += SgceBRCargarSchema($Source, $Root);
    $Revisiones += SgceBRCargarSchema($Target, $Root);
    SgceBRInsertarEscenario($Source);
    $Dump = SgceBRDumpDatos($Source);
    foreach (SgceBRStatements($Dump) as $Stmt) { $Target->exec($Stmt); $Revisiones++; }
    foreach (['Usuarios','CiclosEscolares','Grupos','Alumnos','AlumnoInscripciones','MateriasGrupo','Asignaciones','Calificaciones','Asistencias','BitacoraMovimientos'] as $Tabla) {
        $Revisiones++;
        $A = (int)$Source->query("SELECT COUNT(*) FROM `$Tabla`")->fetchColumn();
        $B = (int)$Target->query("SELECT COUNT(*) FROM `$Tabla`")->fetchColumn();
        if ($A !== $B || $A < 1) { $Errores[] = "Conteo distinto tras restaurar $Tabla: origen=$A destino=$B"; }
    }
} catch (Throwable $E) {
    $Errores[] = $E->getMessage();
} finally {
    if (isset($Admin)) {
        try { $Admin->exec("DROP DATABASE IF EXISTS `$SourceDb`"); } catch (Throwable $E) {}
        try { $Admin->exec("DROP DATABASE IF EXISTS `$TargetDb`"); } catch (Throwable $E) {}
    }
}

if ($Errores) { echo "SGCE BACKUP RESTORE CHECKS: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "SGCE BACKUP RESTORE CHECKS: OK\nOrigen temporal: $SourceDb\nDestino temporal: $TargetDb\nRevisiones ejecutadas: $Revisiones\n";
