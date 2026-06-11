<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceContenidoHtaccessDenegacion() {
    return "Options -Indexes\n" .
        "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
        "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n" .
        "<FilesMatch \"\\.(php|phtml|phar|cgi|pl|py|sh|sql|log|bak|backup|old|orig|tmp|zip|tar|gz|7z|dm)$\">\n" .
        "    <IfModule mod_authz_core.c>\n        Require all denied\n    </IfModule>\n" .
        "    <IfModule !mod_authz_core.c>\n        Order allow,deny\n        Deny from all\n    </IfModule>\n" .
        "</FilesMatch>\n";
}


function SgceAsegurarCarpetaProtegida($Dir) {
    $Dir = (string)$Dir;
    if ($Dir === '') { return false; }
    if (!is_dir($Dir)) { @mkdir($Dir, 0755, true); }
    if (!is_dir($Dir)) { return false; }
    $Htaccess = rtrim($Dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.htaccess';
    $Contenido = SgceContenidoHtaccessDenegacion();
    if (!is_file($Htaccess) || trim((string)@file_get_contents($Htaccess)) !== trim($Contenido)) {
        @file_put_contents($Htaccess, $Contenido, LOCK_EX);
    }
    $Index = rtrim($Dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_file($Index)) { @file_put_contents($Index, "<!doctype html><meta charset=\"utf-8\"><title>SGCE</title>\n", LOCK_EX); }
    @chmod($Htaccess, 0644);
    @chmod($Index, 0644);
    return true;
}


function SgcePrepararDirectoriosSeguros() {
    $Raiz = dirname(__DIR__);
    $Dirs = [
        $Raiz . '/storage',
        defined('SGCE_BACKUP_DIR') ? SGCE_BACKUP_DIR : $Raiz . '/storage/backups',
        defined('SGCE_LOG_DIR') ? SGCE_LOG_DIR : $Raiz . '/storage/logs',
        defined('SGCE_PLANEACIONES_DIR') ? SGCE_PLANEACIONES_DIR : $Raiz . '/storage/planeaciones',
        $Raiz . '/config',
        $Raiz . '/includes',
        $Raiz . '/modules',
        $Raiz . '/views',
        $Raiz . '/reports',
        $Raiz . '/repositories',
        $Raiz . '/services',
        $Raiz . '/public',
        $Raiz . '/cron',
    ];
    $ToolsDir = $Raiz . '/tools';
    if (is_dir($ToolsDir)) { $Dirs[] = $ToolsDir; }
    foreach (array_unique($Dirs) as $Dir) { SgceAsegurarCarpetaProtegida($Dir); }
    if (defined('SGCE_LOG_DIR') && is_dir(SGCE_LOG_DIR) && is_writable(SGCE_LOG_DIR)) {
        @ini_set('error_log', rtrim(SGCE_LOG_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'php-runtime.log');
    }
}


function SgceCarpetaPlaneaciones() {
    $Dir = defined('SGCE_PLANEACIONES_DIR') ? SGCE_PLANEACIONES_DIR : dirname(__DIR__) . '/storage/planeaciones';
    if (!is_dir($Dir)) { @mkdir($Dir, 0775, true); }
    return $Dir;
}


function SgcePrepararCarpetaDocentePlaneaciones($MaestroId, $Username) {
    $Base = SgceCarpetaPlaneaciones();
    $Dir = $Base . '/M' . (int)$MaestroId . '_' . SgceNombreArchivoSeguro($Username);
    if (!is_dir($Dir)) { @mkdir($Dir, 0775, true); }
    return $Dir;
}


function SgceExtensionesPlaneacionPermitidas() {
    return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
}


function SgceMimePlaneacionPermitido($Extension, $Mime) {
    $Extension = strtolower(trim((string)$Extension));
    $Mime = strtolower(trim((string)$Mime));
    if ($Extension === '' || $Mime === '') { return false; }

    $Permitidos = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'xls'  => ['application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/mspowerpoint', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream'],
    ];

    return isset($Permitidos[$Extension]) && in_array($Mime, $Permitidos[$Extension], true);
}


function SgceArchivoPdfValido($Ruta) {
    $Firma = @file_get_contents($Ruta, false, null, 0, 5);
    return $Firma === '%PDF-';
}


function SgceArchivoOfficeBinarioValido($Ruta) {
    $Firma = @file_get_contents($Ruta, false, null, 0, 8);
    return $Firma === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
}


function SgceArchivoOoxmlValido($Ruta, $Extension) {
    if (!class_exists('ZipArchive')) { return true; }
    $Zip = new ZipArchive();
    if ($Zip->open($Ruta) !== true) { return false; }
    $TieneContentTypes = $Zip->locateName('[Content_Types].xml') !== false;
    $DirectorioEsperado = [
        'docx' => 'word/',
        'xlsx' => 'xl/',
        'pptx' => 'ppt/',
    ][strtolower((string)$Extension)] ?? '';
    $TieneDirectorio = false;
    if ($DirectorioEsperado !== '') {
        for ($I = 0; $I < $Zip->numFiles; $I++) {
            $Nombre = (string)$Zip->getNameIndex($I);
            if (str_starts_with($Nombre, $DirectorioEsperado)) { $TieneDirectorio = true; break; }
        }
    }
    $Zip->close();
    return $TieneContentTypes && ($DirectorioEsperado === '' || $TieneDirectorio);
}


function SgceArchivoPlaneacionFirmaValida($Ruta, $Extension) {
    $Extension = strtolower((string)$Extension);
    if ($Extension === 'pdf') { return SgceArchivoPdfValido($Ruta); }
    if (in_array($Extension, ['doc', 'xls', 'ppt'], true)) { return SgceArchivoOfficeBinarioValido($Ruta); }
    if (in_array($Extension, ['docx', 'xlsx', 'pptx'], true)) { return SgceArchivoOoxmlValido($Ruta, $Extension); }
    return false;
}


function SgceValidarArchivoPlaneacion($Archivo) {
    if (!isset($Archivo['error']) || $Archivo['error'] !== UPLOAD_ERR_OK) { return 'Selecciona un archivo válido.'; }
    if (!is_uploaded_file($Archivo['tmp_name'] ?? '')) { return 'La carga del archivo no es válida.'; }

    $Max = 25 * 1024 * 1024;
    if ((int)($Archivo['size'] ?? 0) <= 0 || (int)$Archivo['size'] > $Max) { return 'El archivo no debe superar 25 MB.'; }

    $Nombre = (string)($Archivo['name'] ?? '');
    $Ext = strtolower(pathinfo($Nombre, PATHINFO_EXTENSION));
    if (!in_array($Ext, SgceExtensionesPlaneacionPermitidas(), true)) { return 'Formato no permitido. Usa PDF, Word, Excel o PowerPoint.'; }

    if (function_exists('finfo_open')) {
        $Finfo = finfo_open(FILEINFO_MIME_TYPE);
        $Mime = $Finfo ? (string)finfo_file($Finfo, $Archivo['tmp_name']) : '';
        if (!SgceMimePlaneacionPermitido($Ext, $Mime)) {
            return 'El tipo real del archivo no coincide con su extensión. Sube un PDF, Word, Excel o PowerPoint válido.';
        }
    }

    if (!SgceArchivoPlaneacionFirmaValida($Archivo['tmp_name'], $Ext)) {
        return 'La firma interna del archivo no coincide con un documento válido. Sube un PDF, Word, Excel o PowerPoint real.';
    }

    return true;
}


function SgceNombrePlaneacionEstandar($CicloNombre, $MaestroNombre, $MateriaNombre, $Numero, $Extension = '', $VersionArchivo = 1) {
    $Ciclo = substr(SgceNombreArchivoSeguro((string)$CicloNombre), 0, 16);
    $Materia = substr(SgceNombreArchivoSeguro((string)$MateriaNombre), 0, 30);
    $Maestro = substr(SgceNombreArchivoSeguro((string)$MaestroNombre), 0, 30);
    $NumeroTxt = 'P' . str_pad((string)max(1, (int)$Numero), 2, '0', STR_PAD_LEFT);
    $Version = max(1, (int)$VersionArchivo);
    $VersionTxt = $Version > 1 ? '_V' . str_pad((string)$Version, 2, '0', STR_PAD_LEFT) : '';
    $Base = trim($Ciclo . '_' . $NumeroTxt . $VersionTxt . '_' . $Materia . '_' . $Maestro, '_');
    $Base = preg_replace('/_+/', '_', $Base);
    $Extension = strtolower(trim((string)$Extension, '. '));
    if ($Extension !== '' && preg_match('/^[a-z0-9]{2,8}$/', $Extension)) {
        return $Base . '.' . $Extension;
    }
    return $Base;
}


function SgceNombrePlaneacionInterno($CicloNombre, $MaestroNombre, $MateriaNombre, $Numero, $Extension = '', $VersionArchivo = 1) {
    $Base = pathinfo(SgceNombrePlaneacionEstandar($CicloNombre, $MaestroNombre, $MateriaNombre, $Numero, $Extension, $VersionArchivo), PATHINFO_FILENAME);
    $Extension = strtolower(trim((string)$Extension, '. '));
    $Sufijo = date('Ymd_His') . '_' . bin2hex(random_bytes(3));
    return $Base . '_' . $Sufijo . ($Extension !== '' ? '.' . $Extension : '');
}


function SgceColumnasInsertablesBackup($Pdo, $Tabla) {
    $Stmt = $Pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $Tabla) . '`');
    $Columnas = [];
    while ($Col = $Stmt->fetch(PDO::FETCH_ASSOC)) {
        $Extra = strtolower((string)($Col['Extra'] ?? ''));
        if (strpos($Extra, 'generated') !== false) { continue; }
        $Columnas[] = $Col['Field'];
    }
    return $Columnas;
}


function SgceCrearRespaldoSql($Pdo, $RutaArchivo, $SoloDatos = false) {
    SgcePrepararDirectoriosSeguros();
    $Handle = fopen($RutaArchivo, 'wb');
    if (!$Handle) { return false; }
    fwrite($Handle, "-- SGCE respaldo automático\n-- SGCE_EXPORT_SIGNATURE=SGCE_PRODUCCION\n-- Fecha: " . date('Y-m-d H:i:s') . "\nSET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n");
    $Tablas = $Pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($Tablas as $Tabla) {
        $TablaSql = '`' . str_replace('`', '``', $Tabla) . '`';
        if (!$SoloDatos) {
            $Create = $Pdo->query('SHOW CREATE TABLE ' . $TablaSql)->fetch(PDO::FETCH_ASSOC);
            $CreateSql = $Create['Create Table'] ?? array_values($Create)[1];
            fwrite($Handle, "DROP TABLE IF EXISTS {$TablaSql};\n" . $CreateSql . ";\n\n");
        }
        $Columnas = SgceColumnasInsertablesBackup($Pdo, $Tabla);
        if (!$Columnas) { continue; }
        $ColumnasSql = array_map(fn($C) => '`' . str_replace('`', '``', $C) . '`', $Columnas);
        $Stmt = $Pdo->query('SELECT ' . implode(',', $ColumnasSql) . ' FROM ' . $TablaSql);
        while ($Row = $Stmt->fetch(PDO::FETCH_ASSOC)) {
            $Vals = [];
            foreach ($Columnas as $Col) {
                if ($Tabla === 'Usuarios' && in_array($Col, ['SessionToken','SessionTokenExpira'], true)) { $Vals[] = 'NULL'; continue; }
                $Valor = $Row[$Col] ?? null;
                $Vals[] = $Valor === null ? 'NULL' : $Pdo->quote((string)$Valor);
            }
            fwrite($Handle, 'INSERT INTO ' . $TablaSql . ' (' . implode(',', $ColumnasSql) . ') VALUES (' . implode(',', $Vals) . ");\n");
        }
        fwrite($Handle, "\n");
    }
    fwrite($Handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($Handle);
    @chmod($RutaArchivo, 0640);
    return true;
}


function SgceGenerarBackupAutomatico($Pdo, $Frecuencia = 'diario', $Retener = 14) {
    $Frecuencia = in_array($Frecuencia, ['diario', 'semanal'], true) ? $Frecuencia : 'diario';
    $Retener = max(3, min(60, (int)$Retener));
    $Dir = defined('SGCE_BACKUP_DIR') ? SGCE_BACKUP_DIR : dirname(__DIR__) . '/storage/backups';
    if (!is_dir($Dir) && !@mkdir($Dir, 0775, true) && !is_dir($Dir)) {
        throw new RuntimeException('No se pudo crear la carpeta de respaldos automáticos.');
    }
    if (!is_writable($Dir)) {
        throw new RuntimeException('La carpeta de respaldos automáticos no tiene permisos de escritura.');
    }
    $Sufijo = $Frecuencia === 'semanal' ? date('o_W') : date('Ymd');
    $Archivo = $Dir . '/AutoBackup_' . ucfirst($Frecuencia) . '_' . $Sufijo . '.sql';
    if (is_file($Archivo) && filesize($Archivo) > 0) { return $Archivo; }
    if (!SgceCrearRespaldoSql($Pdo, $Archivo, false)) {
        throw new RuntimeException('No se pudo generar el archivo de respaldo automático.');
    }
    $Archivos = glob($Dir . '/AutoBackup_' . ucfirst($Frecuencia) . '_*.sql') ?: [];
    rsort($Archivos);
    foreach (array_slice($Archivos, $Retener) as $Viejo) { @unlink($Viejo); }
    return $Archivo;
}


function SgceFirmaRespaldoValida($Sql) {
    return is_string($Sql) && preg_match('/SGCE_EXPORT_SIGNATURE=SGCE_PRODUCCION/', $Sql) === 1;
}




function SgceValidarSqlRestauracionSegura($Sql, $MaxSentencias = 250000) {
    $Sql = (string)$Sql;
    if (trim($Sql) === '') { return 'El respaldo SQL está vacío.'; }
    if (!SgceFirmaRespaldoValida($Sql)) { return 'El archivo no tiene la firma oficial SGCE.'; }
    if (preg_match('/\b(DROP\s+DATABASE|CREATE\s+DATABASE|ALTER\s+DATABASE|GRANT\s+|REVOKE\s+|CREATE\s+USER|DROP\s+USER)\b/i', $Sql)) {
        return 'El respaldo contiene instrucciones administrativas no permitidas.';
    }
    $AproxSentencias = substr_count($Sql, ';');
    if ($AproxSentencias > $MaxSentencias) { return 'El respaldo supera el límite seguro de sentencias.'; }
    return true;
}

