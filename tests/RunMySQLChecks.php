<?php
$Root = dirname(__DIR__);
$Host = getenv('SGCE_TEST_DB_HOST') ?: '';
$User = getenv('SGCE_TEST_DB_USER') ?: '';
$Pass = getenv('SGCE_TEST_DB_PASS') ?: '';
$Port = getenv('SGCE_TEST_DB_PORT') ?: '3306';

if ($Host === '' || $User === '') {
    echo "SGCE MYSQL CHECKS: SKIP\nConfigura SGCE_TEST_DB_HOST y SGCE_TEST_DB_USER para ejecutar prueba real con MySQL.\n";
    exit(0);
}

$DbName = 'sgce_test_' . date('Ymd_His') . '_' . random_int(1000, 9999);
$Errores = [];
$Revisiones = 0;

function SgceTestStatements(string $Sql): array {
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

function SgceTestCargarSchema(PDO $Pdo, string $Root): int {
    $Sql = file_get_contents($Root . '/install/SGCE.sql');
    $Total = 0;
    foreach (SgceTestStatements($Sql) as $Stmt) {
        $Pdo->exec($Stmt);
        $Total++;
    }
    return $Total;
}

function SgceTestInsertarEscenario(PDO $Pdo): array {
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
    $Pdo->exec("INSERT INTO Usuarios (Username, Password, NombreCompleto, NombreBusqueda, Rol, Activo) VALUES ('docente','$Hash','DOCENTE NOMBRE LARGO DE PRUEBA','DOCENTE NOMBRE LARGO DE PRUEBA','maestro',1)");
    $MaestroId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO Grupos (CicloId, OfertaId, ProgramaId, EtapaId, Grado, Grupo, Turno, Activo) VALUES ($CicloId,$OfertaId,$ProgramaId,$EtapaId,'1','A','VESPERTINO',1)");
    $GrupoId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO Alumnos (NombreCompleto, NombreBusqueda, GrupoId, Activo) VALUES ('ALUMNO CON NOMBRE LARGO PARA PRUEBA REAL','ALUMNO CON NOMBRE LARGO PARA PRUEBA REAL',$GrupoId,1)");
    $AlumnoId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO AlumnoInscripciones (AlumnoId, CicloId, GrupoId, OfertaId, ProgramaId, EtapaId, Estado) VALUES ($AlumnoId,$CicloId,$GrupoId,$OfertaId,$ProgramaId,$EtapaId,'INSCRITO')");
    $Pdo->exec("INSERT INTO MateriasCatalogo (Nombre, NombreBusqueda, Activo) VALUES ('MATEMÁTICAS CON NOMBRE LARGO DE PRUEBA','MATEMATICAS CON NOMBRE LARGO DE PRUEBA',1)");
    $MateriaId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO MateriasGrupo (CicloId, OfertaId, ProgramaId, EtapaId, GrupoId, MateriaId, MateriaNombre, MateriaBusqueda, HorasSemana, Activo) VALUES ($CicloId,$OfertaId,$ProgramaId,$EtapaId,$GrupoId,$MateriaId,'MATEMÁTICAS CON NOMBRE LARGO DE PRUEBA','MATEMATICAS CON NOMBRE LARGO DE PRUEBA',5,1)");
    $MateriaGrupoId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO Asignaciones (CicloId, MaestroId, GrupoId, MateriaGrupoId, MateriaId, MateriaNombre, MateriaBusqueda, HorasSemana, Activo) VALUES ($CicloId,$MaestroId,$GrupoId,$MateriaGrupoId,$MateriaId,'MATEMÁTICAS CON NOMBRE LARGO DE PRUEBA','MATEMATICAS CON NOMBRE LARGO DE PRUEBA',5,1)");
    $AsignacionId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO PeriodosEvaluacion (CicloId, OfertaId, Nombre, Orden, Activo) VALUES ($CicloId,$OfertaId,'TRIMESTRE 1',1,1)");
    $PeriodoId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO Calificaciones (AlumnoId, AsignacionId, PeriodoId, Calificacion) VALUES ($AlumnoId,$AsignacionId,$PeriodoId,9.50)");
    $Pdo->exec("INSERT INTO Asistencias (CicloId, AsignacionId, AlumnoId, Fecha, Estado) VALUES ($CicloId,$AsignacionId,$AlumnoId,'2026-09-01 08:00:00','A')");
    $Busqueda = 'ADMINISTRADOR PRUEBA PRUEBA_MYSQL TEST ESCENARIO DE PRUEBA MYSQL';
    $Pdo->exec("INSERT INTO BitacoraMovimientos (UsuarioId, Rol, Accion, TablaAfectada, RegistroId, Detalle, BusquedaTexto, Ip) VALUES ($AdminId,'admin','PRUEBA_MYSQL','TEST',1,'ESCENARIO DE PRUEBA MYSQL','$Busqueda','127.0.0.1')");
    return compact('CicloId','OfertaId','ProgramaId','EtapaId','GrupoId','AlumnoId','MateriaId','MateriaGrupoId','AsignacionId','PeriodoId','AdminId','MaestroId');
}

try {
    $Admin = new PDO("mysql:host=$Host;port=$Port;charset=utf8mb4", $User, $Pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $Admin->exec("CREATE DATABASE `$DbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $Pdo = new PDO("mysql:host=$Host;port=$Port;dbname=$DbName;charset=utf8mb4", $User, $Pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $Revisiones += SgceTestCargarSchema($Pdo, $Root);
    SgceTestInsertarEscenario($Pdo);
    foreach (['Usuarios','CiclosEscolares','Grupos','Alumnos','AlumnoInscripciones','MateriasGrupo','Asignaciones','Calificaciones','Asistencias','BitacoraMovimientos'] as $Tabla) {
        $Revisiones++;
        $Total = (int)$Pdo->query("SELECT COUNT(*) FROM `$Tabla`")->fetchColumn();
        if ($Total < 1) { $Errores[] = "Tabla sin datos en prueba MySQL: $Tabla"; }
    }
} catch (Throwable $E) {
    $Errores[] = $E->getMessage();
} finally {
    if (isset($Admin) && $DbName !== '') { try { $Admin->exec("DROP DATABASE IF EXISTS `$DbName`"); } catch (Throwable $E) {} }
}

if ($Errores) { echo "SGCE MYSQL CHECKS: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "SGCE MYSQL CHECKS: OK\nBase temporal: $DbName\nRevisiones ejecutadas: $Revisiones\n";
