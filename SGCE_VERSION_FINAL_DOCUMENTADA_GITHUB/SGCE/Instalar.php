<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

$Mensaje = '';
$Tipo = 'info';
$SqlFile = __DIR__ . '/install/SGCE.sql';
$LockFile = __DIR__ . '/storage/install.lock';
$LocalConfigFile = __DIR__ . '/config/database.local.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

class InstalarMensajeUsuario extends Exception {}

function HInst($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }

function InstalarCsrfToken() {
    if (empty($_SESSION['InstalarCsrfToken']) || !is_string($_SESSION['InstalarCsrfToken'])) {
        $_SESSION['InstalarCsrfToken'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['InstalarCsrfToken'];
}

function InstalarCampoCsrf() {
    return '<input type="hidden" name="InstalarCsrfToken" value="' . HInst(InstalarCsrfToken()) . '">';
}

function InstalarValidarCsrf($Token) {
    return is_string($Token) && isset($_SESSION['InstalarCsrfToken']) && hash_equals($_SESSION['InstalarCsrfToken'], $Token);
}

function InstalarModoDebug() {
    return getenv('SGCE_DEBUG_INSTALLER') === '1';
}

function InstalarSepararSql($Sql) {
    $Sentencias = [];
    $Actual = '';
    $Comilla = null;
    $Escape = false;
    $Len = strlen($Sql);
    for ($I = 0; $I < $Len; $I++) {
        $Ch = $Sql[$I];
        $Next = ($I + 1 < $Len) ? $Sql[$I + 1] : '';

        if ($Comilla === null && $Ch === '-' && $Next === '-') {
            while ($I < $Len && $Sql[$I] !== "\n") { $I++; }
            continue;
        }
        if ($Comilla === null && $Ch === '#') {
            while ($I < $Len && $Sql[$I] !== "\n") { $I++; }
            continue;
        }
        if ($Comilla === null && $Ch === '/' && $Next === '*') {
            $I += 2;
            while ($I + 1 < $Len && !($Sql[$I] === '*' && $Sql[$I + 1] === '/')) { $I++; }
            $I++;
            continue;
        }

        if ($Comilla !== null) {
            $Actual .= $Ch;
            if ($Escape) { $Escape = false; continue; }
            if ($Ch === '\\') { $Escape = true; continue; }
            if ($Ch === $Comilla) { $Comilla = null; }
            continue;
        }

        if ($Ch === "'" || $Ch === '"' || $Ch === '`') {
            $Comilla = $Ch;
            $Actual .= $Ch;
            continue;
        }

        if ($Ch === ';') {
            $Stmt = trim($Actual);
            if ($Stmt !== '') { $Sentencias[] = $Stmt; }
            $Actual = '';
            continue;
        }

        $Actual .= $Ch;
    }
    $Stmt = trim($Actual);
    if ($Stmt !== '') { $Sentencias[] = $Stmt; }
    return $Sentencias;
}

function InstalarValidarPassword($Password) {
    $Password = (string)$Password;
    if (strlen($Password) < 8) { return 'La contraseña del administrador debe tener mínimo 8 caracteres.'; }
    if (!preg_match('/[A-ZÁÉÍÓÚÜÑ]/u', $Password)) { return 'La contraseña debe incluir al menos una mayúscula.'; }
    if (!preg_match('/[a-záéíóúüñ]/u', $Password)) { return 'La contraseña debe incluir al menos una minúscula.'; }
    if (!preg_match('/\d/', $Password)) { return 'La contraseña debe incluir al menos un número.'; }
    if (!preg_match('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9]/u', $Password)) { return 'La contraseña debe incluir al menos un carácter especial.'; }
    return true;
}

function InstalarMayusculas($Texto) {
    $Texto = (string)$Texto;
    if (function_exists('mb_strtoupper')) { return mb_strtoupper($Texto, 'UTF-8'); }
    $Texto = strtr($Texto, [
        'á'=>'Á','é'=>'É','í'=>'Í','ó'=>'Ó','ú'=>'Ú','ü'=>'Ü','ñ'=>'Ñ',
        'à'=>'À','è'=>'È','ì'=>'Ì','ò'=>'Ò','ù'=>'Ù','ä'=>'Ä','ë'=>'Ë','ï'=>'Ï','ö'=>'Ö'
    ]);
    return strtoupper($Texto);
}

function InstalarLongitud($Texto) {
    $Texto = (string)$Texto;
    return function_exists('mb_strlen') ? mb_strlen($Texto, 'UTF-8') : strlen($Texto);
}

function InstalarNombreBaseValido($Nombre) {
    return preg_match('/^[A-Za-z0-9_]{1,64}$/', (string)$Nombre) === 1;
}

function InstalarDsnServidorMysql($Host) {
    return 'mysql:host=' . trim((string)$Host) . ';charset=utf8mb4';
}

function InstalarDsnBaseMysql($Host, $BaseDatos) {
    return 'mysql:host=' . trim((string)$Host) . ';dbname=' . trim((string)$BaseDatos) . ';charset=utf8mb4';
}

function InstalarCrearConexionMysql($Dsn, $Usuario, $Password) {
    return new PDO($Dsn, $Usuario, $Password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
}

function InstalarBaseDatosExiste(PDO $PdoServidor, $BaseDatos) {
    $Stmt = $PdoServidor->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ? LIMIT 1');
    $Stmt->execute([(string)$BaseDatos]);
    return (bool)$Stmt->fetchColumn();
}

function InstalarCrearBaseDatos(PDO $PdoServidor, $BaseDatos) {
    if (!InstalarNombreBaseValido($BaseDatos)) {
        throw new InstalarMensajeUsuario('El nombre de la base de datos solo puede usar letras, números y guion bajo.');
    }
    $PdoServidor->exec('CREATE DATABASE `' . $BaseDatos . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}

function InstalarNormalizarTexto($Texto, $Mayusculas = false) {
    $Texto = trim(preg_replace('/\s+/u', ' ', (string)$Texto));
    return $Mayusculas ? InstalarMayusculas($Texto) : $Texto;
}

function InstalarValidarFecha($Fecha) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$Fecha)) { return false; }
    $D = DateTime::createFromFormat('Y-m-d', (string)$Fecha);
    return $D && $D->format('Y-m-d') === $Fecha;
}

function InstalarSoloLetrasEspacios($Texto) {
    return preg_match('/^[\p{L} .\'-]+$/u', (string)$Texto) === 1;
}

function InstalarValidarTelefonoOpcional($Telefono) {
    $Telefono = trim((string)$Telefono);
    if ($Telefono === '') { return true; }
    if (!preg_match('/^\d{7,15}$/', $Telefono)) {
        return 'El teléfono debe contener solo números, mínimo 7 y máximo 15 dígitos.';
    }
    return true;
}

function InstalarValidarCorreoOpcional($Correo) {
    $Correo = trim((string)$Correo);
    if ($Correo === '') { return true; }
    if (strlen($Correo) > 120 || filter_var($Correo, FILTER_VALIDATE_EMAIL) === false || strpos($Correo, '@') === false || strpos($Correo, '.') === false) {
        return 'El correo institucional debe tener formato válido, por ejemplo direccion@escuela.com.';
    }
    return true;
}

function InstalarValidarTextoOpcional($Valor, $Campo, $Maximo = 120, $SoloLetras = false) {
    $Valor = trim((string)$Valor);
    if ($Valor === '') { return true; }
    if (InstalarLongitud($Valor) > $Maximo) { return $Campo . ' no debe superar ' . $Maximo . ' caracteres.'; }
    if ($SoloLetras && !InstalarSoloLetrasEspacios($Valor)) { return $Campo . ' solo debe contener letras, espacios, puntos, guiones o apóstrofes.'; }
    return true;
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
    foreach ([__DIR__ . '/storage', __DIR__ . '/storage/logs', __DIR__ . '/storage/planeaciones', __DIR__ . '/storage/tmp_uploads', $BackupDir, __DIR__ . '/config', __DIR__ . '/includes', __DIR__ . '/modules', __DIR__ . '/reports', __DIR__ . '/public', __DIR__ . '/cron'] as $Dir) {
        InstalarAsegurarCarpetaProtegida($Dir);
    }
}

function InstalarLogDir() {
    return __DIR__ . '/storage/logs';
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

function InstalarVerificacionesServidor($Valores = [], $ProbarMysql = false) {
    $Checks = [];
    InstalarAddCheck($Checks, 'php', 'Versión PHP', PHP_VERSION_ID >= 80100 ? 'ok' : 'warning', 'Versión detectada: ' . PHP_VERSION . '. Recomendado: PHP 8.1 o superior.');
    foreach (['pdo', 'pdo_mysql', 'mbstring', 'zip', 'simplexml', 'fileinfo', 'iconv', 'json'] as $Ext) {
        InstalarAddCheck($Checks, 'ext_' . $Ext, 'Extensión ' . $Ext, extension_loaded($Ext) ? 'ok' : 'error', extension_loaded($Ext) ? 'Disponible.' : 'No disponible. Debe activarse en PHP.');
    }
    $Rutas = [
        'config' => __DIR__ . '/config',
        'storage' => __DIR__ . '/storage',
        'backups' => trim((string)($Valores['BackupDir'] ?? (__DIR__ . '/storage/backups'))),
        'logs' => __DIR__ . '/storage/logs',
        'planeaciones' => __DIR__ . '/storage/planeaciones',
    ];
    foreach ($Rutas as $Clave => $Ruta) {
        if (!is_dir($Ruta)) { @mkdir($Ruta, 0775, true); }
        $Ok = is_dir($Ruta) && is_writable($Ruta);
        InstalarAddCheck($Checks, 'dir_' . $Clave, 'Carpeta ' . $Clave, $Ok ? 'ok' : 'error', $Ruta . ' | ' . InstalarFormatoPermisos($Ruta));
    }

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

    InstalarAddCheck($Checks, 'sql', 'Archivo SQL de instalación', is_file(__DIR__ . '/install/SGCE.sql') ? 'ok' : 'error', is_file(__DIR__ . '/install/SGCE.sql') ? 'Disponible.' : 'No se encontró install/SGCE.sql.');
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

function InstalarChecksCriticosOk($Checks) {
    foreach ($Checks as $Check) {
        if (($Check['estado'] ?? '') === 'error') { return false; }
    }
    return true;
}

$YaInstalado = is_file($LockFile) || is_file($LocalConfigFile);

$AnioActual = (int)date('Y');
$Valores = [
    'Host' => $_POST['Host'] ?? 'localhost',
    'BaseDatos' => $_POST['BaseDatos'] ?? '',
    'UsuarioMysql' => $_POST['UsuarioMysql'] ?? '',
    'PasswordMysql' => $_POST['PasswordMysql'] ?? '',
    'AdminNombre' => $_POST['AdminNombre'] ?? '',
    'AdminUsuario' => $_POST['AdminUsuario'] ?? '',
    'BackupDir' => $_POST['BackupDir'] ?? (__DIR__ . '/storage/backups'),
    'NombreEscuela' => $_POST['NombreEscuela'] ?? '',
    'ClaveCentroTrabajo' => $_POST['ClaveCentroTrabajo'] ?? '',
    'DirectorNombre' => $_POST['DirectorNombre'] ?? '',
    'MunicipioEstado' => $_POST['MunicipioEstado'] ?? '',
    'TelefonoEscuela' => $_POST['TelefonoEscuela'] ?? '',
    'CorreoEscuela' => $_POST['CorreoEscuela'] ?? '',
    'ColorInstitucional' => $_POST['ColorInstitucional'] ?? '#97051E',
    'CicloNombre' => $_POST['CicloNombre'] ?? ($AnioActual . '-' . ($AnioActual + 1)),
    'FechaInicio' => $_POST['FechaInicio'] ?? ($AnioActual . '-08-01'),
    'FechaFin' => $_POST['FechaFin'] ?? (($AnioActual + 1) . '-07-31'),
    'PeriodoUno' => $_POST['PeriodoUno'] ?? 'PRIMER PARCIAL',
    'PeriodoDos' => $_POST['PeriodoDos'] ?? 'SEGUNDO PARCIAL',
    'PeriodoTres' => $_POST['PeriodoTres'] ?? 'TERCER PARCIAL',
    'PlaneacionesCantidad' => $_POST['PlaneacionesCantidad'] ?? '3',
];

if (isset($_GET['VerificarServidor'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($YaInstalado) {
        http_response_code(403);
        echo json_encode(['error' => 'Instalador bloqueado.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    echo json_encode(['checks' => InstalarVerificacionesServidor($Valores, true)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$YaInstalado) {
    $DetallesEliminacion = [];
    try {
        if (!InstalarValidarCsrf($_POST['InstalarCsrfToken'] ?? '')) {
            throw new Exception('Solicitud inválida. Recarga el instalador e intenta nuevamente.');
        }
        if (($_POST['ConfirmarInstalacion'] ?? '') !== 'INSTALAR SGCE') {
            throw new Exception('Confirmación inválida. Escribe exactamente INSTALAR SGCE.');
        }
        if (!is_file($SqlFile)) { throw new Exception('No se encontró install/SGCE.sql.'); }

        $Host = trim((string)$Valores['Host']);
        $BaseDatos = trim((string)$Valores['BaseDatos']);
        $UsuarioMysql = trim((string)$Valores['UsuarioMysql']);
        $PasswordMysql = (string)$Valores['PasswordMysql'];
        $AdminNombre = InstalarNormalizarTexto($Valores['AdminNombre'], true);
        $AdminUsuario = trim((string)$Valores['AdminUsuario']);
        $AdminPassword = (string)($_POST['AdminPassword'] ?? '');
        $AdminPasswordConfirm = (string)($_POST['AdminPasswordConfirm'] ?? '');
        $BackupDir = trim((string)$Valores['BackupDir']);

        $NombreEscuela = InstalarNormalizarTexto($Valores['NombreEscuela'], true);
        $ClaveCentroTrabajo = InstalarNormalizarTexto($Valores['ClaveCentroTrabajo'], true);
        $DirectorNombre = InstalarNormalizarTexto($Valores['DirectorNombre'], true);
        $MunicipioEstado = InstalarNormalizarTexto($Valores['MunicipioEstado'], true);
        $TelefonoEscuela = InstalarNormalizarTexto($Valores['TelefonoEscuela']);
        $CorreoEscuela = InstalarNormalizarTexto($Valores['CorreoEscuela']);
        $ColorInstitucional = strtoupper(trim((string)($Valores['ColorInstitucional'] ?? '#97051E')));
        $CicloNombre = InstalarNormalizarTexto($Valores['CicloNombre'], true);
        $FechaInicio = trim((string)$Valores['FechaInicio']);
        $FechaFin = trim((string)$Valores['FechaFin']);
        $PeriodoUno = InstalarNormalizarTexto($Valores['PeriodoUno'], true);
        $PeriodoDos = InstalarNormalizarTexto($Valores['PeriodoDos'], true);
        $PeriodoTres = InstalarNormalizarTexto($Valores['PeriodoTres'], true);
        $PlaneacionesCantidadTexto = trim((string)($Valores['PlaneacionesCantidad'] ?? ''));
        if ($PlaneacionesCantidadTexto === '' || !ctype_digit($PlaneacionesCantidadTexto)) {
            throw new Exception('Escribe la cantidad de planeaciones por ciclo.');
        }
        $PlaneacionesCantidad = (int)$PlaneacionesCantidadTexto;

        if ($Host === '' || $UsuarioMysql === '' || $BaseDatos === '' || !InstalarNombreBaseValido($BaseDatos)) {
            throw new Exception('Revisa host, usuario MySQL y nombre de base de datos. La base solo puede usar letras, números y guion bajo.');
        }
        if ($NombreEscuela === '' || InstalarLongitud($NombreEscuela) < 3 || InstalarLongitud($NombreEscuela) > 150) {
            throw new Exception('Escribe el nombre oficial de la escuela. Debe tener entre 3 y 150 caracteres.');
        }
        if ($ClaveCentroTrabajo !== '' && !preg_match('/^[A-Z0-9\-]{3,30}$/u', $ClaveCentroTrabajo)) {
            throw new Exception('La CCT / clave solo debe usar letras, números o guion, de 3 a 30 caracteres.');
        }
        foreach ([
            InstalarValidarTextoOpcional($DirectorNombre, 'El nombre del director(a)', 120, true),
            InstalarValidarTextoOpcional($MunicipioEstado, 'Municipio y estado', 120, false),
            InstalarValidarTelefonoOpcional($TelefonoEscuela),
            InstalarValidarCorreoOpcional($CorreoEscuela),
        ] as $ValidacionCampo) {
            if ($ValidacionCampo !== true) { throw new Exception($ValidacionCampo); }
        }
        if (!preg_match('/^#[0-9A-F]{6}$/', $ColorInstitucional)) {
            throw new Exception('Selecciona un color institucional válido.');
        }
        $ChecksServidor = InstalarVerificacionesServidor($Valores, false);
        if (!InstalarChecksCriticosOk($ChecksServidor)) {
            throw new Exception('El servidor todavía no cumple los requisitos mínimos. Usa Verificar servidor para revisar permisos y extensiones.');
        }
        if ($AdminNombre === '' || InstalarLongitud($AdminNombre) < 3 || !InstalarSoloLetrasEspacios($AdminNombre)) {
            throw new Exception('Escribe el nombre del administrador. Solo debe contener letras y espacios.');
        }
        if ($AdminUsuario === '' || !preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $AdminUsuario)) {
            throw new Exception('Revisa el usuario administrador. Debe tener mínimo 3 caracteres y acepta letras, números, punto, guion, guion bajo o @.');
        }
        if ($AdminPassword !== $AdminPasswordConfirm) {
            throw new Exception('Las contraseñas del administrador no coinciden. Revisa ambos campos e intenta nuevamente.');
        }
        $ValidacionPassword = InstalarValidarPassword($AdminPassword);
        if ($ValidacionPassword !== true) { throw new Exception($ValidacionPassword); }
        if ($CicloNombre === '' || !InstalarValidarFecha($FechaInicio) || !InstalarValidarFecha($FechaFin) || strtotime($FechaInicio) >= strtotime($FechaFin)) {
            throw new Exception('Revisa el ciclo escolar. Debe tener nombre, fecha de inicio y fecha de fin válida.');
        }
        if ($PeriodoUno === '' || $PeriodoDos === '' || $PeriodoTres === '') {
            throw new Exception('Los tres periodos de evaluación son obligatorios.');
        }
        if (count(array_unique([$PeriodoUno, $PeriodoDos, $PeriodoTres])) !== 3) {
            throw new Exception('Los nombres de los tres periodos no pueden repetirse.');
        }
        if ($PlaneacionesCantidad < 1 || $PlaneacionesCantidad > 12) {
            throw new Exception('La cantidad de planeaciones debe estar entre 1 y 12.');
        }

        
        InstalarVerificarEscritura($LocalConfigFile);
        if (!is_dir(dirname($LockFile))) { @mkdir(dirname($LockFile), 0775, true); }
        if (!is_writable(dirname($LockFile))) {
            throw new Exception('La carpeta storage no tiene permisos de escritura para crear install.lock. Ruta: ' . dirname($LockFile) . ' | Permisos: ' . InstalarFormatoPermisos(dirname($LockFile)) . ' | Usuario PHP: ' . InstalarUsuarioPhp());
        }

        try {
            $PdoServidor = InstalarCrearConexionMysql(InstalarDsnServidorMysql($Host), $UsuarioMysql, $PasswordMysql);
        } catch (Throwable $EConexionServidor) {
            InstalarRegistrarError($EConexionServidor, 'MYSQL_SERVIDOR');
            throw new InstalarMensajeUsuario('No se pudo conectar al servidor MySQL. Revisa host, usuario y contraseña.');
        }

        try {
            if (!InstalarBaseDatosExiste($PdoServidor, $BaseDatos)) {
                try {
                    InstalarCrearBaseDatos($PdoServidor, $BaseDatos);
                } catch (Throwable $ECrearBase) {
                    InstalarRegistrarError($ECrearBase, 'MYSQL_CREAR_BASE');
                    throw new InstalarMensajeUsuario('La base de datos no existe y este usuario MySQL no tiene permiso para crearla. En local usa un usuario con permiso CREATE DATABASE o crea la base manualmente; en Plesk créala primero desde el panel.');
                }
            }
        } catch (InstalarMensajeUsuario $EUsuario) {
            throw $EUsuario;
        } catch (Throwable $EBaseExiste) {
            InstalarRegistrarError($EBaseExiste, 'MYSQL_REVISAR_BASE');
            throw new InstalarMensajeUsuario('No fue posible revisar o preparar la base de datos indicada. Revisa permisos del usuario MySQL sobre esa base.');
        }

        $DsnDb = InstalarDsnBaseMysql($Host, $BaseDatos);
        try {
            $PdoInstall = InstalarCrearConexionMysql($DsnDb, $UsuarioMysql, $PasswordMysql);
        } catch (Throwable $EConexionBase) {
            InstalarRegistrarError($EConexionBase, 'MYSQL_CONECTAR_BASE');
            throw new InstalarMensajeUsuario('La base existe o fue creada, pero el usuario MySQL no tiene permiso para usarla. Asigna permisos completos sobre esa base e intenta nuevamente.');
        }

        $TablasExistentes = $PdoInstall->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($TablasExistentes)) {
            throw new InstalarMensajeUsuario('La base de datos seleccionada no está vacía. Usa una base exclusiva y vacía para evitar mezclar instalaciones.');
        }

        $Sql = file_get_contents($SqlFile);
        if ($Sql === false || trim($Sql) === '') { throw new InstalarMensajeUsuario('El SQL de instalación está vacío.'); }
        foreach (InstalarSepararSql($Sql) as $Sentencia) { $PdoInstall->exec($Sentencia); }

        $PdoDb = InstalarCrearConexionMysql($DsnDb, $UsuarioMysql, $PasswordMysql);

        $PdoDb->beginTransaction();
        $StmtAdmin = $PdoDb->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol, Activo) VALUES (?, ?, ?, 'admin', 1)");
        $StmtAdmin->execute([$AdminUsuario, password_hash($AdminPassword, PASSWORD_DEFAULT), $AdminNombre]);
        $AdminId = (int)$PdoDb->lastInsertId();

        InstalarGuardarConfiguracion($PdoDb, [
            'NombreEscuela' => $NombreEscuela,
            'ClaveCentroTrabajo' => $ClaveCentroTrabajo,
            'DirectorNombre' => $DirectorNombre,
            'MunicipioEstado' => $MunicipioEstado,
            'TelefonoEscuela' => $TelefonoEscuela,
            'CorreoEscuela' => $CorreoEscuela,
            'LemaInstitucional' => '',
            'ColorInstitucional' => $ColorInstitucional,
            'SistemaNombre' => 'SGCE',
            'PlaneacionesCantidad' => (string)$PlaneacionesCantidad,
            'InstalacionFecha' => date('Y-m-d H:i:s'),
        ]);

        $PdoDb->prepare('UPDATE CiclosEscolares SET Activo = 0')->execute();
        $StmtCiclo = $PdoDb->prepare('INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES (?, ?, ?, 1)');
        $StmtCiclo->execute([$CicloNombre, $FechaInicio, $FechaFin]);
        $CicloId = (int)$PdoDb->lastInsertId();
        $StmtPeriodo = $PdoDb->prepare('INSERT INTO PeriodosEvaluacion (CicloId, Nombre, Orden, Activo) VALUES (?, ?, ?, 1)');
        $StmtPeriodo->execute([$CicloId, $PeriodoUno, 1]);
        $StmtPeriodo->execute([$CicloId, $PeriodoDos, 2]);
        $StmtPeriodo->execute([$CicloId, $PeriodoTres, 3]);

        $StmtBitacora = $PdoDb->prepare('INSERT INTO BitacoraMovimientos (UsuarioId, Rol, Accion, TablaAfectada, RegistroId, Detalle, Ip) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $StmtBitacora->execute([$AdminId, 'admin', 'INSTALACION_INICIAL', 'ConfiguracionSistema', null, 'INSTALACIÓN INICIAL DEL SISTEMA', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
        $PdoDb->commit();

        if (!is_dir(dirname($LockFile))) { @mkdir(dirname($LockFile), 0755, true); }
        if (!is_dir($BackupDir)) { @mkdir($BackupDir, 0755, true); }
        InstalarAsegurarProteccionesIniciales($BackupDir);

        $ConfigExport = "<?php\nreturn [\n" .
            "    'host' => " . var_export($Host, true) . ",\n" .
            "    'database' => " . var_export($BaseDatos, true) . ",\n" .
            "    'username' => " . var_export($UsuarioMysql, true) . ",\n" .
            "    'password' => " . var_export($PasswordMysql, true) . ",\n" .
            "    'charset' => 'utf8mb4',\n" .
            "    'timezone' => 'America/Mexico_City',\n" .
            "    'backup_dir' => " . var_export($BackupDir, true) . ",\n" .
            "    'log_dir' => " . var_export(__DIR__ . '/storage/logs', true) . ",\n" .
            "    'planeaciones_dir' => " . var_export(__DIR__ . '/storage/planeaciones', true) . ",\n" .
            "    'production' => true,\n" .
            "];\n";
        InstalarEscribirArchivoSeguro($LocalConfigFile, $ConfigExport);

        $LockOk = file_put_contents($LockFile, 'SGCE INSTALADO: ' . date('Y-m-d H:i:s') . PHP_EOL, LOCK_EX);
        if ($LockOk === false) {
            $Error = error_get_last();
            throw new Exception('La base y la configuración se crearon, pero no se pudo escribir storage/install.lock. Detalle: ' . (($Error['message'] ?? 'sin detalle')));
        }
        InstalarEliminarDirectorio(__DIR__ . '/install', $DetallesEliminacion);
        register_shutdown_function(function(){ @unlink(__FILE__); });

        $Mensaje = 'Instalación completada correctamente. Ya puedes entrar al sistema con el administrador inicial.';
        if ($DetallesEliminacion) { error_log('SGCE instalador: revisar limpieza automática: ' . implode(' | ', $DetallesEliminacion)); }
        $Tipo = 'success';
        $YaInstalado = true;
    } catch (Exception $E) {
        if (isset($PdoDb) && $PdoDb instanceof PDO && $PdoDb->inTransaction()) { $PdoDb->rollBack(); }
        $CodigoError = InstalarRegistrarError($E, 'INSTALACION');
        if ($E instanceof InstalarMensajeUsuario) {
            $Mensaje = $E->getMessage() . ' Código de seguimiento: ' . $CodigoError;
        } else {
            $Mensaje = InstalarModoDebug()
                ? 'Error al instalar: ' . $E->getMessage()
                : 'No se pudo completar la instalación. Verifica los datos capturados y pulsa Verificar servidor. Código de seguimiento: ' . $CodigoError;
        }
        $Tipo = 'danger';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalación SGCE</title>
<link rel="icon" href="assets/media/img/favicon.ico">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.min.css?cache=sgce2026final">
</head>
<body class="SgceBody SgceInstallerPage">
<main class="SgceModuleWrap" style="max-width:1120px">
    <section class="Top SgceInstallerHero">
        <div class="TopLeft">
            <div class="IconBox"><span class="SgceColorIcon" aria-hidden="true">🧰</span></div>
            <div>
                <h1>INSTALACIÓN SGCE</h1>
                <p>Configura la escuela, crea el ciclo escolar inicial y registra el administrador principal.</p>
            </div>
        </div>
    </section>

    <?php if ($Mensaje !== ''): ?>
        <div class="alert alert-<?= HInst($Tipo) ?> SgceInstallerAlert border-0 shadow-sm rounded-4 mt-4 fw-semibold" role="alert">
            <div class="SgceInstallerAlertBody">
                <i class="fa-solid <?= $Tipo === 'success' ? 'fa-circle-check' : ($Tipo === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-info') ?> me-2"></i>
                <span><?= HInst($Mensaje) ?></span>
            </div>
            <button type="button" class="SgceInstallerAlertClose" aria-label="Cerrar mensaje" data-sgce-dismiss>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($YaInstalado): ?>
        <section class="SgcePanel mt-4 p-4 SgceInstallerCard">
            <h2 class="h4 fw-bold text-success"><span class="SgceColorIcon SgceTitleIcon me-2" aria-hidden="true">✅</span>Sistema listo</h2>
            <p class="mb-3">El sistema está listo. Entra desde la pantalla principal con el administrador creado. Por seguridad, el instalador quedó bloqueado; si tu servidor lo permite, elimina el archivo <strong>Instalar.php</strong> después de confirmar el acceso.</p>
            <a href="index.php" class="BtnPrimary text-decoration-none d-inline-flex align-items-center gap-2"><span class="SgceColorIcon" aria-hidden="true">🚪</span> Ir al acceso principal</a>
        </section>
    <?php else: ?>
        <section class="SgcePanel mt-4 p-4 SgceInstallerCard">
            <div class="SgceInstallerTitle">
                <span><span class="SgceColorIcon" aria-hidden="true">⚙️</span></span>
                <div>
                    <h2>Configuración inicial del sistema</h2>
                    <p>Prepara el sistema con los datos oficiales de la escuela, el ciclo escolar inicial y el administrador principal.</p>
                </div>
            </div>
            <div class="SgceInstallerWarning">
                <span class="SgceColorIcon" aria-hidden="true">ℹ️</span>
                <div><strong>Importante:</strong> en local el instalador puede crear la base si el usuario MySQL tiene permiso. En Plesk, crea primero una base exclusiva y vacía desde el panel; SGCE usará esa base para crear las tablas iniciales.</div>
            </div>
            <div class="SgceInstallerCheckPanel" id="SgceInstallerCheckPanel">
                <div>
                    <strong><span class="SgceColorIcon" aria-hidden="true">✅</span> Verificación del servidor</strong>
                    <p>Revisa requisitos de PHP, permisos y conexión antes de instalar.</p>
                </div>
                <button type="button" class="BtnPrimary SgceInstallerVerifyBtn" id="SgceInstallerVerifyBtn"><span class="SgceColorIcon" aria-hidden="true">🛡️</span> Verificar servidor</button>
                <div class="SgceInstallerCheckResults" id="SgceInstallerCheckResults"></div>
            </div>
            <form method="post" class="row g-3 mt-2" id="SgceInstallerForm">
                <?= InstalarCampoCsrf() ?>
                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><span class="SgceColorIcon" aria-hidden="true">🗄️</span> Conexión MySQL</h3></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Host MySQL</label><input class="form-control FormControl" name="Host" value="<?= HInst($Valores['Host']) ?>" required></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Base de datos</label><input class="form-control FormControl" name="BaseDatos" value="<?= HInst($Valores['BaseDatos']) ?>" required maxlength="64" pattern="[A-Za-z0-9_]{1,64}" title="Solo letras, números y guion bajo." placeholder="nombre_base_datos"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Usuario MySQL</label><input class="form-control FormControl" name="UsuarioMysql" value="<?= HInst($Valores['UsuarioMysql']) ?>" required placeholder="usuario_mysql"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Contraseña MySQL</label><input class="form-control FormControl" type="password" name="PasswordMysql" value="<?= HInst($Valores['PasswordMysql']) ?>"></div>
                <div class="col-12"><label class="fw-bold mb-2">Carpeta de respaldos</label><input class="form-control FormControl" name="BackupDir" value="<?= HInst($Valores['BackupDir']) ?>" required></div>

                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><span class="SgceColorIcon" aria-hidden="true">🏫</span> Datos oficiales de la escuela</h3></div>
                <div class="col-md-8"><label class="fw-bold mb-2">Nombre oficial de la escuela</label><input class="form-control FormControl InputUpper" name="NombreEscuela" value="<?= HInst($Valores['NombreEscuela']) ?>" required minlength="3" maxlength="150" placeholder="NOMBRE DE LA ESCUELA"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">CCT / Clave</label><input class="form-control FormControl InputUpper" name="ClaveCentroTrabajo" value="<?= HInst($Valores['ClaveCentroTrabajo']) ?>" maxlength="30" pattern="[A-Z0-9\-]{0,30}" title="Solo letras, números o guion." placeholder="Opcional"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Director(a)</label><input class="form-control FormControl InputUpper" name="DirectorNombre" value="<?= HInst($Valores['DirectorNombre']) ?>" maxlength="120" pattern="[A-ZÁÉÍÓÚÜÑ .'-]*" title="Solo letras y espacios." placeholder="Opcional"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Municipio y estado</label><input class="form-control FormControl InputUpper" name="MunicipioEstado" value="<?= HInst($Valores['MunicipioEstado']) ?>" maxlength="120" placeholder="MUNICIPIO, ESTADO"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Teléfono</label><input class="form-control FormControl InputDigits" type="tel" name="TelefonoEscuela" value="<?= HInst($Valores['TelefonoEscuela']) ?>" inputmode="numeric" autocomplete="tel" minlength="7" maxlength="15" pattern="\d{7,15}" title="Solo números, mínimo 7 y máximo 15 dígitos." placeholder="Opcional"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Correo institucional</label><input class="form-control FormControl" type="email" name="CorreoEscuela" value="<?= HInst($Valores['CorreoEscuela']) ?>" maxlength="120" autocomplete="email" placeholder="Opcional: correo@escuela.edu"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Color institucional</label><div class="SgceColorControl"><input class="form-control FormControl" type="color" name="ColorInstitucional" id="ColorInstitucional" value="<?= HInst($Valores['ColorInstitucional'] ?: '#97051E') ?>"><span id="ColorInstitucionalTexto"><?= HInst($Valores['ColorInstitucional'] ?: '#97051E') ?></span></div></div>

                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><span class="SgceColorIcon" aria-hidden="true">📅</span> Ciclo escolar inicial</h3></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Nombre del ciclo</label><input class="form-control FormControl InputUpper" name="CicloNombre" value="<?= HInst($Valores['CicloNombre']) ?>" required maxlength="40"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Fecha de inicio</label><input class="form-control FormControl" type="date" name="FechaInicio" value="<?= HInst($Valores['FechaInicio']) ?>" required></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Fecha de fin</label><input class="form-control FormControl" type="date" name="FechaFin" value="<?= HInst($Valores['FechaFin']) ?>" required></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Periodo 1</label><input class="form-control FormControl InputUpper" name="PeriodoUno" value="<?= HInst($Valores['PeriodoUno']) ?>" required maxlength="80"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Periodo 2</label><input class="form-control FormControl InputUpper" name="PeriodoDos" value="<?= HInst($Valores['PeriodoDos']) ?>" required maxlength="80"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Periodo 3</label><input class="form-control FormControl InputUpper" name="PeriodoTres" value="<?= HInst($Valores['PeriodoTres']) ?>" required maxlength="80"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Planeaciones por ciclo</label><input class="form-control FormControl InputDigits" name="PlaneacionesCantidad" value="<?= HInst($Valores['PlaneacionesCantidad']) ?>" required min="1" max="12" maxlength="2" inputmode="numeric" placeholder="Cantidad de entregas"><small class="text-muted fw-semibold">Define cuántas entregas solicitará la institución por materia en el ciclo activo.</small></div>

                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><span class="SgceColorIcon" aria-hidden="true">👤</span> Administrador inicial</h3></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Nombre del administrador</label><input class="form-control FormControl InputUpper" name="AdminNombre" value="<?= HInst($Valores['AdminNombre']) ?>" required minlength="3" maxlength="120" pattern="[A-ZÁÉÍÓÚÜÑ .'-]+" title="Solo letras y espacios." placeholder="NOMBRE COMPLETO"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Usuario administrador</label><input class="form-control FormControl" name="AdminUsuario" value="<?= HInst($Valores['AdminUsuario']) ?>" required minlength="3" maxlength="80" pattern="[A-Za-z0-9._@-]{3,80}" autocomplete="username" placeholder="usuario_admin"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Contraseña administrador</label><input class="form-control FormControl" type="password" name="AdminPassword" required minlength="8" autocomplete="new-password" placeholder="Mínimo 8, mayúscula, minúscula, número y símbolo"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Repetir contraseña</label><input class="form-control FormControl" type="password" name="AdminPasswordConfirm" required minlength="8" autocomplete="new-password" placeholder="Repite la contraseña del administrador"></div>
                <div class="col-12"><label class="fw-bold mb-2">Confirmación</label><input class="form-control FormControl" name="ConfirmarInstalacion" placeholder="INSTALAR SGCE" required></div>
                <div class="col-12"><button type="submit" class="BtnPrimary SgceInstallerBtn border-0"><span class="SgceColorIcon" aria-hidden="true">✨</span> Instalar sistema</button></div>
            </form>
        </section>
    <?php endif; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sgce-shared.js?cache=sgce2026final"></script>
<script src="assets/js/Instalar.js?cache=sgce2026final"></script>
</body>
</html>
