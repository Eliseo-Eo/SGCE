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
    static $PreparadoEnRequest = false;
    $Raiz = dirname(__DIR__);
    if (defined('SGCE_LOG_DIR') && is_dir(SGCE_LOG_DIR) && is_writable(SGCE_LOG_DIR)) {
        @ini_set('error_log', rtrim(SGCE_LOG_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'php-runtime.log');
    }
    if ($PreparadoEnRequest) { return; }
    $PreparadoEnRequest = true;

    $StorageDir = $Raiz . '/storage';
    if (!is_dir($StorageDir)) { @mkdir($StorageDir, 0775, true); }
    $VersionSegura = defined('SGCE_VERSION') ? preg_replace('/[^A-Za-z0-9._-]/', '_', SGCE_VERSION) : 'actual';
    $Marcador = $StorageDir . '/.sgce_directorios_protegidos_' . $VersionSegura . '.ok';
    if (is_file($Marcador)) { return; }

    $LockDir = $StorageDir . '/locks';
    if (!is_dir($LockDir)) { @mkdir($LockDir, 0775, true); }
    $LockPath = $LockDir . '/directorios_seguros.lock';
    $Lock = @fopen($LockPath, 'c');
    if ($Lock) { @flock($Lock, LOCK_EX); }
    try {
        if (is_file($Marcador)) { return; }
        $Dirs = [
            $StorageDir,
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
        @file_put_contents($Marcador, 'SGCE directorios protegidos ' . date('c') . PHP_EOL, LOCK_EX);
        @chmod($Marcador, 0644);
    } finally {
        if ($Lock) { @flock($Lock, LOCK_UN); @fclose($Lock); }
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
    // Validación estricta: Un OOXML moderno es un ZIP real.
    // Si el servidor no tiene ZipArchive, no se acepta por seguridad.
    if (!class_exists('ZipArchive')) { return false; }

    $Extension = strtolower((string)$Extension);
    $ArchivoPrincipal = [
        'docx' => 'word/document.xml',
        'xlsx' => 'xl/workbook.xml',
        'pptx' => 'ppt/presentation.xml',
    ][$Extension] ?? '';
    if ($ArchivoPrincipal === '') { return false; }

    $Zip = new ZipArchive();
    if ($Zip->open($Ruta) !== true) { return false; }

    $Valido = $Zip->locateName('[Content_Types].xml') !== false
        && $Zip->locateName('_rels/.rels') !== false
        && $Zip->locateName($ArchivoPrincipal) !== false;

    $MaxEntradas = 200;
    $MaxEntradaDescomprimida = 80 * 1024 * 1024;
    $MaxTotalDescomprimido = 160 * 1024 * 1024;
    $TotalDescomprimido = 0;
    if ($Zip->numFiles <= 0 || $Zip->numFiles > $MaxEntradas) { $Valido = false; }

    for ($I = 0; $Valido && $I < $Zip->numFiles; $I++) {
        $Nombre = (string)$Zip->getNameIndex($I);
        $Stat = $Zip->statIndex($I);
        $Size = is_array($Stat) ? (int)($Stat['size'] ?? 0) : 0;
        $CompSize = is_array($Stat) ? (int)($Stat['comp_size'] ?? 0) : 0;
        $TotalDescomprimido += max(0, $Size);
        if ($Nombre === '' || strpos($Nombre, "\0") !== false || str_starts_with($Nombre, '/') || str_contains($Nombre, '../')) {
            $Valido = false;
            break;
        }
        if ($Size > $MaxEntradaDescomprimida || $TotalDescomprimido > $MaxTotalDescomprimido) {
            $Valido = false;
            break;
        }
        if ($CompSize > 0 && $Size > 0 && ($Size / max(1, $CompSize)) > 120) {
            $Valido = false;
            break;
        }
    }

    $Zip->close();
    return $Valido;
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
    $Tmp = $RutaArchivo . '.tmp.' . bin2hex(random_bytes(4));
    $Handle = fopen($Tmp, 'wb');
    if (!$Handle) { return false; }

    fwrite($Handle, "-- SGCE respaldo automatico\n-- Fecha: " . date('Y-m-d H:i:s') . "\nSET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n");
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

    $Ok = SgceFirmarArchivoRespaldo($Tmp, $RutaArchivo);
    @unlink($Tmp);
    if (!$Ok) { return false; }
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


function SgceBackupSigningKeyEsPlaceholder(string $Key): bool {
    $Key = strtolower(trim($Key));
    return in_array($Key, [
        'cambia_este_valor_por_64_bytes_hexadecimales_generados_con_random_bytes',
        'change_me',
        'changeme',
    ], true);
}

function SgceBackupSigningKeyHexValida(string $Key): bool {
    return preg_match('/^[a-f0-9]{128}$/i', trim($Key)) === 1 && hex2bin(trim($Key)) !== false;
}

function SgceLeerBackupSigningKeyPersistida(): string {
    $Rutas = [
        dirname(__DIR__) . '/config/backup_signing.local.php',
        dirname(__DIR__) . '/storage/keys/backup_signing.key',
    ];

    foreach ($Rutas as $Ruta) {
        if (!is_file($Ruta) || !is_readable($Ruta)) { continue; }
        $Valor = '';
        if (str_ends_with($Ruta, '.php')) {
            $Leido = require $Ruta;
            $Valor = is_string($Leido) ? trim($Leido) : '';
        } else {
            $Valor = trim((string)file_get_contents($Ruta));
        }
        if (SgceBackupSigningKeyHexValida($Valor)) { return $Valor; }
    }

    return '';
}

function SgcePersistirBackupSigningKeyGenerada(string $Key): void {
    $Rutas = [
        dirname(__DIR__) . '/config/backup_signing.local.php' => "<?php\nreturn '" . $Key . "';\n",
        dirname(__DIR__) . '/storage/keys/backup_signing.key' => $Key . "\n",
    ];

    foreach ($Rutas as $Ruta => $Contenido) {
        $Dir = dirname($Ruta);
        if (!is_dir($Dir) && !@mkdir($Dir, 0775, true) && !is_dir($Dir)) { continue; }
        if (!is_writable($Dir)) { continue; }
        $Tmp = $Ruta . '.tmp.' . bin2hex(random_bytes(6));
        if (@file_put_contents($Tmp, $Contenido, LOCK_EX) === false) { @unlink($Tmp); continue; }
        @chmod($Tmp, 0600);
        if (@rename($Tmp, $Ruta)) {
            @chmod($Ruta, 0600);
            error_log('SGCE: backup_signing_key ausente en una instalación existente; se generó una clave persistente para respaldos HMAC. Conserva este archivo junto con tus respaldos.');
            return;
        }
        @unlink($Tmp);
    }

    throw new RuntimeException('No fue posible persistir la clave privada de respaldos. Revisa permisos en config/ o storage/keys/.');
}

function SgceObtenerOGenerarBackupSigningKeyHex(): string {
    $Persistida = SgceLeerBackupSigningKeyPersistida();
    if ($Persistida !== '') { return $Persistida; }

    $LockPath = dirname(__DIR__) . '/storage/keys/.backup_signing.lock';
    $LockDir = dirname($LockPath);
    if (!is_dir($LockDir) && !@mkdir($LockDir, 0775, true) && !is_dir($LockDir)) {
        throw new RuntimeException('No fue posible preparar el bloqueo de clave privada de respaldos.');
    }

    $Lock = @fopen($LockPath, 'c');
    if (!$Lock) {
        throw new RuntimeException('No fue posible abrir el bloqueo de clave privada de respaldos.');
    }

    if (!flock($Lock, LOCK_EX)) {
        fclose($Lock);
        throw new RuntimeException('No fue posible bloquear la generación de clave privada de respaldos.');
    }

    try {
        $Persistida = SgceLeerBackupSigningKeyPersistida();
        if ($Persistida !== '') { return $Persistida; }

        $Nueva = bin2hex(random_bytes(64));
        SgcePersistirBackupSigningKeyGenerada($Nueva);
        return $Nueva;
    } finally {
        flock($Lock, LOCK_UN);
        fclose($Lock);
    }
}

function SgceBackupSigningKey(): string {
    $Key = defined('SGCE_BACKUP_SIGNING_KEY') ? trim((string)SGCE_BACKUP_SIGNING_KEY) : '';
    if ($Key === '') { $Key = trim((string)(getenv('SGCE_BACKUP_SIGNING_KEY') ?: '')); }

    if ($Key === '') {
        $Key = SgceObtenerOGenerarBackupSigningKeyHex();
    }

    if (SgceBackupSigningKeyEsPlaceholder($Key)) {
        throw new RuntimeException('La clave backup_signing_key conserva un valor placeholder. Genérala con: bin2hex(random_bytes(64)).');
    }

    if (!SgceBackupSigningKeyHexValida($Key)) {
        throw new RuntimeException('La clave backup_signing_key debe ser hexadecimal de 64 bytes. Genérala con: bin2hex(random_bytes(64)).');
    }

    $Binaria = hex2bin(trim($Key));
    if ($Binaria === false || strlen($Binaria) !== 64) {
        throw new RuntimeException('La clave backup_signing_key no se pudo interpretar como hexadecimal válido.');
    }
    return $Binaria;
}

function SgceFirmarArchivoRespaldo(string $RutaOrigen, string $RutaDestino): bool {
    if (!is_file($RutaOrigen) || !is_readable($RutaOrigen)) { return false; }
    $Firma = hash_hmac_file('sha256', $RutaOrigen, SgceBackupSigningKey());
    $In = fopen($RutaOrigen, 'rb');
    $Out = fopen($RutaDestino, 'wb');
    if (!$In || !$Out) {
        if ($In) { fclose($In); }
        if ($Out) { fclose($Out); }
        return false;
    }
    fwrite($Out, '-- SGCE_HMAC=' . $Firma . "\n");
    while (!feof($In)) {
        $Chunk = fread($In, 1024 * 1024);
        if ($Chunk === false) { fclose($In); fclose($Out); return false; }
        if ($Chunk !== '') { fwrite($Out, $Chunk); }
    }
    fclose($In);
    fclose($Out);
    return true;
}

function SgceEnviarArchivoSqlFirmado(string $RutaSqlSinFirma, string $NombreArchivo): void {
    if (!is_file($RutaSqlSinFirma) || !is_readable($RutaSqlSinFirma)) {
        throw new RuntimeException('No se pudo leer el respaldo temporal.');
    }
    $Firma = hash_hmac_file('sha256', $RutaSqlSinFirma, SgceBackupSigningKey());
    SgceHeaderDescarga($NombreArchivo, 'application/sql; charset=utf-8');
    SgceEnviarHeadersNoCacheDescarga();
    echo '-- SGCE_HMAC=' . $Firma . "\n";
    $In = fopen($RutaSqlSinFirma, 'rb');
    if (!$In) { return; }
    while (!feof($In)) {
        $Chunk = fread($In, 1024 * 1024);
        if ($Chunk === false) { break; }
        if ($Chunk !== '') { echo $Chunk; }
        if (function_exists('flush')) { flush(); }
    }
    fclose($In);
}

function SgceFirmaArchivoRespaldoValida(string $RutaArchivo): bool {
    if (!is_file($RutaArchivo) || !is_readable($RutaArchivo)) { return false; }
    $Handle = fopen($RutaArchivo, 'rb');
    if (!$Handle) { return false; }
    $PrimeraLinea = fgets($Handle);
    if (!is_string($PrimeraLinea) || !preg_match('/^-- SGCE_HMAC=([a-f0-9]{64})\R?$/i', $PrimeraLinea, $M)) {
        fclose($Handle);
        return false;
    }
    try {
        $Ctx = hash_init('sha256', HASH_HMAC, SgceBackupSigningKey());
        while (!feof($Handle)) {
            $Chunk = fread($Handle, 1024 * 1024);
            if ($Chunk === false) { fclose($Handle); return false; }
            if ($Chunk !== '') { hash_update($Ctx, $Chunk); }
        }
        fclose($Handle);
        return hash_equals(strtolower($M[1]), hash_final($Ctx));
    } catch (Throwable $E) {
        fclose($Handle);
        return false;
    }
}

