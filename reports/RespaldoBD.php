<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'respaldos', 'Solo el administrador puede generar respaldos de la base de datos.');

RegistrarBitacora($Pdo, $UserSession, 'GENERAR_RESPALDO', 'BASE_DE_DATOS', null, 'RESPALDO SQL DESCARGADO DESDE ADMIN');

function QTablaRespaldo($Tabla) {
    return '`' . str_replace('`', '``', $Tabla) . '`';
}

function ColumnasInsertablesRespaldo($Pdo, $Tabla) {
    $StmtCols = $Pdo->query('SHOW COLUMNS FROM ' . QTablaRespaldo($Tabla));
    $Columnas = [];
    while ($Col = $StmtCols->fetch(PDO::FETCH_ASSOC)) {
        $Extra = strtolower((string)($Col['Extra'] ?? ''));
        if (strpos($Extra, 'generated') !== false) {
            continue;
        }
        $Columnas[] = $Col['Field'];
    }
    return $Columnas;
}


$NombreArchivo = 'Respaldo_SGCE_' . date('Ymd_His') . '.sql';
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $NombreArchivo . '"');
SgceEnviarHeadersNoCacheDescarga();

echo "-- ============================================================\n";
echo "-- RESPALDO SGCE\n";
echo "-- GENERADO: " . date('Y-m-d H:i:s') . "\n";
echo "-- NOTA: NO SE RESPALDAN TOKENS DE SESIÓN ACTIVOS.\n";
echo "-- ============================================================\n\n";
echo "-- SGCE_EXPORT_SIGNATURE=SGCE_PRODUCCION\n";
echo "-- NOTA: LAS CONTRASEÑAS SE RESPALDAN COMO HASH SEGURO, NO EN TEXTO PLANO.\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n";
echo "SET NAMES utf8mb4;\n\n";

$Tablas = $Pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);

foreach ($Tablas as $TablaRow) {
    $Tabla = $TablaRow[0];
    echo "-- ------------------------------------------------------------\n";
    echo "-- TABLA: `$Tabla`\n";
    echo "-- ------------------------------------------------------------\n\n";
    echo "DROP TABLE IF EXISTS `$Tabla`;\n";

    $CreateStmt = $Pdo->query('SHOW CREATE TABLE `' . str_replace('`','``',$Tabla) . '`')->fetch(PDO::FETCH_ASSOC);
    $CreateSql = $CreateStmt['Create Table'] ?? array_values($CreateStmt)[1];
    echo $CreateSql . ";\n\n";

    $ColumnasInsertables = ColumnasInsertablesRespaldo($Pdo, $Tabla);
    if (!$ColumnasInsertables) {
        echo "\n";
        continue;
    }
    $ColumnasSql = array_map(function($Col){ return '`' . str_replace('`','``',$Col) . '`'; }, $ColumnasInsertables);
    $StmtDatos = $Pdo->query('SELECT ' . implode(',', $ColumnasSql) . ' FROM ' . QTablaRespaldo($Tabla));
    while ($Fila = $StmtDatos->fetch(PDO::FETCH_ASSOC)) {
        $Valores = [];
        foreach ($ColumnasInsertables as $NombreColumna) {
            $Valor = $Fila[$NombreColumna] ?? null;
            if ($Tabla === 'Usuarios' && in_array($NombreColumna, ['SessionToken','SessionTokenExpira'], true)) {
                $Valores[] = 'NULL';
                continue;
            }
            if ($Valor === null) {
                $Valores[] = 'NULL';
            } else {
                $Valores[] = $Pdo->quote((string)$Valor);
            }
        }
        echo 'INSERT INTO ' . QTablaRespaldo($Tabla) . ' (' . implode(',', $ColumnasSql) . ') VALUES (' . implode(',', $Valores) . ");\n";
    }
    echo "\n";
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
exit;

