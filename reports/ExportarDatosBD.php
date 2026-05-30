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
    'Grupos',
    'Alumnos',
    'Asignaciones',
    'CiclosEscolares',
    'PeriodosEvaluacion',
    'Calificaciones',
    'Asistencias',
    'Avisos',
    'Planeaciones',
    'BitacoraMovimientos',
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
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $NombreArchivo . '"');
SgceEnviarHeadersNoCacheDescarga();
header('X-Content-Type-Options: nosniff');

echo "-- ============================================================\n";
echo "-- RESPALDO SGCE SOLO DATOS\n";
echo "-- GENERADO: " . date('Y-m-d H:i:s') . "\n";
echo "-- RestaurarBD.php permite fusionar o reemplazar datos con este archivo.\n";
echo "-- NOTA: NO SE RESPALDAN TOKENS DE SESIÓN ACTIVOS.\n";
echo "-- ============================================================\n\n";
echo "-- SGCE_EXPORT_SIGNATURE=SGCE_PRODUCCION\n";
echo "-- NOTA: LAS CONTRASEÑAS SE RESPALDAN COMO HASH SEGURO, NO EN TEXTO PLANO.\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n";
echo "SET NAMES utf8mb4;\n\n";

foreach ($Tablas as $Tabla) {
    $Columnas = ColumnasInsertablesDatos($Pdo, $Tabla);
    if (!$Columnas) { continue; }

    $Pk = LlavePrimariaDatos($Pdo, $Tabla);
    $ColumnasSql = array_map(function($Col){ return '`' . str_replace('`', '``', $Col) . '`'; }, $Columnas);
    $ColumnasSelect = implode(',', $ColumnasSql);

    echo "-- ------------------------------------------------------------\n";
    echo "-- DATOS: `" . str_replace('`','``',$Tabla) . "`\n";
    echo "-- ------------------------------------------------------------\n";

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

        echo 'INSERT INTO ' . QTablaDatos($Tabla) . ' (' . implode(',', $ColumnasSql) . ') VALUES (' . implode(',', $Valores) . ')';
        if ($UpdateParts) {
            echo ' ON DUPLICATE KEY UPDATE ' . implode(',', $UpdateParts);
        }
        echo ";\n";
    }
    echo "\n";

    if (function_exists('flush')) { flush(); }
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
exit;

