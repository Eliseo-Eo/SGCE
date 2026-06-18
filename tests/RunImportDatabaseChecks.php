<?php
$Root = dirname(__DIR__);
$Host = getenv('SGCE_TEST_DB_HOST') ?: '';
$User = getenv('SGCE_TEST_DB_USER') ?: '';
$Pass = getenv('SGCE_TEST_DB_PASS') ?: '';
$Port = getenv('SGCE_TEST_DB_PORT') ?: '3306';

if ($Host === '' || $User === '') {
    echo "SGCE IMPORT DATABASE CHECKS: SKIP\nConfigura SGCE_TEST_DB_HOST y SGCE_TEST_DB_USER para ejecutar importaciones reales sobre MySQL temporal.\n";
    exit(0);
}

if (!defined('SGCE_APP')) { define('SGCE_APP', true); }
require_once $Root . '/includes/SGCE_Texto.php';
require_once $Root . '/includes/SGCE_SearchHelpers.php';

$DbName = 'sgce_import_test_' . date('Ymd_His') . '_' . random_int(1000, 9999);
$Errores = [];
$Revisiones = 0;

function SgceImpStatements(string $Sql): array {
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

function SgceImpCargarSchema(PDO $Pdo, string $Root): int {
    $Total = 0;
    foreach (SgceImpStatements(file_get_contents($Root . '/install/SGCE.sql')) as $Stmt) {
        $Pdo->exec($Stmt);
        $Total++;
    }
    return $Total;
}

function SgceImpLeerCsv(string $Ruta): array {
    $Filas = [];
    $Handle = fopen($Ruta, 'r');
    if (!$Handle) { throw new RuntimeException('No se pudo abrir fixture: ' . basename($Ruta)); }
    while (($Row = fgetcsv($Handle)) !== false) { $Filas[] = $Row; }
    fclose($Handle);
    return $Filas;
}

function SgceImpPrepararBase(PDO $Pdo): array {
    $Hash = password_hash('Tecnica_101', PASSWORD_DEFAULT);
    $StmtConfig = $Pdo->prepare('INSERT INTO ConfiguracionSistema (Clave, Valor) VALUES (?, ?)');
    foreach ([
        'NombreEscuela' => 'ESCUELA PRUEBA',
        'ClaveCentroTrabajo' => 'TEST',
        'MunicipioEstado' => 'JALISCO',
        'NombreDirector' => 'DIRECTOR PRUEBA',
        'ColorPrimario' => '#97051E',
    ] as $Clave => $Valor) { $StmtConfig->execute([$Clave, $Valor]); }
    $Pdo->exec("INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES ('2026-2027','2026-08-01','2027-07-31',1)");
    $CicloId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO OfertasEducativas (Nombre, NivelEducativo, TipoPeriodizacion, TotalEtapas, EtiquetaEtapa, UsaProgramas, Activo) VALUES ('Secundaria General','SECUNDARIA','ANUAL',3,'AÑO',0,1)");
    $OfertaId = (int)$Pdo->lastInsertId();
    $Pdo->exec("INSERT INTO ConfiguracionesAcademicas (OfertaId, CantidadPeriodosEvaluacion, NombreBasePeriodo, UsaPlaneaciones) VALUES ($OfertaId,3,'TRIMESTRE',1)");
    $Pdo->exec("INSERT INTO ProgramasEducativos (OfertaId, Nombre, Activo) VALUES ($OfertaId,'GENERAL',1)");
    $ProgramaId = (int)$Pdo->lastInsertId();
    $Etapas = [];
    for ($I = 1; $I <= 3; $I++) {
        $Terminal = $I === 3 ? 1 : 0;
        $Pdo->exec("INSERT INTO EtapasAcademicas (OfertaId, Nombre, Orden, EsTerminal, Activo) VALUES ($OfertaId,'$I AÑO',$I,$Terminal,1)");
        $Etapas[$I] = (int)$Pdo->lastInsertId();
    }
    $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, NombreBusqueda, Rol, Activo) VALUES ('admin', ?, 'ADMINISTRADOR PRUEBA', 'ADMINISTRADOR PRUEBA', 'admin', 1)")->execute([$Hash]);
    return compact('CicloId','OfertaId','ProgramaId','Etapas');
}

function SgceImpImportarDocentes(PDO $Pdo, string $Root): int {
    $Filas = SgceImpLeerCsv($Root . '/tests/fixtures/plantilla_docentes.csv');
    array_shift($Filas);
    $Stmt = $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, NombreBusqueda, Rol, Activo) VALUES (?, ?, ?, ?, 'maestro', 1)");
    $Total = 0;
    foreach ($Filas as $Fila) {
        [$Nombre, $Username, $Password] = array_pad($Fila, 3, '');
        $Nombre = SgceNormalizarNombre($Nombre);
        $Username = trim((string)$Username);
        if ($Nombre === '' || $Username === '' || trim((string)$Password) === '') { continue; }
        $Stmt->execute([$Username, password_hash((string)$Password, PASSWORD_DEFAULT), $Nombre, SgceTextoBusquedaNormalizado($Nombre)]);
        $Total++;
    }
    return $Total;
}

function SgceImpImportarGrupos(PDO $Pdo, string $Root, array $Ctx): array {
    $Filas = SgceImpLeerCsv($Root . '/tests/fixtures/plantilla_grupos.csv');
    array_shift($Filas);
    $Stmt = $Pdo->prepare('INSERT INTO Grupos (CicloId, OfertaId, ProgramaId, EtapaId, Grado, Grupo, Turno, Activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
    $Mapa = [];
    foreach ($Filas as $Fila) {
        [$Grado, $Grupo, $Turno] = array_pad($Fila, 3, '');
        $Orden = max(1, min(3, (int)SgceNormalizarEtapaAcademica($Grado)));
        $Grupo = SgceNormalizarGrupo($Grupo);
        $Turno = SgceNormalizarTurno($Turno);
        $Stmt->execute([$Ctx['CicloId'], $Ctx['OfertaId'], $Ctx['ProgramaId'], $Ctx['Etapas'][$Orden], (string)$Orden, $Grupo, $Turno]);
        $Mapa[$Orden . '|' . $Grupo . '|' . $Turno] = (int)$Pdo->lastInsertId();
    }
    return $Mapa;
}

function SgceImpImportarMaterias(PDO $Pdo, string $Root, array $Ctx, array $Grupos): int {
    $Filas = SgceImpLeerCsv($Root . '/tests/fixtures/plantilla_materias.csv');
    array_shift($Filas);
    $StmtCat = $Pdo->prepare('INSERT INTO MateriasCatalogo (Nombre, NombreBusqueda, Activo) VALUES (?, ?, 1)');
    $StmtMat = $Pdo->prepare('INSERT INTO MateriasGrupo (CicloId, OfertaId, ProgramaId, EtapaId, GrupoId, MateriaId, MateriaNombre, MateriaBusqueda, HorasSemana, Activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
    $Total = 0;
    foreach ($Filas as $Fila) {
        [$Materia, $Grado, $Grupo, $Turno, $Horas] = array_pad($Fila, 5, '');
        $Materia = SgceNormalizarMayusculas($Materia);
        $Orden = max(1, min(3, (int)SgceNormalizarEtapaAcademica($Grado)));
        $Grupo = SgceNormalizarGrupo($Grupo);
        $Turno = SgceNormalizarTurno($Turno);
        $GrupoId = $Grupos[$Orden . '|' . $Grupo . '|' . $Turno] ?? 0;
        if ($Materia === '' || $GrupoId <= 0) { continue; }
        $Busqueda = SgceTextoBusquedaNormalizado($Materia);
        $StmtCat->execute([$Materia, $Busqueda]);
        $MateriaId = (int)$Pdo->lastInsertId();
        $StmtMat->execute([$Ctx['CicloId'], $Ctx['OfertaId'], $Ctx['ProgramaId'], $Ctx['Etapas'][$Orden], $GrupoId, $MateriaId, $Materia, $Busqueda, max(1, min(40, (int)$Horas))]);
        $Total++;
    }
    return $Total;
}

function SgceImpImportarAlumnos(PDO $Pdo, string $Root, array $Ctx, array $Grupos): int {
    $Filas = SgceImpLeerCsv($Root . '/tests/fixtures/plantilla_alumnos.csv');
    array_shift($Filas);
    $StmtAl = $Pdo->prepare('INSERT INTO Alumnos (NombreCompleto, NombreBusqueda, GrupoId, Activo) VALUES (?, ?, ?, 1)');
    $StmtIn = $Pdo->prepare("INSERT INTO AlumnoInscripciones (AlumnoId, CicloId, GrupoId, OfertaId, ProgramaId, EtapaId, Estado) VALUES (?, ?, ?, ?, ?, ?, 'INSCRITO')");
    $Total = 0;
    foreach ($Filas as $Fila) {
        [$Nombre, $Grado, $Grupo, $Turno] = array_pad($Fila, 4, '');
        $Nombre = SgceNormalizarNombre($Nombre);
        $Orden = max(1, min(3, (int)SgceNormalizarEtapaAcademica($Grado)));
        $Grupo = SgceNormalizarGrupo($Grupo);
        $Turno = SgceNormalizarTurno($Turno);
        $GrupoId = $Grupos[$Orden . '|' . $Grupo . '|' . $Turno] ?? 0;
        if ($Nombre === '' || $GrupoId <= 0) { continue; }
        $StmtAl->execute([$Nombre, SgceTextoBusquedaNormalizado($Nombre), $GrupoId]);
        $AlumnoId = (int)$Pdo->lastInsertId();
        $StmtIn->execute([$AlumnoId, $Ctx['CicloId'], $GrupoId, $Ctx['OfertaId'], $Ctx['ProgramaId'], $Ctx['Etapas'][$Orden]]);
        $Total++;
    }
    return $Total;
}

try {
    $Admin = new PDO("mysql:host=$Host;port=$Port;charset=utf8mb4", $User, $Pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $Admin->exec("CREATE DATABASE `$DbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $Pdo = new PDO("mysql:host=$Host;port=$Port;dbname=$DbName;charset=utf8mb4", $User, $Pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $Revisiones += SgceImpCargarSchema($Pdo, $Root);
    $Ctx = SgceImpPrepararBase($Pdo);
    $Docentes = SgceImpImportarDocentes($Pdo, $Root);
    $Grupos = SgceImpImportarGrupos($Pdo, $Root, $Ctx);
    $Materias = SgceImpImportarMaterias($Pdo, $Root, $Ctx, $Grupos);
    $Alumnos = SgceImpImportarAlumnos($Pdo, $Root, $Ctx, $Grupos);
    $Esperados = ['Usuarios' => 3, 'Grupos' => 4, 'MateriasGrupo' => 3, 'Alumnos' => 3, 'AlumnoInscripciones' => 3];
    foreach ($Esperados as $Tabla => $Minimo) {
        $Revisiones++;
        $Total = (int)$Pdo->query("SELECT COUNT(*) FROM `$Tabla`")->fetchColumn();
        if ($Total < $Minimo) { $Errores[] = "Importación insuficiente en $Tabla: esperado mínimo $Minimo, obtenido $Total"; }
    }
    $Revisiones += $Docentes + count($Grupos) + $Materias + $Alumnos;
} catch (Throwable $E) {
    $Errores[] = $E->getMessage();
} finally {
    if (isset($Admin) && $DbName !== '') { try { $Admin->exec("DROP DATABASE IF EXISTS `$DbName`"); } catch (Throwable $E) {} }
}

if ($Errores) { echo "SGCE IMPORT DATABASE CHECKS: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "SGCE IMPORT DATABASE CHECKS: OK\nBase temporal: $DbName\nRevisiones ejecutadas: $Revisiones\n";
