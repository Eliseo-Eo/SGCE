<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'respaldos', 'Solo el administrador puede descargar respaldos de la base de datos.');

RegistrarBitacora($Pdo, $UserSession, 'GENERAR_RESPALDO_DATOS', 'BASE_DE_DATOS', null, 'RESPALDO SOLO DATOS DESCARGADO');

function QTablaDatos($Tabla) {
    return '`' . str_replace('`', '``', $Tabla) . '`';
}

function ColumnasInsertablesDatos($Pdo, $Tabla) {
    $Stmt = $Pdo->query('SHOW COLUMNS FROM ' . QTablaDatos($Tabla));
    $Columnas = [];
    while ($Col = $Stmt->fetch(PDO::FETCH_ASSOC)) {
        $Extra = strtolower((string)($Col['Extra'] ?? ''));
        if (strpos($Extra, 'generated') !== false) {
            continue;
        }
        $Columnas[] = $Col['Field'];
    }
    return $Columnas;
}

function ValorSqlDatos($Pdo, $Tabla, $Columna, $Valor) {
    if ($Tabla === 'Usuarios' && in_array($Columna, ['SessionToken','SessionTokenExpira'], true)) {
        return 'NULL';
    }
    if ($Valor === null) {
        return 'NULL';
    }
    return $Pdo->quote((string)$Valor);
}

function LlavePrimariaDatos($Pdo, $Tabla) {
    $Stmt = $Pdo->query('SHOW KEYS FROM ' . QTablaDatos($Tabla) . " WHERE Key_name = 'PRIMARY'");
    $Pk = [];
    while ($Row = $Stmt->fetch(PDO::FETCH_ASSOC)) {
        $Pk[] = $Row['Column_name'];
    }
    return $Pk;
}

$TablasPreferidas = [
    'ConfiguracionSistema',
    'Usuarios',
    'CiclosEscolares',
    'OfertasEducativas',
    'ConfiguracionesAcademicas',
    'ProgramasEducativos',
    'EtapasAcademicas',
    'Grupos',
    'Alumnos',
    'AlumnoInscripciones',
    'MateriasCatalogo',
    'MateriasGrupo',
    'Asignaciones',
    'AsignacionDocenteHistorial',
    'PeriodosEvaluacion',
    'MigracionesCiclo',
    'Calificaciones',
    'Asistencias',
    'ConductaRegistros',
    'KardexAlumno',
    'KardexDetalle',
    'Avisos',
    'Planeaciones',
    'BitacoraMovimientos',
    'BitacoraMovimientosArchivo',
    'IntentosSeguridad'
];

$TablasExistentes = array_map(function($R){ return $R[0]; }, $Pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM));
$Tablas = [];
foreach ($TablasPreferidas as $Tabla) {
    if (in_array($Tabla, $TablasExistentes, true)) {
        $Tablas[] = $Tabla;
    }
}
foreach ($TablasExistentes as $Tabla) {
    if (!in_array($Tabla, $Tablas, true)) {
        $Tablas[] = $Tabla;
    }
}

$NombreArchivo = 'Datos_SGCE_' . date('Ymd_His') . '.sql';
$RutaTemporal = tempnam(sys_get_temp_dir(), 'sgce_sql_');
if ($RutaTemporal === false) { http_response_code(500); exit('No se pudo preparar el respaldo temporal.'); }
$HandleSql = fopen($RutaTemporal, 'wb');
if (!$HandleSql) { @unlink($RutaTemporal); http_response_code(500); exit('No se pudo escribir el respaldo temporal.'); }
function EscribirSqlExport($Texto) { global $HandleSql; fwrite($HandleSql, (string)$Texto); }

EscribirSqlExport("-- ============================================================\n");
EscribirSqlExport("-- RESPALDO SGCE SOLO DATOS\n");
EscribirSqlExport("-- GENERADO: " . date('Y-m-d H:i:s') . "\n");
EscribirSqlExport("-- RestaurarBD.php permite fusionar o reemplazar datos con este archivo.\n");
EscribirSqlExport("-- NOTA: NO SE RESPALDAN TOKENS DE SESIÓN ACTIVOS.\n");
EscribirSqlExport("-- ============================================================\n\n");
EscribirSqlExport("-- NOTA: LAS CONTRASEÑAS SE RESPALDAN COMO HASH SEGURO, NO EN TEXTO PLANO.\n");
EscribirSqlExport("SET FOREIGN_KEY_CHECKS=0;\n");
EscribirSqlExport("SET NAMES utf8mb4;\n\n");

foreach ($Tablas as $Tabla) {
    $Columnas = ColumnasInsertablesDatos($Pdo, $Tabla);
    if (!$Columnas) { continue; }

    $Pk = LlavePrimariaDatos($Pdo, $Tabla);
    $ColumnasSql = array_map(function($Col){ return '`' . str_replace('`', '``', $Col) . '`'; }, $Columnas);
    $ColumnasSelect = implode(',', $ColumnasSql);

    EscribirSqlExport("-- ------------------------------------------------------------\n");
    EscribirSqlExport("-- DATOS: `" . str_replace('`','``',$Tabla) . "`\n");
    EscribirSqlExport("-- ------------------------------------------------------------\n");

    $StmtDatos = $Pdo->query('SELECT ' . $ColumnasSelect . ' FROM ' . QTablaDatos($Tabla));
    while ($Fila = $StmtDatos->fetch(PDO::FETCH_ASSOC)) {
        $Valores = [];
        foreach ($Columnas as $Columna) {
            $Valores[] = ValorSqlDatos($Pdo, $Tabla, $Columna, $Fila[$Columna] ?? null);
        }

        $UpdateParts = [];
        foreach ($Columnas as $Columna) {
            if (in_array($Columna, $Pk, true)) { continue; }
            if ($Tabla === 'Usuarios' && in_array($Columna, ['SessionToken','SessionTokenExpira'], true)) {
                $ColSql = '`' . str_replace('`','``',$Columna) . '`';
                $UpdateParts[] = $ColSql . '=NULL';
                continue;
            }
            $ColSql = '`' . str_replace('`','``',$Columna) . '`';
            $UpdateParts[] = $ColSql . '=VALUES(' . $ColSql . ')';
        }

        EscribirSqlExport('INSERT INTO ' . QTablaDatos($Tabla) . ' (' . implode(',', $ColumnasSql) . ') VALUES (' . implode(',', $Valores) . ')');
        if ($UpdateParts) {
            EscribirSqlExport(' ON DUPLICATE KEY UPDATE ' . implode(',', $UpdateParts));
        }
        EscribirSqlExport(";\n");
    }
    EscribirSqlExport("\n");

    if (function_exists('flush')) { flush(); }
}

EscribirSqlExport("SET FOREIGN_KEY_CHECKS=1;\n");
fclose($HandleSql);
SgceEnviarArchivoSqlFirmado($RutaTemporal, $NombreArchivo);
@unlink($RutaTemporal);
exit;
