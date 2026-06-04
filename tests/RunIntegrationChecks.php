<?php
define('SGCE_APP', true);

$Root = dirname(__DIR__);
$Host = getenv('SGCE_TEST_DB_HOST') ?: '';
$User = getenv('SGCE_TEST_DB_USER') ?: '';
$Pass = getenv('SGCE_TEST_DB_PASS') ?: '';
$Charset = getenv('SGCE_TEST_DB_CHARSET') ?: 'utf8mb4';
$Prefix = preg_replace('/[^a-zA-Z0-9_]/', '', getenv('SGCE_TEST_DB_PREFIX') ?: 'sgce_test_');
$Database = $Prefix . date('Ymd_His') . '_' . random_int(1000, 9999);

if ($Host === '' || $User === '') {
    echo "SGCE INTEGRATION CHECKS: SKIP\n";
    echo "Configura SGCE_TEST_DB_HOST y SGCE_TEST_DB_USER para ejecutar una prueba real con MySQL temporal.\n";
    exit(0);
}

$Errores = [];
$Revisiones = 0;

function Check($Condicion, $Mensaje) {
    global $Errores, $Revisiones;
    $Revisiones++;
    if (!$Condicion) { $Errores[] = $Mensaje; }
}

function EjecutarSql(PDO $Pdo, string $Sql): void {
    $Sql = preg_replace('/^\s*--.*$/m', '', $Sql);
    $Partes = preg_split('/;\s*(?:\r?\n|$)/', $Sql);
    foreach ($Partes as $Parte) {
        $Parte = trim($Parte);
        if ($Parte === '') { continue; }
        $Pdo->exec($Parte);
    }
}

try {
    $PdoServidor = new PDO("mysql:host={$Host};charset={$Charset}", $User, $Pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $PdoServidor->exec("CREATE DATABASE `{$Database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $Pdo = new PDO("mysql:host={$Host};dbname={$Database};charset={$Charset}", $User, $Pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    EjecutarSql($Pdo, file_get_contents($Root . '/install/SGCE.sql'));

    $TablasEsperadas = [
        'Usuarios','CiclosEscolares','OfertasEducativas','ProgramasEducativos','EtapasAcademicas','Grupos',
        'Alumnos','AlumnoInscripciones','MateriasCatalogo','MateriasGrupo','Asignaciones','PeriodosEvaluacion',
        'Calificaciones','Asistencias','Avisos','Planeaciones','BitacoraMovimientos','IntentosSeguridad'
    ];

    foreach ($TablasEsperadas as $Tabla) {
        $Stmt = $Pdo->prepare('SHOW TABLES LIKE ?');
        $Stmt->execute([$Tabla]);
        Check((bool)$Stmt->fetchColumn(), 'Falta tabla instalada: ' . $Tabla);
    }

    $Pdo->beginTransaction();
    $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, NombreBusqueda, Rol, Activo) VALUES (?,?,?,?,?,1)")
        ->execute(['AdminPrueba', password_hash('Tecnica_101', PASSWORD_DEFAULT), 'ADMIN PRUEBA', 'ADMIN PRUEBA', 'admin']);
    $AdminId = (int)$Pdo->lastInsertId();

    $Pdo->prepare("INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES (?,?,?,1)")
        ->execute(['2026-2027','2026-08-01','2027-07-31']);
    $CicloId = (int)$Pdo->lastInsertId();

    $Pdo->prepare("INSERT INTO OfertasEducativas (Nombre, NivelEducativo, TipoPeriodizacion, TotalEtapas, EtiquetaEtapa, UsaProgramas, Activo) VALUES (?,?,?,?,?,?,1)")
        ->execute(['SECUNDARIA GENERAL','SECUNDARIA','ANUAL',3,'AÑO',0]);
    $OfertaId = (int)$Pdo->lastInsertId();

    $Pdo->prepare("INSERT INTO ConfiguracionesAcademicas (OfertaId, CantidadPeriodosEvaluacion, NombreBasePeriodo, UsaPlaneaciones, TipoPlaneacion, PlaneacionesCantidad, Activo) VALUES (?,?,?,?,?,?,1)")
        ->execute([$OfertaId,3,'TRIMESTRE',1,'PERIODO',3]);

    $Pdo->prepare("INSERT INTO ProgramasEducativos (OfertaId, Nombre, Clave, Activo) VALUES (?,?,?,1)")
        ->execute([$OfertaId,'GENERAL','GEN']);
    $ProgramaId = (int)$Pdo->lastInsertId();

    $Pdo->prepare("INSERT INTO EtapasAcademicas (OfertaId, Nombre, Orden, EsTerminal, Activo) VALUES (?,?,?,?,1)")
        ->execute([$OfertaId,'1° AÑO',1,0]);
    $EtapaId = (int)$Pdo->lastInsertId();

    $Pdo->prepare("INSERT INTO Grupos (CicloId, OfertaId, ProgramaId, EtapaId, Grado, Grupo, Turno, Activo) VALUES (?,?,?,?,?,?,?,1)")
        ->execute([$CicloId,$OfertaId,$ProgramaId,$EtapaId,'1° AÑO','A','MATUTINO']);
    $GrupoId = (int)$Pdo->lastInsertId();

    $Pdo->prepare("INSERT INTO Alumnos (NombreCompleto, NombreBusqueda, GrupoId, Activo) VALUES (?,?,?,1)")
        ->execute(['ALUMNO PRUEBA UNO','ALUMNO PRUEBA UNO',$GrupoId]);
    $AlumnoId = (int)$Pdo->lastInsertId();

    $Pdo->prepare("INSERT INTO AlumnoInscripciones (AlumnoId, CicloId, GrupoId, OfertaId, ProgramaId, EtapaId, Estado) VALUES (?,?,?,?,?,?,?)")
        ->execute([$AlumnoId,$CicloId,$GrupoId,$OfertaId,$ProgramaId,$EtapaId,'INSCRITO']);

    $Pdo->prepare("INSERT INTO MateriasCatalogo (Nombre, NombreBusqueda, Activo) VALUES (?,?,1)")
        ->execute(['ESPAÑOL 1','ESPAÑOL 1']);
    $MateriaId = (int)$Pdo->lastInsertId();

    $Pdo->prepare("INSERT INTO MateriasGrupo (CicloId, OfertaId, ProgramaId, EtapaId, GrupoId, MateriaId, MateriaNombre, MateriaBusqueda, HorasSemana, Activo) VALUES (?,?,?,?,?,?,?,?,?,1)")
        ->execute([$CicloId,$OfertaId,$ProgramaId,$EtapaId,$GrupoId,$MateriaId,'ESPAÑOL 1','ESPAÑOL 1',5]);
    $MateriaGrupoId = (int)$Pdo->lastInsertId();

    $Pdo->prepare("INSERT INTO Asignaciones (CicloId, MaestroId, GrupoId, MateriaGrupoId, MateriaNombre, HorasSemana, Activo) VALUES (?,?,?,?,?,?,1)")
        ->execute([$CicloId,$AdminId,$GrupoId,$MateriaGrupoId,'ESPAÑOL 1',5]);
    $AsignacionId = (int)$Pdo->lastInsertId();

    $Pdo->prepare("INSERT INTO PeriodosEvaluacion (CicloId, OfertaId, Nombre, Orden, Activo) VALUES (?,?,?,?,1)")
        ->execute([$CicloId,$OfertaId,'TRIMESTRE 1',1]);
    $PeriodoId = (int)$Pdo->lastInsertId();

    $Pdo->prepare("INSERT INTO Calificaciones (AlumnoId, AsignacionId, PeriodoId, Calificacion) VALUES (?,?,?,?)")
        ->execute([$AlumnoId,$AsignacionId,$PeriodoId,9.5]);
    $Pdo->prepare("INSERT INTO Asistencias (AlumnoId, AsignacionId, Fecha, Estado) VALUES (?,?,?,?)")
        ->execute([$AlumnoId,$AsignacionId,date('Y-m-d'),'PRESENTE']);

    Check((int)$Pdo->query('SELECT COUNT(*) FROM Calificaciones')->fetchColumn() === 1, 'No se insertó calificación de prueba.');
    Check((int)$Pdo->query('SELECT COUNT(*) FROM Asistencias')->fetchColumn() === 1, 'No se insertó asistencia de prueba.');
    Check((int)$Pdo->query('SELECT COUNT(*) FROM AlumnoInscripciones')->fetchColumn() === 1, 'No se insertó inscripción histórica de prueba.');
    $Pdo->rollBack();
} catch (Throwable $E) {
    $Errores[] = $E->getMessage();
} finally {
    if (isset($PdoServidor) && $PdoServidor instanceof PDO) {
        try { $PdoServidor->exec("DROP DATABASE IF EXISTS `{$Database}`"); } catch (Throwable $E) {}
    }
}

if ($Errores) {
    echo "SGCE INTEGRATION CHECKS: ERROR\n" . implode("\n", $Errores) . "\n";
    exit(1);
}

echo "SGCE INTEGRATION CHECKS: OK\nBase temporal: {$Database}\nRevisiones ejecutadas: {$Revisiones}\n";
