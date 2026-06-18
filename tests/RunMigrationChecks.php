<?php
$Root = dirname(__DIR__);
$Errores = [];
$Revisiones = 0;

function CheckFileContains(string $Root, string $File, array $Needles, array &$Errores, int &$Revisiones): void {
    $Path = $Root . '/' . $File;
    $Revisiones++;
    if (!is_file($Path)) { $Errores[] = "Falta archivo requerido: $File"; return; }
    $Contenido = file_get_contents($Path);
    foreach ($Needles as $Needle) {
        $Revisiones++;
        if (!str_contains($Contenido, $Needle)) { $Errores[] = "No se encontró '$Needle' en $File"; }
    }
}

CheckFileContains($Root, 'install/SGCE.sql', ['CREATE TABLE MigracionesCiclo', 'idx_migraciones_origen_destino', 'HuellaCompletada', 'uk_migraciones_completadas'], $Errores, $Revisiones);
CheckFileContains($Root, 'services/migracion/MigracionService.php', ['SgceMigracionDiagnosticar', 'SgceMigracionCopiarPeriodosDesdeOrigen', 'SgceMigracionAsegurarEstructuraCicloDestino', 'SgceMigracionCopiarMateriasDesdeGrupo', 'SgceMigracionEjecutarCicloBlindado', 'GET_LOCK', 'PreMigracion_Datos_', 'SgceCrearRespaldoSql($Pdo, $Archivo, true)', 'uk_migraciones_completadas'], $Errores, $Revisiones);
CheckFileContains($Root, 'modules/MigracionAdmin.php', ['Diagnóstico previo obligatorio', 'Materias a copiar', 'Copiar periodos del ciclo origen al destino', 'ConfirmacionMigracion', 'SimularMigracion'], $Errores, $Revisiones);
CheckFileContains($Root, 'modules/HistorialAlumno.php', ['PERIODOS NO CONFIGURADOS', 'Ir a ciclos y periodos'], $Errores, $Revisiones);
CheckFileContains($Root, 'docs/MIGRACION_CICLO_ESCOLAR.md', ['Respaldo obligatorio', 'Doble migración', 'Copia de materias por grupo', 'Copia segura de asignaciones'], $Errores, $Revisiones);

$Host = getenv('SGCE_TEST_DB_HOST') ?: '';
$User = getenv('SGCE_TEST_DB_USER') ?: '';
$Pass = getenv('SGCE_TEST_DB_PASS') ?: '';
$Port = getenv('SGCE_TEST_DB_PORT') ?: '3306';

if ($Host !== '' && $User !== '') {
    $DbName = 'sgce_migration_test_' . date('Ymd_His') . '_' . random_int(1000, 9999);
    try {
        $Admin = new PDO("mysql:host=$Host;port=$Port;charset=utf8mb4", $User, $Pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $Admin->exec("CREATE DATABASE `$DbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $Pdo = new PDO("mysql:host=$Host;port=$Port;dbname=$DbName;charset=utf8mb4", $User, $Pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $Sql = file_get_contents($Root . '/install/SGCE.sql');
        foreach (preg_split('/;\s*\n/', $Sql) as $Stmt) {
            $Stmt = trim($Stmt);
            if ($Stmt !== '') { $Pdo->exec($Stmt); $Revisiones++; }
        }
        $Hash = password_hash('Tecnica_101', PASSWORD_DEFAULT);
        $Pdo->exec("INSERT INTO Usuarios (Username, Password, NombreCompleto, NombreBusqueda, Rol, Activo) VALUES ('admin','$Hash','ADMIN PRUEBA','ADMIN PRUEBA','admin',1)");
        $Pdo->exec("INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES ('2026-2027','2026-08-01','2027-07-31',0),('2027-2028','2027-08-01','2028-07-31',1)");
        $CicloOrigen = 1; $CicloDestino = 2;
        $Pdo->exec("INSERT INTO OfertasEducativas (Nombre, NivelEducativo, TipoPeriodizacion, TotalEtapas, EtiquetaEtapa, UsaProgramas, Activo) VALUES ('SECUNDARIA TECNICA','SECUNDARIA','ANUAL',3,'AÑO',0,1)");
        $OfertaId = (int)$Pdo->lastInsertId();
        $Pdo->exec("INSERT INTO ConfiguracionesAcademicas (OfertaId, CantidadPeriodosEvaluacion, NombreBasePeriodo, UsaPlaneaciones) VALUES ($OfertaId,3,'TRIMESTRE',1)");
        $Pdo->exec("INSERT INTO ProgramasEducativos (OfertaId, Nombre, Clave, Activo) VALUES ($OfertaId,'GENERAL','GEN',1)");
        $ProgramaId = (int)$Pdo->lastInsertId();
        $Pdo->exec("INSERT INTO EtapasAcademicas (OfertaId, Nombre, Orden, EsTerminal, Activo) VALUES ($OfertaId,'1° AÑO',1,0,1),($OfertaId,'2° AÑO',2,0,1),($OfertaId,'3° AÑO',3,1,1)");
        $Pdo->exec("INSERT INTO Grupos (CicloId, OfertaId, ProgramaId, EtapaId, Grado, Grupo, Turno, Activo) VALUES ($CicloOrigen,$OfertaId,$ProgramaId,1,'1° AÑO','A','VESPERTINO',1)");
        $GrupoOrigen = (int)$Pdo->lastInsertId();
        $Pdo->exec("INSERT INTO Alumnos (NombreCompleto, NombreBusqueda, GrupoId, Activo) VALUES ('ALUMNO MIGRACION','ALUMNO MIGRACION',$GrupoOrigen,1)");
        $AlumnoId = (int)$Pdo->lastInsertId();
        $Pdo->exec("INSERT INTO AlumnoInscripciones (AlumnoId, CicloId, GrupoId, OfertaId, ProgramaId, EtapaId, Estado) VALUES ($AlumnoId,$CicloOrigen,$GrupoOrigen,$OfertaId,$ProgramaId,1,'INSCRITO')");
        // Destino sin periodos debe detectarse como bloqueo en el esquema/servicio si se prueba integrado en la aplicación.
        $Total = (int)$Pdo->query('SELECT COUNT(*) FROM MigracionesCiclo')->fetchColumn();
        if ($Total !== 0) { $Errores[] = 'MigracionesCiclo no inicia vacía.'; }
        $Revisiones++;
    } catch (Throwable $E) {
        $Errores[] = $E->getMessage();
    } finally {
        if (isset($Admin)) { try { $Admin->exec("DROP DATABASE IF EXISTS `$DbName`"); } catch (Throwable $E) {} }
    }
}

if ($Errores) { echo "SGCE MIGRATION CHECKS: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "SGCE MIGRATION CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
if ($Host === '' || $User === '') { echo "Nota: Prueba MySQL profunda omitida; configura SGCE_TEST_DB_HOST y SGCE_TEST_DB_USER para ejecutarla.\n"; }
