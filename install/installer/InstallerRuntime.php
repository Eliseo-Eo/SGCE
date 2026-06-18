<?php
if (!defined('SGCE_INSTALLER')) { http_response_code(403); exit('Acceso directo no permitido.'); }


function InstalarScriptTag(string $Ruta): string {
    return '<script src="' . htmlspecialchars(InstalarAssetUrl($Ruta), ENT_QUOTES, 'UTF-8') . '"></script>';
}

function InstalarFooterAssets(): string {
    return implode("\n", [
        '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>',
        InstalarScriptTag('assets/js/shared/theme.js'),
        InstalarScriptTag('assets/js/shared/notifications.js'),
        InstalarScriptTag('assets/js/shared/bootstrap-modals.js'),
        InstalarScriptTag('assets/js/shared/confirm-modal.js'),
        InstalarScriptTag('assets/js/shared/maestro-empty-state.js'),
        InstalarScriptTag('assets/js/shared/csrf.js'),
        InstalarScriptTag('assets/js/Instalar.js'),
    ]);
}

function InstalarRutaRaizAplicacion() {
    return dirname(__DIR__, 2);
}

function InstalarRutaInstalador() {
    return dirname(__DIR__);
}

function InstalarNormalizarUrlBaseSistema($Url): string {
    $Url = trim((string)$Url);
    if ($Url === '') { return ''; }
    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $Url) && !preg_match('#^https?://#i', $Url)) { return ''; }
    if (preg_match('#^https?://#i', $Url)) {
        $Partes = parse_url($Url);
        if (empty($Partes['host'])) { return ''; }
        $Scheme = strtolower((string)($Partes['scheme'] ?? 'https'));
        $Host = strtolower((string)$Partes['host']);
        $Puerto = isset($Partes['port']) ? ':' . (int)$Partes['port'] : '';
        $Path = '/' . trim((string)($Partes['path'] ?? ''), '/');
        $Path = $Path === '/' ? '/' : $Path . '/';
        return $Scheme . '://' . $Host . $Puerto . $Path;
    }
    $Url = '/' . trim($Url, '/');
    return $Url === '/' ? '/' : $Url . '/';
}

function InstalarDetectarUrlBaseSistema(): string {
    $Https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
    $Scheme = $Https ? 'https' : 'http';
    $Host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $Dir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
    $Dir = '/' . trim($Dir, '/');
    $Path = $Dir === '/' ? '/' : $Dir . '/';
    return InstalarNormalizarUrlBaseSistema($Scheme . '://' . ($Host ?: 'localhost') . $Path);
}

function InstalarFormatoPermisos($Path) {
    if (!file_exists($Path)) { return 'NO EXISTE'; }
    $Permisos = substr(sprintf('%o', fileperms($Path)), -4);
    $Propietario = function_exists('posix_getpwuid') ? (posix_getpwuid(fileowner($Path))['name'] ?? fileowner($Path)) : fileowner($Path);
    $Grupo = function_exists('posix_getgrgid') ? (posix_getgrgid(filegroup($Path))['name'] ?? filegroup($Path)) : filegroup($Path);
    return $Permisos . ' ' . $Propietario . ':' . $Grupo;
}

function InstalarUsuarioPhp() {
    if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
        $Info = posix_getpwuid(posix_geteuid());
        if (is_array($Info) && !empty($Info['name'])) { return $Info['name']; }
    }
    return get_current_user();
}

function InstalarVerificarEscritura($RutaArchivo) {
    $Dir = dirname($RutaArchivo);
    if (!is_dir($Dir)) {
        if (!mkdir($Dir, 0775, true) && !is_dir($Dir)) {
            throw new Exception('No se pudo crear la carpeta de configuración: ' . $Dir);
        }
    }
    $RealDir = realpath($Dir) ?: $Dir;
    if (!is_writable($Dir)) {
        throw new Exception('La carpeta config no tiene permisos de escritura para PHP. Ruta: ' . $RealDir . ' | Permisos: ' . InstalarFormatoPermisos($Dir) . ' | Usuario PHP: ' . InstalarUsuarioPhp());
    }
    $Prueba = $Dir . '/.sgce_write_test_' . bin2hex(random_bytes(4));
    $Ok = file_put_contents($Prueba, 'ok', LOCK_EX);
    if ($Ok === false) {
        $Error = error_get_last();
        throw new Exception('PHP no pudo escribir archivo de prueba en config. Ruta: ' . $RealDir . ' | Usuario PHP: ' . InstalarUsuarioPhp() . ' | Detalle: ' . (($Error['message'] ?? 'sin detalle')));
    }
    @unlink($Prueba);
    return true;
}

function InstalarEscribirArchivoSeguro($RutaArchivo, $Contenido) {
    InstalarVerificarEscritura($RutaArchivo);
    $Dir = dirname($RutaArchivo);
    $Tmp = $Dir . '/.' . basename($RutaArchivo) . '.tmp.' . bin2hex(random_bytes(4));
    $Bytes = file_put_contents($Tmp, $Contenido, LOCK_EX);
    if ($Bytes === false) {
        $Error = error_get_last();
        throw new Exception('No se pudo escribir archivo temporal de configuración. Ruta: ' . $Tmp . ' | Usuario PHP: ' . InstalarUsuarioPhp() . ' | Detalle: ' . (($Error['message'] ?? 'sin detalle')));
    }
    @chmod($Tmp, 0640);
    if (!rename($Tmp, $RutaArchivo)) {
        $Error = error_get_last();
        @unlink($Tmp);
        throw new Exception('No se pudo guardar config/database.local.php. Ruta destino: ' . $RutaArchivo . ' | Permisos config: ' . InstalarFormatoPermisos($Dir) . ' | Usuario PHP: ' . InstalarUsuarioPhp() . ' | Detalle: ' . (($Error['message'] ?? 'sin detalle')));
    }
    @chmod($RutaArchivo, 0640);
    return true;
}

function InstalarEliminarDirectorio($Dir, &$Detalles) {
    if (!is_dir($Dir)) { return true; }
    $Ok = true;
    foreach (scandir($Dir) ?: [] as $Item) {
        if ($Item === '.' || $Item === '..') { continue; }
        $Path = $Dir . DIRECTORY_SEPARATOR . $Item;
        if (is_dir($Path)) { $Ok = InstalarEliminarDirectorio($Path, $Detalles) && $Ok; }
        elseif (!@unlink($Path)) { $Detalles[] = 'No se pudo eliminar: ' . $Path; $Ok = false; }
    }
    if (!@rmdir($Dir)) { $Detalles[] = 'No se pudo eliminar carpeta: ' . $Dir; $Ok = false; }
    return $Ok;
}

function InstalarGuardarConfiguracion($PdoDb, $Datos) {
    $Stmt = $PdoDb->prepare('INSERT INTO ConfiguracionSistema (Clave, Valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE Valor = VALUES(Valor), FechaActualizacion = CURRENT_TIMESTAMP');
    foreach ($Datos as $Clave => $Valor) {
        $Stmt->execute([$Clave, $Valor]);
    }
}


function InstalarContenidoHtaccessDenegacion() {
    return "Options -Indexes\n" .
        "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
        "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n" .
        "<FilesMatch \"\\.(php|phtml|phar|cgi|pl|py|sh|sql|log|bak|backup|old|orig|tmp|zip|tar|gz|7z|dm)$\">\n" .
        "    <IfModule mod_authz_core.c>\n        Require all denied\n    </IfModule>\n" .
        "    <IfModule !mod_authz_core.c>\n        Order allow,deny\n        Deny from all\n    </IfModule>\n" .
        "</FilesMatch>\n";
}

function InstalarAsegurarCarpetaProtegida($Dir) {
    $Dir = (string)$Dir;
    if ($Dir === '') { return false; }
    if (!is_dir($Dir)) { @mkdir($Dir, 0755, true); }
    if (!is_dir($Dir)) { return false; }
    @file_put_contents(rtrim($Dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.htaccess', InstalarContenidoHtaccessDenegacion(), LOCK_EX);
    $Index = rtrim($Dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_file($Index)) { @file_put_contents($Index, "<!doctype html><meta charset=\"utf-8\"><title>SGCE</title>\n", LOCK_EX); }
    return true;
}

function InstalarAsegurarProteccionesIniciales($BackupDir) {
    $RootDir = InstalarRutaRaizAplicacion();
    foreach ([$RootDir . '/storage', $RootDir . '/storage/logs', $RootDir . '/storage/planeaciones', $RootDir . '/storage/tmp_uploads', $BackupDir, $RootDir . '/config', $RootDir . '/includes', $RootDir . '/modules', $RootDir . '/reports', $RootDir . '/public', $RootDir . '/cron'] as $Dir) {
        InstalarAsegurarCarpetaProtegida($Dir);
    }
}

function InstalarLogDir() {
    return InstalarRutaRaizAplicacion() . '/storage/logs';
}

function InstalarRegistrarError($Error, $Contexto = 'INSTALADOR') {
    $Dir = InstalarLogDir();
    if (!is_dir($Dir)) { @mkdir($Dir, 0775, true); }
    $Id = date('YmdHis') . '-' . bin2hex(random_bytes(4));
    $Linea = [
        'id' => $Id,
        'fecha' => date('c'),
        'contexto' => $Contexto,
        'mensaje' => $Error instanceof Throwable ? $Error->getMessage() : (string)$Error,
        'archivo' => $Error instanceof Throwable ? $Error->getFile() : null,
        'linea' => $Error instanceof Throwable ? $Error->getLine() : null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
    ];
    @file_put_contents($Dir . '/instalador-' . date('Y-m-d') . '.log', json_encode($Linea, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    return $Id;
}

function InstalarAddCheck(&$Checks, $Clave, $Titulo, $Estado, $Detalle) {
    $Checks[] = ['clave' => $Clave, 'titulo' => $Titulo, 'estado' => $Estado, 'detalle' => $Detalle];
}

function InstalarSessionSavePathReal() {
    $Ruta = trim((string)session_save_path());
    if ($Ruta === '') { return sys_get_temp_dir(); }
    if (strpos($Ruta, ';') !== false) {
        $Partes = explode(';', $Ruta);
        $Ruta = trim((string)end($Partes));
    }
    return $Ruta !== '' ? $Ruta : sys_get_temp_dir();
}

function InstalarVerificacionesServidor($Valores = [], $ProbarMysql = false) {
    $Checks = [];
    InstalarAddCheck($Checks, 'php', 'Versión PHP', PHP_VERSION_ID >= 80100 ? 'ok' : 'warning', 'Versión detectada: ' . PHP_VERSION . '. Recomendado: PHP 8.1 o superior.');
    foreach (['pdo', 'pdo_mysql', 'mbstring', 'zip', 'simplexml', 'fileinfo', 'iconv', 'json'] as $Ext) {
        InstalarAddCheck($Checks, 'ext_' . $Ext, 'Extensión ' . $Ext, extension_loaded($Ext) ? 'ok' : 'error', extension_loaded($Ext) ? 'Disponible.' : 'No disponible. Debe activarse en PHP.');
    }

    $UploadMax = trim((string)ini_get('upload_max_filesize'));
    $PostMax = trim((string)ini_get('post_max_size'));
    $MemoryLimit = trim((string)ini_get('memory_limit'));
    $MaxExecution = trim((string)ini_get('max_execution_time'));
    InstalarAddCheck($Checks, 'php_upload_limits', 'Límites de subida PHP', 'ok', 'upload_max_filesize=' . $UploadMax . ' | post_max_size=' . $PostMax . ' | memory_limit=' . $MemoryLimit . ' | max_execution_time=' . $MaxExecution . '. Recomendado para respaldos/importaciones: 64M o superior.');
    InstalarAddCheck($Checks, 'timezone', 'Zona horaria PHP', date_default_timezone_get() ? 'ok' : 'warning', 'Zona horaria detectada: ' . (date_default_timezone_get() ?: 'sin definir') . '. Recomendado: America/Mexico_City.');
    $RutaSesionReal = InstalarSessionSavePathReal();
    $SesionOk = is_dir($RutaSesionReal) && is_writable($RutaSesionReal);
    InstalarAddCheck($Checks, 'session_path', 'Sesiones PHP', $SesionOk ? 'ok' : 'warning', 'Ruta de sesión detectada: ' . $RutaSesionReal . '. Debe ser escribible para conservar sesión del instalador. SGCE también usa un token temporal de respaldo para evitar falsos bloqueos.');
    $RutaCsrfTemporal = InstalarRutaRaizAplicacion() . '/storage/locks';
    if (!is_dir($RutaCsrfTemporal)) { @mkdir($RutaCsrfTemporal, 0775, true); }
    InstalarAddCheck($Checks, 'installer_csrf_store', 'Token temporal de instalador', is_dir($RutaCsrfTemporal) && is_writable($RutaCsrfTemporal) ? 'ok' : 'warning', 'Ruta de respaldo CSRF: ' . $RutaCsrfTemporal . '. Se usa si PHP pierde la sesión durante el POST.');

    $Rutas = [
        'config' => InstalarRutaRaizAplicacion() . '/config',
        'storage' => InstalarRutaRaizAplicacion() . '/storage',
        'backups' => trim((string)($Valores['BackupDir'] ?? (InstalarRutaRaizAplicacion() . '/storage/backups'))),
        'logs' => InstalarRutaRaizAplicacion() . '/storage/logs',
        'planeaciones' => InstalarRutaRaizAplicacion() . '/storage/planeaciones',
        'tmp_uploads' => InstalarRutaRaizAplicacion() . '/storage/tmp_uploads',
    ];
    foreach ($Rutas as $Clave => $Ruta) {
        if (!is_dir($Ruta)) { @mkdir($Ruta, 0775, true); }
        $Ok = is_dir($Ruta) && is_writable($Ruta);
        InstalarAddCheck($Checks, 'dir_' . $Clave, 'Carpeta ' . $Clave, $Ok ? 'ok' : 'error', $Ruta . ' | ' . InstalarFormatoPermisos($Ruta));
    }

    $ConfigLocal = InstalarRutaRaizAplicacion() . '/config/database.local.php';
    $ConfigDir = dirname($ConfigLocal);
    InstalarAddCheck($Checks, 'config_local_write', 'Escritura de configuración', is_dir($ConfigDir) && is_writable($ConfigDir) ? 'ok' : 'error', 'Destino: ' . $ConfigLocal . ' | Carpeta: ' . InstalarFormatoPermisos($ConfigDir));
    $LockDir = InstalarRutaRaizAplicacion() . '/storage';
    InstalarAddCheck($Checks, 'install_lock_write', 'Bloqueo de instalador', is_dir($LockDir) && is_writable($LockDir) ? 'ok' : 'error', 'Se escribirá storage/install.lock al finalizar. Carpeta: ' . InstalarFormatoPermisos($LockDir));

    $TmpUpload = trim((string)ini_get('upload_tmp_dir'));
    $TmpSistema = function_exists('sys_get_temp_dir') ? trim((string)sys_get_temp_dir()) : '';
    $TmpDetectado = $TmpUpload !== '' ? $TmpUpload : $TmpSistema;
    $TmpOk = $TmpDetectado !== '' && is_dir($TmpDetectado) && is_writable($TmpDetectado);
    InstalarAddCheck(
        $Checks,
        'php_upload_tmp',
        'Temporal de subidas PHP',
        $TmpOk ? 'ok' : 'error',
        'Temporal detectado: ' . ($TmpDetectado !== '' ? $TmpDetectado : 'sin definir') .
        ' | Existe: ' . ($TmpDetectado !== '' && is_dir($TmpDetectado) ? 'sí' : 'no') .
        ' | Escribible: ' . ($TmpDetectado !== '' && is_writable($TmpDetectado) ? 'sí' : 'no') .
        ' | upload_tmp_dir: ' . ($TmpUpload !== '' ? $TmpUpload : 'usa temporal del sistema') .
        ' | Si falla, crea/corrige /tmp con permisos 1777 o configura upload_tmp_dir en PHP.'
    );

    $SqlInstalacion = InstalarRutaInstalador() . '/SGCE.sql';
    InstalarAddCheck($Checks, 'sql', 'Archivo SQL de instalación', is_file($SqlInstalacion) ? 'ok' : 'error', is_file($SqlInstalacion) ? 'Disponible: ' . $SqlInstalacion : 'No se encontró install/SGCE.sql.');
    if ($ProbarMysql) {
        $Host = trim((string)($Valores['Host'] ?? ''));
        $Usuario = trim((string)($Valores['UsuarioMysql'] ?? ''));
        $Password = (string)($Valores['PasswordMysql'] ?? '');
        if ($Host !== '' && $Usuario !== '') {
            try {
                $BaseDatos = trim((string)($Valores['BaseDatos'] ?? ''));
                $PdoServidor = InstalarCrearConexionMysql(InstalarDsnServidorMysql($Host), $Usuario, $Password);
                InstalarAddCheck($Checks, 'mysql', 'Conexión MySQL', 'ok', 'Usuario y contraseña validados contra el servidor MySQL.');
                if ($BaseDatos !== '') {
                    if (!InstalarNombreBaseValido($BaseDatos)) {
                        InstalarAddCheck($Checks, 'mysql_db_name', 'Nombre de base de datos', 'error', 'Solo letras, números y guion bajo.');
                    } elseif (InstalarBaseDatosExiste($PdoServidor, $BaseDatos)) {
                        InstalarAddCheck($Checks, 'mysql_db_exists', 'Base de datos', 'ok', 'La base existe. Se usará para instalar SGCE.');
                        try {
                            $PdoCheck = InstalarCrearConexionMysql(InstalarDsnBaseMysql($Host, $BaseDatos), $Usuario, $Password);
                            $TablasCheck = $PdoCheck->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                            InstalarAddCheck($Checks, 'mysql_empty', 'Base de datos vacía', empty($TablasCheck) ? 'ok' : 'error', empty($TablasCheck) ? 'Lista para instalar.' : 'La base seleccionada ya contiene tablas. Usa una base exclusiva y vacía.');
                        } catch (Throwable $EPermiso) {
                            InstalarRegistrarError($EPermiso, 'VERIFICACION_MYSQL_BASE');
                            InstalarAddCheck($Checks, 'mysql_db_access', 'Permisos sobre la base', 'error', 'El usuario conecta a MySQL, pero no tiene permisos sobre la base indicada.');
                        }
                    } else {
                        InstalarAddCheck($Checks, 'mysql_db_exists', 'Base de datos', 'warning', 'La base no existe. En local se intentará crear automáticamente al instalar; en Plesk normalmente debes crearla primero desde el panel.');
                    }
                }
            } catch (Throwable $E) {
                InstalarRegistrarError($E, 'VERIFICACION_MYSQL');
                InstalarAddCheck($Checks, 'mysql', 'Conexión MySQL', 'error', 'No fue posible conectar al servidor MySQL. Revisa host, usuario y contraseña.');
            }
        } else {
            InstalarAddCheck($Checks, 'mysql', 'Conexión MySQL', 'warning', 'Captura host y usuario MySQL para probar la conexión.');
        }
    }
    return $Checks;
}

function InstalarResumenChecks($Checks) {
    $Resumen = ['ok' => 0, 'warning' => 0, 'error' => 0, 'total' => 0];
    foreach ((array)$Checks as $Check) {
        $Estado = (string)($Check['estado'] ?? 'warning');
        if (!isset($Resumen[$Estado])) { $Estado = 'warning'; }
        $Resumen[$Estado]++;
        $Resumen['total']++;
    }
    return $Resumen;
}

function InstalarChecksCriticosOk($Checks) {
    foreach ($Checks as $Check) {
        if (($Check['estado'] ?? '') === 'error') { return false; }
    }
    return true;
}
