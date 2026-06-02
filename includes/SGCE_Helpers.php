<?php

function IniciarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => EsHttps(),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }
        session_start();
    }
}

function EsHttps() {
    $HttpsDirecto = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
    $ProtoProxy = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    $SslProxy = strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
    $PuertoProxy = (int)($_SERVER['HTTP_X_FORWARDED_PORT'] ?? 0);
    return $HttpsDirecto || $ProtoProxy === 'https' || $SslProxy === 'on' || $PuertoProxy === 443;
}

function EnviarHeadersSeguridad() {
    if (headers_sent()) { return; }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; script-src 'self' https://cdn.jsdelivr.net; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; frame-ancestors 'self'; form-action 'self'; base-uri 'self'");
    if (EsHttps()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

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
        $Raiz . '/reports',
        $Raiz . '/repositories',
        $Raiz . '/services',
        $Raiz . '/public',
        $Raiz . '/cron',
    ];
    foreach (array_unique($Dirs) as $Dir) { SgceAsegurarCarpetaProtegida($Dir); }
    if (defined('SGCE_LOG_DIR') && is_dir(SGCE_LOG_DIR) && is_writable(SGCE_LOG_DIR)) {
        @ini_set('error_log', rtrim(SGCE_LOG_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'php-runtime.log');
    }
}

function SgceEnviarHeadersNoCacheDescarga() {
    if (headers_sent()) { return; }
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
}

function SgceCerrarSesionPhpCompleta() {
    if (session_status() === PHP_SESSION_NONE) { return; }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $Params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $Params['path'] ?? '/',
            'domain' => $Params['domain'] ?? '',
            'secure' => (bool)($Params['secure'] ?? EsHttps()),
            'httponly' => true,
            'samesite' => $Params['samesite'] ?? 'Strict',
        ]);
    }
    session_destroy();
}

function HGlobal($Texto) {
    return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8');
}


function SgceSalirConError($Mensaje, $Codigo = 400) {
    $Codigo = max(400, min(599, (int)$Codigo));
    http_response_code($Codigo);
    exit(HGlobal($Mensaje));
}

function ObtenerCsrfToken() {
    IniciarSesionSegura();
    if (empty($_SESSION['CsrfToken']) || !is_string($_SESSION['CsrfToken'])) {
        $_SESSION['CsrfToken'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['CsrfToken'];
}

function ValidarCsrfToken($Token) {
    IniciarSesionSegura();
    return is_string($Token) && isset($_SESSION['CsrfToken']) && hash_equals($_SESSION['CsrfToken'], $Token);
}

function RequerirCsrfPost() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { return; }
    if (!ValidarCsrfToken($_POST['CsrfToken'] ?? '')) {
        SgceSalirConError('Solicitud inválida. Recarga la página e intenta nuevamente.', 403);
    }
}

function CampoCsrf() {
    return '<input type="hidden" name="CsrfToken" value="' . HGlobal(ObtenerCsrfToken()) . '">';
}

function ImprimirCsrfScript() {
    $Token = HGlobal(ObtenerCsrfToken());
    echo "\n<span data-sgce-csrf-token=\"" . $Token . "\" hidden></span>\n";
}

function ObtenerIpCliente() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function SgcePasswordHash($Password) {
    return password_hash((string)$Password, PASSWORD_DEFAULT);
}

function SgceValidarPasswordFuerte($Password, $Minimo = 8) {
    $Password = (string)$Password;
    if (strlen($Password) < $Minimo) {
        return 'La contraseña debe tener mínimo ' . (int)$Minimo . ' caracteres.';
    }
    if (!preg_match('/[A-ZÁÉÍÓÚÜÑ]/u', $Password)) {
        return 'La contraseña debe incluir al menos una mayúscula.';
    }
    if (!preg_match('/[a-záéíóúüñ]/u', $Password)) {
        return 'La contraseña debe incluir al menos una minúscula.';
    }
    if (!preg_match('/\d/', $Password)) {
        return 'La contraseña debe incluir al menos un número.';
    }
    if (!preg_match('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9]/u', $Password)) {
        return 'La contraseña debe incluir al menos un carácter especial.';
    }
    return true;
}

function SgcePasswordVerify($Password, $Hash) {
    return is_string($Hash) && password_verify((string)$Password, $Hash);
}

function SgceCadenaMayusculas($Texto) {
    $Texto = (string)$Texto;
    if (function_exists('mb_strtoupper')) { return mb_strtoupper($Texto, 'UTF-8'); }
    $Texto = strtr($Texto, [
        'á'=>'Á','é'=>'É','í'=>'Í','ó'=>'Ó','ú'=>'Ú','ü'=>'Ü','ñ'=>'Ñ',
        'à'=>'À','è'=>'È','ì'=>'Ì','ò'=>'Ò','ù'=>'Ù','ä'=>'Ä','ë'=>'Ë','ï'=>'Ï','ö'=>'Ö'
    ]);
    return strtoupper($Texto);
}

function SgceLongitudTexto($Texto) {
    $Texto = (string)$Texto;
    return function_exists('mb_strlen') ? mb_strlen($Texto, 'UTF-8') : strlen($Texto);
}

function SgceNormalizarMayusculas($Valor) {
    $Valor = trim((string)$Valor);
    $Valor = preg_replace('/\s+/u', ' ', $Valor);
    return SgceCadenaMayusculas($Valor);
}

function SgceNormalizarNombre($Valor) {
    $Valor = SgceNormalizarMayusculas($Valor);
    return preg_replace('/[^A-ZÁÉÍÓÚÜÑ\s]/u', '', $Valor);
}

function SgceNormalizarGrupo($Valor) {
    $Valor = SgceNormalizarMayusculas($Valor);
    return preg_match('/^[A-ZÁÉÍÓÚÜÑ]{1,3}$/u', $Valor) ? $Valor : '';
}

function SgceValidarGrado($Valor) {
    $Valor = trim((string)$Valor);
    return $Valor !== '' && ctype_digit($Valor);
}

function SgceNormalizarTurno($Valor) {
    $Valor = SgceNormalizarMayusculas($Valor);
    return in_array($Valor, ['MATUTINO', 'VESPERTINO'], true) ? $Valor : '';
}

function SgceNormalizarTextoUsuarios($Valor) {
    return SgceNormalizarMayusculas($Valor);
}

function SgceCrearTablaConfiguracionSiNoExiste($Pdo) {
    static $TablaConfiguracionLista = false;
    if ($TablaConfiguracionLista) { return; }
    $Pdo->exec("CREATE TABLE IF NOT EXISTS ConfiguracionSistema (
        Clave VARCHAR(80) NOT NULL PRIMARY KEY,
        Valor TEXT NULL,
        FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_config_fecha (FechaActualizacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $TablaConfiguracionLista = true;
}

function SgceConfiguracionDefault() {
    return [
        'NombreEscuela' => 'ESCUELA SIN CONFIGURAR',
        'ClaveCentroTrabajo' => '',
        'DirectorNombre' => '',
        'MunicipioEstado' => '',
        'TelefonoEscuela' => '',
        'CorreoEscuela' => '',
        'LemaInstitucional' => '',
        'ColorInstitucional' => '#97051E',
        'SistemaNombre' => 'SGCE',
        'PlaneacionesCantidad' => '',
    ];
}

function SgceObtenerConfiguracion($Pdo, $ForzarRecarga = false) {
    if (!$ForzarRecarga && isset($GLOBALS['SGCE_CONFIG_CACHE']) && is_array($GLOBALS['SGCE_CONFIG_CACHE'])) {
        return $GLOBALS['SGCE_CONFIG_CACHE'];
    }

    $Config = SgceConfiguracionDefault();
    try {
        if (!$Pdo->inTransaction()) { SgceCrearTablaConfiguracionSiNoExiste($Pdo); }
        $Stmt = $Pdo->query('SELECT Clave, Valor FROM ConfiguracionSistema');
        foreach ($Stmt->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $Clave = (string)($Row['Clave'] ?? '');
            if ($Clave !== '' && array_key_exists($Clave, $Config)) {
                $Config[$Clave] = (string)($Row['Valor'] ?? '');
            }
        }
    } catch (Exception $E) {}

    $GLOBALS['SGCE_CONFIG_CACHE'] = $Config;
    return $Config;
}

function SgceGuardarConfiguracion($Pdo, $Datos) {
    if (!$Pdo->inTransaction()) { SgceCrearTablaConfiguracionSiNoExiste($Pdo); }
    unset($GLOBALS['SGCE_CONFIG_CACHE']);
    $Permitidas = array_keys(SgceConfiguracionDefault());
    $Stmt = $Pdo->prepare('INSERT INTO ConfiguracionSistema (Clave, Valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE Valor = VALUES(Valor), FechaActualizacion = CURRENT_TIMESTAMP');
    foreach ($Permitidas as $Clave) {
        if (array_key_exists($Clave, $Datos)) {
            $Stmt->execute([$Clave, (string)$Datos[$Clave]]);
        }
    }
}

function SgceNormalizarColorHex($Color, $Default = '#97051E') {
    $Color = trim((string)$Color);
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $Color)) { return $Default; }
    return strtoupper($Color);
}

function SgceColorAjustar($Color, $Porcentaje) {
    $Color = ltrim(SgceNormalizarColorHex($Color), '#');
    $R = hexdec(substr($Color, 0, 2));
    $G = hexdec(substr($Color, 2, 2));
    $B = hexdec(substr($Color, 4, 2));
    $Porcentaje = max(-100, min(100, (int)$Porcentaje));
    $Target = $Porcentaje >= 0 ? 255 : 0;
    $Factor = abs($Porcentaje) / 100;
    $R = (int)round($R + ($Target - $R) * $Factor);
    $G = (int)round($G + ($Target - $G) * $Factor);
    $B = (int)round($B + ($Target - $B) * $Factor);
    return sprintf('#%02X%02X%02X', max(0, min(255, $R)), max(0, min(255, $G)), max(0, min(255, $B)));
}

function SgceColorRgb($Color) {
    $Color = ltrim(SgceNormalizarColorHex($Color), '#');
    return [hexdec(substr($Color, 0, 2)), hexdec(substr($Color, 2, 2)), hexdec(substr($Color, 4, 2))];
}

function SgceColorInstitucional($Pdo) {
    $Config = SgceObtenerConfiguracion($Pdo);
    return SgceNormalizarColorHex($Config['ColorInstitucional'] ?? '#97051E');
}

function SgceEstilosTema($Pdo) {
    $Base = SgceColorInstitucional($Pdo);
    $Oscuro = SgceColorAjustar($Base, -22);
    $Profundo = SgceColorAjustar($Base, -48);
    $Suave = SgceColorAjustar($Base, 84);
    $Claro = SgceColorAjustar($Base, 32);
    [$R, $G, $B] = SgceColorRgb($Base);
    return '<style id="SgceTemaInstitucional">:root{--SgceGuinda:' . $Base . ';--SgceGuindaRGB:' . $R . ',' . $G . ',' . $B . ';--SgceGuindaOscuro:' . $Oscuro . ';--SgceGuindaProfundo:' . $Profundo . ';--SgceGuindaSuave:' . $Suave . ';--SgceGuindaClaro:' . $Claro . ';--SgceSombraGuinda:0 12px 26px rgba(' . $R . ',' . $G . ',' . $B . ',.14);}</style>';
}

function SgceRolesSistema() {
    return [
        'admin' => 'ADMINISTRADOR',
        'administrativo' => 'ADMINISTRATIVO',
        'maestro' => 'MAESTRO',
    ];
}

function SgceNormalizarRolSistema($Rol) {
    $Rol = strtolower(trim((string)$Rol));
    return in_array($Rol, ['admin', 'administrativo', 'maestro'], true) ? $Rol : '';
}

function SgceValidarRolUsuario($Rol, $Roles = null) {
    $Roles = $Roles ?: SgceRolesSistema();
    return array_key_exists(SgceNormalizarRolSistema($Rol), $Roles);
}

function SgceRolSesion($UserSession) {
    return is_array($UserSession) ? SgceNormalizarRolSistema($UserSession['Rol'] ?? '') : '';
}

function SgceTieneRol($UserSession, $Roles) {
    $Rol = SgceRolSesion($UserSession);
    $RolesNormalizados = array_map('SgceNormalizarRolSistema', (array)$Roles);
    return $Rol !== '' && in_array($Rol, $RolesNormalizados, true);
}

function SgcePermisosPorRol() {
    return [
        'admin' => [
            'admin.panel', 'admin.dashboard', 'usuarios', 'catalogos', 'periodos', 'avisos', 'reportes',
            'respaldos', 'bitacora', 'configuracion', 'asistencia', 'asistencia_editar',
            'asistencia_historica', 'calificaciones', 'importar', 'planeaciones'
        ],
        'administrativo' => [
            'admin.panel', 'admin.dashboard', 'catalogos', 'avisos', 'reportes',
            'asistencia', 'asistencia_editar', 'asistencia_historica', 'calificaciones', 'importar', 'planeaciones'
        ],
        'maestro' => ['docente', 'asistencia', 'calificaciones', 'planeaciones'],
    ];
}

function SgceTienePermiso($UserSession, $Permiso) {
    $Rol = SgceRolSesion($UserSession);
    if ($Rol === '') { return false; }
    $Mapa = SgcePermisosPorRol();
    return in_array($Permiso, $Mapa[$Rol] ?? [], true);
}

function SgceDenegarAcceso($Mensaje = 'No tienes permiso para entrar a esta sección.') {
    http_response_code(403);
    $MensajeSeguro = HGlobal($Mensaje);
    $Inicio = 'index.php';
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Acceso denegado | SGCE</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/sgce-base.min.css?v=sgce">
<link rel="stylesheet" href="assets/css/sgce-soft-motion.css?v=sgce"></head><body><main class="container py-5"><section class="card card-custom p-5 text-center mx-auto" style="max-width:680px"><div class="display-5 text-danger mb-3"><i class="fa-solid fa-lock"></i></div><h1 class="fw-black mb-2">Acceso denegado</h1><p class="text-muted fw-semibold mb-4">' . $MensajeSeguro . '</p><a class="SgceBtnVolverInicio mx-auto" href="' . $Inicio . '"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a></section></main></body></html>';
    exit;
}

function SgceExigirPermiso($UserSession, $Permiso, $Mensaje = 'No tienes permiso para entrar a esta sección.') {
    if (!SgceTienePermiso($UserSession, $Permiso)) { SgceDenegarAcceso($Mensaje); }
}

function SgcePuedeGestionarUsuarios($UserSession) { return SgceTienePermiso($UserSession, 'usuarios'); }
function SgcePuedeGestionarCatalogos($UserSession) { return SgceTienePermiso($UserSession, 'catalogos'); }
function SgcePuedeAdministrarReportes($UserSession) { return SgceTienePermiso($UserSession, 'reportes'); }
function SgcePuedeAdministrarPeriodos($UserSession) { return SgceTienePermiso($UserSession, 'periodos'); }
function SgcePuedeRespaldos($UserSession) { return SgceTienePermiso($UserSession, 'respaldos'); }
function SgcePuedeBitacora($UserSession) { return SgceTienePermiso($UserSession, 'bitacora'); }
function SgcePuedeImportarCatalogos($UserSession) { return SgceTienePermiso($UserSession, 'importar'); }
function SgcePuedeConfigurarSistema($UserSession) { return SgceTienePermiso($UserSession, 'configuracion'); }
function SgcePuedeGestionarPlaneaciones($UserSession) { return SgceTienePermiso($UserSession, 'planeaciones') && !SgceTieneRol($UserSession, ['maestro']); }
function SgcePuedeCorregirAsistenciaHistorica($UserSession) { return SgceTienePermiso($UserSession, 'asistencia_historica'); }
function SgcePuedePanelAdmin($UserSession) { return SgceTienePermiso($UserSession, 'admin.panel'); }

function SgceUrlInicioPorRol($UserSession) {
    $Rol = SgceRolSesion($UserSession);
    if ($Rol === 'maestro') { return 'Maestro.php'; }
    if (in_array($Rol, ['admin', 'administrativo'], true)) { return 'Admin.php?Tab=inicio'; }
    return 'index.php';
}

function SgceTabsAdminPermitidas($UserSession = null) {
    $Tabs = ['inicio', 'maestros', 'grupos', 'alumnos', 'expedientes', 'asignaciones'];
    if (SgcePuedeBitacora($UserSession)) { $Tabs[] = 'bitacora'; }
    return $Tabs;
}

function SgceTabAdminPermitida($Tab, $UserSession = null) {
    $Tab = (string)$Tab;
    $Permitidas = SgceTabsAdminPermitidas($UserSession);
    return in_array($Tab, $Permitidas, true) ? $Tab : 'inicio';
}

function SgceRedirectAdminTab($Tab, $UserSession = null) {
    header('Location: Admin.php?Tab=' . urlencode(SgceTabAdminPermitida($Tab, $UserSession)));
    exit;
}

function SgcePageSizeSeguro($Valor, $Default = 50, $Min = 5, $Max = 100) {
    $Valor = (int)$Valor;
    if ($Valor <= 0) { $Valor = (int)$Default; }
    return max((int)$Min, min((int)$Max, $Valor));
}

function SgcePaginaActual($Nombre, $Default = 1) {
    $Valor = isset($_GET[$Nombre]) ? (int)$_GET[$Nombre] : (int)$Default;
    return max(1, $Valor);
}

function SgceLimitOffset($Pagina, $PorPagina) {
    $Pagina = max(1, (int)$Pagina);
    $PorPagina = max(1, min(100, (int)$PorPagina));
    return [($Pagina - 1) * $PorPagina, $PorPagina];
}

function SgceRenderPager($NombrePagina, $PaginaActual, $TotalRegistros, $PorPagina, $ParametrosExtra = []) {
    $TotalPaginas = max(1, (int)ceil(((int)$TotalRegistros) / max(1, (int)$PorPagina)));
    if ($TotalPaginas <= 1) {
        return '<div class="SgcePagerServer text-muted small">Mostrando ' . (int)$TotalRegistros . ' registro(s).</div>';
    }
    $PaginaActual = min(max(1, (int)$PaginaActual), $TotalPaginas);
    $Html = '<nav class="SgcePagerServer" aria-label="Paginación"><ul class="pagination pagination-sm justify-content-center flex-wrap gap-1">';
    $Base = $_GET;
    foreach ($ParametrosExtra as $K => $V) { $Base[$K] = $V; }
    $Crear = function($Pagina, $Texto, $Disabled = false, $Active = false) use ($Base, $NombrePagina) {
        $Params = $Base;
        $Params[$NombrePagina] = $Pagina;
        $Clase = 'page-item' . ($Disabled ? ' disabled' : '') . ($Active ? ' active' : '');
        return '<li class="' . $Clase . '"><a class="page-link" href="?' . HGlobal(http_build_query($Params)) . '">' . $Texto . '</a></li>';
    };
    $Html .= $Crear(max(1, $PaginaActual - 1), '&laquo;', $PaginaActual <= 1);
    $Inicio = max(1, $PaginaActual - 2);
    $Fin = min($TotalPaginas, $PaginaActual + 2);
    if ($Inicio > 1) { $Html .= $Crear(1, '1', false, $PaginaActual === 1); }
    for ($I = $Inicio; $I <= $Fin; $I++) { $Html .= $Crear($I, (string)$I, false, $I === $PaginaActual); }
    if ($Fin < $TotalPaginas) { $Html .= $Crear($TotalPaginas, (string)$TotalPaginas, false, $PaginaActual === $TotalPaginas); }
    $Html .= $Crear(min($TotalPaginas, $PaginaActual + 1), '&raquo;', $PaginaActual >= $TotalPaginas);
    $Html .= '</ul><div class="text-center text-muted small">Página ' . $PaginaActual . ' de ' . $TotalPaginas . ' · ' . (int)$TotalRegistros . ' registro(s)</div></nav>';
    return $Html;
}

function SgceContarAdminsActivos($Pdo) {
    $Stmt = $Pdo->query("SELECT COUNT(*) FROM Usuarios WHERE Rol = 'admin' AND Activo = 1");
    return (int)$Stmt->fetchColumn();
}

function SgcePeriodoActualId($Pdo, $PeriodoSolicitado = 0) {
    $PeriodoSolicitado = (int)$PeriodoSolicitado;
    if ($PeriodoSolicitado > 0) {
        $Stmt = $Pdo->prepare('SELECT P.Id FROM PeriodosEvaluacion P INNER JOIN CiclosEscolares C ON C.Id = P.CicloId WHERE P.Id = ? AND P.Activo = 1 AND C.Activo = 1 AND P.Orden BETWEEN 1 AND 3 LIMIT 1');
        $Stmt->execute([$PeriodoSolicitado]);
        $Id = (int)$Stmt->fetchColumn();
        if ($Id > 0) { return $Id; }
    }
    $Stmt = $Pdo->query("SELECT P.Id FROM PeriodosEvaluacion P INNER JOIN CiclosEscolares C ON C.Id = P.CicloId WHERE P.Activo = 1 AND C.Activo = 1 AND P.Orden BETWEEN 1 AND 3 ORDER BY C.FechaInicio DESC, P.Orden ASC, P.Id ASC LIMIT 1");
    return (int)$Stmt->fetchColumn();
}

function SgceCicloActivo($Pdo) {
    $Stmt = $Pdo->query("SELECT Id, Nombre, FechaInicio, FechaFin FROM CiclosEscolares WHERE Activo = 1 ORDER BY FechaInicio DESC, Id DESC LIMIT 1");
    return $Stmt->fetch() ?: ['Id' => 0, 'Nombre' => '', 'FechaInicio' => null, 'FechaFin' => null];
}

function SgcePeriodoInfo($Pdo, $PeriodoId) {
    $Stmt = $Pdo->prepare("SELECT P.Id, P.Nombre, P.Orden, P.CicloId, C.Nombre AS CicloNombre, C.FechaInicio, C.FechaFin FROM PeriodosEvaluacion P INNER JOIN CiclosEscolares C ON C.Id = P.CicloId WHERE P.Id = ? AND P.Activo = 1 AND C.Activo = 1 AND P.Orden BETWEEN 1 AND 3 LIMIT 1");
    $Stmt->execute([(int)$PeriodoId]);
    return $Stmt->fetch() ?: null;
}

function SgceValidarParcial($Orden) {
    $Orden = (int)$Orden;
    return $Orden >= 1 && $Orden <= 3;
}

function SgcePeriodosDisponibles($Pdo) {
    return $Pdo->query("SELECT P.Id, P.Nombre, P.Orden, C.Nombre AS CicloNombre, C.Id AS CicloId FROM PeriodosEvaluacion P INNER JOIN CiclosEscolares C ON C.Id = P.CicloId WHERE P.Activo = 1 AND C.Activo = 1 AND P.Orden BETWEEN 1 AND 3 ORDER BY C.FechaInicio DESC, P.Orden ASC, P.Id ASC")->fetchAll();
}

function VerificarSesionCookie($Pdo) {
    if (empty($_COOKIE['AuthToken'])) { return false; }
    $Token = trim((string)$_COOKIE['AuthToken']);
    if ($Token === '' || !preg_match('/^[a-f0-9]{64}$/i', $Token)) { return false; }
    $Stmt = $Pdo->prepare('SELECT Id, Username, NombreCompleto, Rol FROM Usuarios WHERE SessionToken = ? AND Activo = 1 AND SessionTokenExpira >= NOW() LIMIT 1');
    $Stmt->execute([$Token]);
    $User = $Stmt->fetch() ?: false;
    if ($User) { $User['Rol'] = SgceNormalizarRolSistema($User['Rol'] ?? ''); }
    return $User;
}

function CrearTablaRateLimitSiNoExiste($Pdo) {
    $Pdo->exec("CREATE TABLE IF NOT EXISTS IntentosSeguridad (
        Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ClaveHash CHAR(64) NOT NULL,
        Contexto VARCHAR(40) NOT NULL,
        Intentos INT UNSIGNED NOT NULL DEFAULT 0,
        BloqueadoHasta DATETIME DEFAULT NULL,
        UltimoIntento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unico_contexto_clave (Contexto, ClaveHash),
        INDEX idx_intentos_bloqueado (Contexto, BloqueadoHasta),
        INDEX idx_intentos_ultimo (UltimoIntento)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function RateLimitClave($Contexto, $Identificador = '') {
    return hash('sha256', $Contexto . '|' . ObtenerIpCliente() . '|' . trim((string)$Identificador));
}

function RateLimitDisponible($Pdo, $Contexto, $Identificador = '') {
    try {
        CrearTablaRateLimitSiNoExiste($Pdo);
        $Stmt = $Pdo->prepare('SELECT BloqueadoHasta FROM IntentosSeguridad WHERE Contexto = ? AND ClaveHash = ? LIMIT 1');
        $Stmt->execute([$Contexto, RateLimitClave($Contexto, $Identificador)]);
        $Row = $Stmt->fetch();
        return !$Row || empty($Row['BloqueadoHasta']) || strtotime($Row['BloqueadoHasta']) <= time();
    } catch (Exception $E) { return true; }
}

function RateLimitRegistrarFallo($Pdo, $Contexto, $Identificador = '', $MaxIntentos = 5, $VentanaMinutos = 15) {
    try {
        CrearTablaRateLimitSiNoExiste($Pdo);
        $ClaveHash = RateLimitClave($Contexto, $Identificador);
        $Stmt = $Pdo->prepare('SELECT Intentos, UltimoIntento FROM IntentosSeguridad WHERE Contexto = ? AND ClaveHash = ? LIMIT 1');
        $Stmt->execute([$Contexto, $ClaveHash]);
        $Row = $Stmt->fetch();
        $Intentos = ($Row && strtotime($Row['UltimoIntento']) >= time() - ($VentanaMinutos * 60)) ? ((int)$Row['Intentos'] + 1) : 1;
        $BloqueadoHasta = $Intentos >= $MaxIntentos ? date('Y-m-d H:i:s', time() + ($VentanaMinutos * 60)) : null;
        $Upsert = $Pdo->prepare('INSERT INTO IntentosSeguridad (Contexto, ClaveHash, Intentos, BloqueadoHasta, UltimoIntento) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE Intentos = VALUES(Intentos), BloqueadoHasta = VALUES(BloqueadoHasta), UltimoIntento = NOW()');
        $Upsert->execute([$Contexto, $ClaveHash, $Intentos, $BloqueadoHasta]);
    } catch (Exception $E) {}
}

function RateLimitLimpiar($Pdo, $Contexto, $Identificador = '') {
    try {
        CrearTablaRateLimitSiNoExiste($Pdo);
        $Stmt = $Pdo->prepare('DELETE FROM IntentosSeguridad WHERE Contexto = ? AND ClaveHash = ?');
        $Stmt->execute([$Contexto, RateLimitClave($Contexto, $Identificador)]);
    } catch (Exception $E) {}
}

function CrearTablaBitacoraSiNoExiste($Pdo) {
    static $TablaBitacoraLista = false;
    if ($TablaBitacoraLista) { return; }
    $Pdo->exec("CREATE TABLE IF NOT EXISTS BitacoraMovimientos (
        Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        UsuarioId INT UNSIGNED DEFAULT NULL,
        Rol VARCHAR(30) DEFAULT NULL,
        Accion VARCHAR(80) NOT NULL,
        TablaAfectada VARCHAR(80) DEFAULT NULL,
        RegistroId BIGINT UNSIGNED DEFAULT NULL,
        Detalle TEXT DEFAULT NULL,
        Ip VARCHAR(45) DEFAULT NULL,
        FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_bitacora_fecha (FechaRegistro),
        INDEX idx_bitacora_usuario_fecha (UsuarioId, FechaRegistro),
        INDEX idx_bitacora_accion_fecha (Accion, FechaRegistro),
        INDEX idx_bitacora_fecha_id (FechaRegistro, Id),
        INDEX idx_bitacora_tabla_registro (TablaAfectada, RegistroId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $TablaBitacoraLista = true;
}

function RegistrarBitacora($Pdo, $UserSession, $Accion, $TablaAfectada = null, $RegistroId = null, $Detalle = null) {
    try {
        
        if (!$Pdo->inTransaction()) { CrearTablaBitacoraSiNoExiste($Pdo); }
        $Stmt = $Pdo->prepare('INSERT INTO BitacoraMovimientos (UsuarioId, Rol, Accion, TablaAfectada, RegistroId, Detalle, Ip) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $Stmt->execute([
            is_array($UserSession) && isset($UserSession['Id']) ? $UserSession['Id'] : null,
            is_array($UserSession) && isset($UserSession['Rol']) ? $UserSession['Rol'] : null,
            (string)$Accion,
            $TablaAfectada,
            $RegistroId,
            $Detalle,
            ObtenerIpCliente(),
        ]);
    } catch (Exception $E) {}
}

function SgceCantidadPlaneaciones($Pdo) {
    $Config = SgceObtenerConfiguracion($Pdo);
    $Cantidad = (int)($Config['PlaneacionesCantidad'] ?? 1);
    return max(1, min(12, $Cantidad));
}

function SgceCrearTablaPlaneacionesSiNoExiste($Pdo) {
    static $TablaPlaneacionesLista = false;
    if ($TablaPlaneacionesLista) { return; }
    $Pdo->exec("CREATE TABLE IF NOT EXISTS Planeaciones (
        Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        CicloId INT UNSIGNED NOT NULL,
        MaestroId INT UNSIGNED NOT NULL,
        MateriaNombre VARCHAR(140) NOT NULL,
        Numero INT UNSIGNED NOT NULL,
        VersionArchivo INT UNSIGNED NOT NULL DEFAULT 1,
        Titulo VARCHAR(180) DEFAULT NULL,
        ArchivoOriginal VARCHAR(255) NOT NULL,
        ArchivoGuardado VARCHAR(255) NOT NULL,
        MimeType VARCHAR(120) DEFAULT NULL,
        TamanoBytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
        Estado ENUM('SUBIDA','APROBADA','DEVUELTA') NOT NULL DEFAULT 'SUBIDA',
        NotaRevision TEXT DEFAULT NULL,
        RevisadoPor INT UNSIGNED DEFAULT NULL,
        FechaRevision DATETIME DEFAULT NULL,
        FechaSubida TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unico_planeacion_docente_materia_numero (CicloId, MaestroId, MateriaNombre, Numero),
        INDEX idx_planeaciones_maestro_ciclo (MaestroId, CicloId, MateriaNombre, Numero),
        INDEX idx_planeaciones_estado (Estado, FechaActualizacion),
        INDEX idx_planeaciones_numero (Numero),
        CONSTRAINT fk_planeaciones_ciclo FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_planeaciones_maestro FOREIGN KEY (MaestroId) REFERENCES Usuarios(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_planeaciones_revisor FOREIGN KEY (RevisadoPor) REFERENCES Usuarios(Id) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $TablaPlaneacionesLista = true;
}

function SgceNormalizarMateriaPlaneacion($Texto) {
    $Texto = trim(preg_replace('/\s+/u', ' ', (string)$Texto));
    return SgceCadenaMayusculas($Texto);
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

function SgceNombreArchivoSeguro($Texto) {
    $Texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$Texto);
    $Texto = preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', (string)$Texto);
    $Texto = trim($Texto, '._-');
    return $Texto !== '' ? $Texto : 'archivo';
}

function SgceEstadosPlaneacion() {
    return [
        'PENDIENTE' => 'PENDIENTE',
        'SUBIDA' => 'SUBIDA',
        'APROBADA' => 'APROBADA',
        'DEVUELTA' => 'DEVUELTA',
    ];
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
        if ($Finfo) { finfo_close($Finfo); }
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

function SgceMateriasDocente($Pdo, $MaestroId) {
    $Ciclo = SgceCicloActivo($Pdo);
    $CicloId = (int)($Ciclo['Id'] ?? 0);
    if ($CicloId <= 0) { return []; }
    $Stmt = $Pdo->prepare("SELECT A.MateriaNombre,
        GROUP_CONCAT(CONCAT(G.Grado, ' ', G.Grupo, ' - ', G.Turno) ORDER BY G.Turno, CAST(G.Grado AS UNSIGNED), G.Grado, G.Grupo SEPARATOR ', ') AS Grupos
        FROM Asignaciones A
        INNER JOIN Grupos G ON G.Id = A.GrupoId AND G.CicloId = A.CicloId
        WHERE A.MaestroId = ? AND A.CicloId = ? AND A.Activo = 1 AND G.Activo = 1
        GROUP BY A.MateriaNombre
        ORDER BY A.MateriaNombre ASC");
    $Stmt->execute([(int)$MaestroId, $CicloId]);
    return $Stmt->fetchAll(PDO::FETCH_ASSOC);
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

function SgceDbTablaExiste(PDO $Pdo, string $Tabla): bool {
    try {
        $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $Stmt->execute([$Tabla]);
        return (int)$Stmt->fetchColumn() > 0;
    } catch (Exception $E) { return false; }
}

function SgceDbColumnaExiste(PDO $Pdo, string $Tabla, string $Columna): bool {
    try {
        $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $Stmt->execute([$Tabla, $Columna]);
        return (int)$Stmt->fetchColumn() > 0;
    } catch (Exception $E) { return false; }
}

function SgceDbIndiceExiste(PDO $Pdo, string $Tabla, string $Indice): bool {
    try {
        $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
        $Stmt->execute([$Tabla, $Indice]);
        return (int)$Stmt->fetchColumn() > 0;
    } catch (Exception $E) { return false; }
}

function SgceDbFkExiste(PDO $Pdo, string $Tabla, string $Fk): bool {
    try {
        $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        $Stmt->execute([$Tabla, $Fk]);
        return (int)$Stmt->fetchColumn() > 0;
    } catch (Exception $E) { return false; }
}

function SgceDbExecSilencioso(PDO $Pdo, string $Sql): void {
    try { $Pdo->exec($Sql); } catch (Exception $E) {}
}

function SgceCicloPorId(PDO $Pdo, int $CicloId) {
    $Stmt = $Pdo->prepare('SELECT Id, Nombre, FechaInicio, FechaFin, Activo FROM CiclosEscolares WHERE Id = ? LIMIT 1');
    $Stmt->execute([$CicloId]);
    return $Stmt->fetch() ?: null;
}

function SgceCiclosListar(PDO $Pdo): array {
    return $Pdo->query('SELECT Id, Nombre, FechaInicio, FechaFin, Activo FROM CiclosEscolares ORDER BY FechaInicio DESC, Id DESC')->fetchAll();
}

function SgceCiclosInactivosConGrupos(PDO $Pdo): array {
    if (!SgceDbTablaExiste($Pdo, 'Grupos') || !SgceDbColumnaExiste($Pdo, 'Grupos', 'CicloId')) { return []; }
    return $Pdo->query("SELECT C.Id, C.Nombre, C.FechaInicio, C.FechaFin, COUNT(G.Id) AS TotalGrupos
        FROM CiclosEscolares C
        INNER JOIN Grupos G ON G.CicloId = C.Id
        WHERE C.Activo = 0
        GROUP BY C.Id, C.Nombre, C.FechaInicio, C.FechaFin
        ORDER BY C.FechaInicio DESC, C.Id DESC")->fetchAll();
}

function SgceGruposListarPorCiclo(PDO $Pdo, int $CicloId, bool $SoloActivos = true): array {
    if ($CicloId <= 0) { return []; }
    $WhereActivo = $SoloActivos ? ' AND G.Activo = 1' : '';
    $Stmt = $Pdo->prepare("SELECT G.Id, G.CicloId, G.Grado, G.Grupo, G.Turno, C.Nombre AS CicloNombre, C.Activo AS CicloActivo,
        (SELECT COUNT(*) FROM AlumnoInscripciones AI WHERE AI.GrupoId = G.Id AND AI.CicloId = G.CicloId AND AI.Estado IN ('INSCRITO','PROMOVIDO','EGRESADO')) AS TotalAlumnos
        FROM Grupos G
        INNER JOIN CiclosEscolares C ON C.Id = G.CicloId
        WHERE G.CicloId = ?{$WhereActivo}
        ORDER BY G.Turno, CAST(G.Grado AS UNSIGNED), G.Grado, G.Grupo, G.Id");
    $Stmt->execute([$CicloId]);
    return $Stmt->fetchAll();
}

function SgceGrupoObtenerPorId(PDO $Pdo, int $GrupoId) {
    $Stmt = $Pdo->prepare("SELECT G.Id, G.CicloId, G.Grado, G.Grupo, G.Turno, G.Activo, C.Nombre AS CicloNombre, C.Activo AS CicloActivo, C.FechaInicio, C.FechaFin
        FROM Grupos G
        INNER JOIN CiclosEscolares C ON C.Id = G.CicloId
        WHERE G.Id = ? LIMIT 1");
    $Stmt->execute([$GrupoId]);
    return $Stmt->fetch() ?: null;
}

function SgceGrupoObtenerPorCicloDatos(PDO $Pdo, int $CicloId, string $Grado, string $Grupo, string $Turno) {
    $Stmt = $Pdo->prepare('SELECT Id, CicloId, Grado, Grupo, Turno, Activo FROM Grupos WHERE CicloId = ? AND Grado = ? AND Grupo = ? AND Turno = ? LIMIT 1');
    $Stmt->execute([$CicloId, $Grado, $Grupo, $Turno]);
    return $Stmt->fetch() ?: null;
}

function SgceGrupoCrearOReactivar(PDO $Pdo, int $CicloId, string $Grado, string $Grupo, string $Turno): int {
    $Existente = SgceGrupoObtenerPorCicloDatos($Pdo, $CicloId, $Grado, $Grupo, $Turno);
    if ($Existente) {
        if ((int)$Existente['Activo'] !== 1) {
            $Stmt = $Pdo->prepare('UPDATE Grupos SET Activo = 1 WHERE Id = ?');
            $Stmt->execute([(int)$Existente['Id']]);
        }
        return (int)$Existente['Id'];
    }
    $Stmt = $Pdo->prepare('INSERT INTO Grupos (CicloId, Grado, Grupo, Turno, Activo) VALUES (?, ?, ?, ?, 1)');
    $Stmt->execute([$CicloId, $Grado, $Grupo, $Turno]);
    return (int)$Pdo->lastInsertId();
}

function SgceAlumnoTieneInscripcion(PDO $Pdo, int $AlumnoId, int $CicloId): bool {
    $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM AlumnoInscripciones WHERE AlumnoId = ? AND CicloId = ?');
    $Stmt->execute([$AlumnoId, $CicloId]);
    return (int)$Stmt->fetchColumn() > 0;
}

function SgceAlumnoInscribirEnCiclo(PDO $Pdo, int $AlumnoId, int $CicloId, int $GrupoId, string $Estado = 'INSCRITO'): bool {
    $Estado = in_array($Estado, ['INSCRITO','PROMOVIDO','EGRESADO','BAJA'], true) ? $Estado : 'INSCRITO';
    try {
        $Stmt = $Pdo->prepare('INSERT INTO AlumnoInscripciones (AlumnoId, CicloId, GrupoId, Estado) VALUES (?, ?, ?, ?)');
        $Stmt->execute([$AlumnoId, $CicloId, $GrupoId, $Estado]);
        return true;
    } catch (PDOException $E) {
        return false;
    }
}

function SgceAlumnosPorGrupoCiclo(PDO $Pdo, int $GrupoId, int $CicloId, array $Estados = ['INSCRITO']): array {
    if ($GrupoId <= 0 || $CicloId <= 0) { return []; }
    $EstadosPermitidos = ['INSCRITO','PROMOVIDO','EGRESADO','BAJA'];
    $Estados = array_values(array_intersect($Estados, $EstadosPermitidos));
    if (!$Estados) { $Estados = ['INSCRITO']; }
    $Place = implode(',', array_fill(0, count($Estados), '?'));
    $Stmt = $Pdo->prepare("SELECT A.Id, A.NombreCompleto, AI.GrupoId, G.Grado, G.Grupo, G.Turno, AI.CicloId, AI.Estado
        FROM AlumnoInscripciones AI
        INNER JOIN Alumnos A ON A.Id = AI.AlumnoId AND A.Activo = 1
        INNER JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId
        WHERE AI.GrupoId = ? AND AI.CicloId = ? AND AI.Estado IN ($Place)
        ORDER BY A.NombreCompleto, A.Id");
    $Stmt->execute(array_merge([$GrupoId, $CicloId], $Estados));
    return $Stmt->fetchAll();
}


function SgceMateriaIdPorNombre(PDO $Pdo, string $Nombre): int {
    $Nombre = SgceNormalizarMayusculas($Nombre);
    if ($Nombre === '') { return 0; }
    if (!SgceDbTablaExiste($Pdo, 'MateriasCatalogo')) { return 0; }
    $Stmt = $Pdo->prepare('SELECT Id FROM MateriasCatalogo WHERE Nombre = ? LIMIT 1');
    $Stmt->execute([$Nombre]);
    $Id = (int)$Stmt->fetchColumn();
    if ($Id > 0) {
        $Pdo->prepare('UPDATE MateriasCatalogo SET Activo = 1 WHERE Id = ?')->execute([$Id]);
        return $Id;
    }
    $Stmt = $Pdo->prepare('INSERT INTO MateriasCatalogo (Nombre, Activo) VALUES (?, 1)');
    $Stmt->execute([$Nombre]);
    return (int)$Pdo->lastInsertId();
}

function SgceAsignacionObtener(PDO $Pdo, int $AsignacionId) {
    $Stmt = $Pdo->prepare("SELECT A.Id, A.CicloId, A.MaestroId, A.GrupoId, A.MateriaId, A.MateriaNombre, A.Activo,
        G.Grado, G.Grupo, G.Turno, C.Nombre AS CicloNombre, C.Activo AS CicloActivo,
        U.NombreCompleto AS MaestroNombre
        FROM Asignaciones A
        INNER JOIN Grupos G ON G.Id = A.GrupoId AND G.CicloId = A.CicloId
        INNER JOIN CiclosEscolares C ON C.Id = A.CicloId
        INNER JOIN Usuarios U ON U.Id = A.MaestroId
        WHERE A.Id = ? LIMIT 1");
    $Stmt->execute([$AsignacionId]);
    return $Stmt->fetch() ?: null;
}

function SgceAsignacionTieneDatosAcademicos(PDO $Pdo, int $AsignacionId): bool {
    if ($AsignacionId <= 0) { return false; }
    $Total = 0;
    if (SgceDbTablaExiste($Pdo, 'Calificaciones')) {
        $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM Calificaciones WHERE AsignacionId = ?');
        $Stmt->execute([$AsignacionId]);
        $Total += (int)$Stmt->fetchColumn();
    }
    if (SgceDbTablaExiste($Pdo, 'Asistencias')) {
        $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM Asistencias WHERE AsignacionId = ?');
        $Stmt->execute([$AsignacionId]);
        $Total += (int)$Stmt->fetchColumn();
    }
    return $Total > 0;
}

function SgceDocenteAsignacionesActuales(PDO $Pdo, int $MaestroId): int {
    if ($MaestroId <= 0 || !SgceDbTablaExiste($Pdo, 'Asignaciones')) { return 0; }
    $Stmt = $Pdo->prepare("SELECT COUNT(*)
        FROM Asignaciones A
        INNER JOIN CiclosEscolares C ON C.Id = A.CicloId AND C.Activo = 1
        INNER JOIN Grupos G ON G.Id = A.GrupoId AND G.CicloId = A.CicloId AND G.Activo = 1
        WHERE A.MaestroId = ? AND A.Activo = 1");
    $Stmt->execute([$MaestroId]);
    return (int)$Stmt->fetchColumn();
}

function SgceAsignacionTieneHistorialActivo(PDO $Pdo, int $AsignacionId, int $MaestroId): bool {
    if (!SgceDbTablaExiste($Pdo, 'AsignacionDocenteHistorial')) { return false; }
    $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM AsignacionDocenteHistorial WHERE AsignacionId = ? AND MaestroId = ? AND FechaFin IS NULL');
    $Stmt->execute([$AsignacionId, $MaestroId]);
    return (int)$Stmt->fetchColumn() > 0;
}

function SgceRegistrarDocenteAsignacionActual(PDO $Pdo, int $AsignacionId, int $MaestroId, int $UsuarioId = 0, string $Tipo = 'TITULAR', string $Motivo = ''): void {
    if ($AsignacionId <= 0 || $MaestroId <= 0 || !SgceDbTablaExiste($Pdo, 'AsignacionDocenteHistorial')) { return; }
    $Tipo = in_array($Tipo, ['TITULAR','INTERINATO','RELEVO'], true) ? $Tipo : 'TITULAR';
    if (SgceAsignacionTieneHistorialActivo($Pdo, $AsignacionId, $MaestroId)) { return; }
    $StmtCerrar = $Pdo->prepare('UPDATE AsignacionDocenteHistorial SET FechaFin = NOW() WHERE AsignacionId = ? AND FechaFin IS NULL');
    $StmtCerrar->execute([$AsignacionId]);
    $Stmt = $Pdo->prepare('INSERT INTO AsignacionDocenteHistorial (AsignacionId, MaestroId, FechaInicio, TipoMovimiento, Motivo, RegistradoPor) VALUES (?, ?, NOW(), ?, ?, NULLIF(?,0))');
    $Stmt->execute([$AsignacionId, $MaestroId, $Tipo, $Motivo, $UsuarioId]);
}

function SgceRelevarDocenteAsignacion(PDO $Pdo, int $AsignacionId, int $NuevoMaestroId, int $UsuarioId = 0, string $Motivo = 'RELEVO DOCENTE / INTERINATO'): bool {
    $Asignacion = SgceAsignacionObtener($Pdo, $AsignacionId);
    if (!$Asignacion) { throw new RuntimeException('La asignación no existe.'); }
    if ((int)$Asignacion['CicloActivo'] !== 1 || (int)$Asignacion['Activo'] !== 1) { throw new RuntimeException('Solo puedes relevar docentes en asignaciones activas del ciclo activo.'); }
    if (!SgceMaestroExisteActivo($Pdo, $NuevoMaestroId)) { throw new RuntimeException('El nuevo docente debe estar activo.'); }
    $MaestroAnteriorId = (int)$Asignacion['MaestroId'];
    if ($MaestroAnteriorId === $NuevoMaestroId) {
        SgceRegistrarDocenteAsignacionActual($Pdo, $AsignacionId, $NuevoMaestroId, $UsuarioId, 'TITULAR', 'REGISTRO ACTUAL SIN CAMBIO');
        return false;
    }
    if (!SgceAsignacionTieneHistorialActivo($Pdo, $AsignacionId, $MaestroAnteriorId)) {
        $Pdo->prepare('INSERT INTO AsignacionDocenteHistorial (AsignacionId, MaestroId, FechaInicio, FechaFin, TipoMovimiento, Motivo, RegistradoPor) VALUES (?, ?, NULL, NOW(), ?, ?, NULLIF(?,0))')
            ->execute([$AsignacionId, $MaestroAnteriorId, 'TITULAR', 'DOCENTE RESPONSABLE ANTERIOR ANTES DEL RELEVO', $UsuarioId]);
    } else {
        $Pdo->prepare('UPDATE AsignacionDocenteHistorial SET FechaFin = NOW() WHERE AsignacionId = ? AND MaestroId = ? AND FechaFin IS NULL')
            ->execute([$AsignacionId, $MaestroAnteriorId]);
    }
    $Pdo->prepare('UPDATE Asignaciones SET MaestroId = ? WHERE Id = ?')->execute([$NuevoMaestroId, $AsignacionId]);
    $Pdo->prepare('INSERT INTO AsignacionDocenteHistorial (AsignacionId, MaestroId, FechaInicio, TipoMovimiento, Motivo, RegistradoPor) VALUES (?, ?, NOW(), ?, ?, NULLIF(?,0))')
        ->execute([$AsignacionId, $NuevoMaestroId, 'INTERINATO', $Motivo, $UsuarioId]);
    return true;
}

function SgceAsignacionSincronizarMateria(PDO $Pdo, int $AsignacionId, string $MateriaNombre): void {
    if ($AsignacionId <= 0 || !SgceDbColumnaExiste($Pdo, 'Asignaciones', 'MateriaId')) { return; }
    $MateriaId = SgceMateriaIdPorNombre($Pdo, $MateriaNombre);
    if ($MateriaId > 0) {
        $Stmt = $Pdo->prepare('UPDATE Asignaciones SET MateriaId = ? WHERE Id = ?');
        $Stmt->execute([$MateriaId, $AsignacionId]);
    }
}

function SgceKardexAlumnoExiste(PDO $Pdo, int $AlumnoId, int $CicloId): bool {
    if (!SgceDbTablaExiste($Pdo, 'KardexAlumno')) { return false; }
    $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM KardexAlumno WHERE AlumnoId = ? AND CicloId = ?');
    $Stmt->execute([$AlumnoId, $CicloId]);
    return (int)$Stmt->fetchColumn() > 0;
}

function SgceKardexCongelarAlumnoCiclo(PDO $Pdo, int $AlumnoId, int $CicloId, int $UsuarioId = 0, bool $Forzar = true): bool {
    if ($AlumnoId <= 0 || $CicloId <= 0 || !SgceDbTablaExiste($Pdo, 'KardexAlumno') || !SgceDbTablaExiste($Pdo, 'KardexDetalle')) { return false; }
    $StmtInfo = $Pdo->prepare("SELECT A.Id AS AlumnoId, A.NombreCompleto, AI.GrupoId, AI.Estado, C.Nombre AS CicloNombre,
            G.Grado, G.Grupo, G.Turno
        FROM AlumnoInscripciones AI
        INNER JOIN Alumnos A ON A.Id = AI.AlumnoId
        INNER JOIN CiclosEscolares C ON C.Id = AI.CicloId
        INNER JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId
        WHERE AI.AlumnoId = ? AND AI.CicloId = ? LIMIT 1");
    $StmtInfo->execute([$AlumnoId, $CicloId]);
    $Info = $StmtInfo->fetch();
    if (!$Info) { return false; }

    if (!$Forzar && SgceKardexAlumnoExiste($Pdo, $AlumnoId, $CicloId)) { return true; }

    $StmtDetalle = $Pdo->prepare("SELECT Asg.MateriaNombre, U.NombreCompleto AS MaestroNombre,
            MAX(CASE WHEN P.Orden = 1 THEN Cal.Calificacion END) AS P1,
            MAX(CASE WHEN P.Orden = 2 THEN Cal.Calificacion END) AS P2,
            MAX(CASE WHEN P.Orden = 3 THEN Cal.Calificacion END) AS P3,
            ROUND(AVG(CASE WHEN Cal.Calificacion IS NOT NULL THEN Cal.Calificacion END), 2) AS Promedio
        FROM Asignaciones Asg
        LEFT JOIN Usuarios U ON U.Id = Asg.MaestroId
        LEFT JOIN PeriodosEvaluacion P ON P.CicloId = Asg.CicloId AND P.Activo = 1 AND P.Orden BETWEEN 1 AND 3
        LEFT JOIN Calificaciones Cal ON Cal.AlumnoId = ? AND Cal.AsignacionId = Asg.Id AND Cal.PeriodoId = P.Id
        WHERE Asg.CicloId = ? AND Asg.GrupoId = ?
        GROUP BY Asg.Id, Asg.MateriaNombre, U.NombreCompleto
        HAVING P1 IS NOT NULL OR P2 IS NOT NULL OR P3 IS NOT NULL
        ORDER BY Asg.MateriaNombre ASC, Asg.Id ASC");
    $StmtDetalle->execute([$AlumnoId, $CicloId, (int)$Info['GrupoId']]);
    $Detalles = $StmtDetalle->fetchAll();

    $Suma = 0.0; $Cuenta = 0;
    foreach ($Detalles as $D) {
        foreach (['P1','P2','P3'] as $K) {
            if ($D[$K] !== null && $D[$K] !== '') { $Suma += (float)$D[$K]; $Cuenta++; }
        }
    }
    $PromedioFinal = $Cuenta > 0 ? round($Suma / $Cuenta, 2) : null;

    $TransaccionPropia = !$Pdo->inTransaction();
    if ($TransaccionPropia) { $Pdo->beginTransaction(); }
    try {
        $StmtId = $Pdo->prepare('SELECT Id FROM KardexAlumno WHERE AlumnoId = ? AND CicloId = ? LIMIT 1 FOR UPDATE');
        $StmtId->execute([$AlumnoId, $CicloId]);
        $KardexId = (int)$StmtId->fetchColumn();
        if ($KardexId > 0) {
            $Pdo->prepare('UPDATE KardexAlumno SET GrupoId = ?, CicloNombreSnapshot = ?, GradoSnapshot = ?, GrupoSnapshot = ?, TurnoSnapshot = ?, EstadoFinal = ?, PromedioFinal = ?, GeneradoPor = NULLIF(?,0), FechaGeneracion = CURRENT_TIMESTAMP WHERE Id = ?')
                ->execute([(int)$Info['GrupoId'], (string)$Info['CicloNombre'], (string)$Info['Grado'], (string)$Info['Grupo'], (string)$Info['Turno'], (string)$Info['Estado'], $PromedioFinal, $UsuarioId, $KardexId]);
            $Pdo->prepare('DELETE FROM KardexDetalle WHERE KardexId = ?')->execute([$KardexId]);
        } else {
            $Pdo->prepare('INSERT INTO KardexAlumno (AlumnoId, CicloId, GrupoId, CicloNombreSnapshot, GradoSnapshot, GrupoSnapshot, TurnoSnapshot, EstadoFinal, PromedioFinal, GeneradoPor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?,0))')
                ->execute([$AlumnoId, $CicloId, (int)$Info['GrupoId'], (string)$Info['CicloNombre'], (string)$Info['Grado'], (string)$Info['Grupo'], (string)$Info['Turno'], (string)$Info['Estado'], $PromedioFinal, $UsuarioId]);
            $KardexId = (int)$Pdo->lastInsertId();
        }
        $StmtInsDet = $Pdo->prepare('INSERT INTO KardexDetalle (KardexId, MateriaNombreSnapshot, MaestroNombreSnapshot, Parcial1, Parcial2, Parcial3, Promedio, Orden) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $Orden = 1;
        foreach ($Detalles as $D) {
            $StmtInsDet->execute([
                $KardexId,
                (string)$D['MateriaNombre'],
                (string)($D['MaestroNombre'] ?? ''),
                $D['P1'] !== null ? (float)$D['P1'] : null,
                $D['P2'] !== null ? (float)$D['P2'] : null,
                $D['P3'] !== null ? (float)$D['P3'] : null,
                $D['Promedio'] !== null ? (float)$D['Promedio'] : null,
                $Orden++
            ]);
        }
        if ($TransaccionPropia) { $Pdo->commit(); }
        return true;
    } catch (Throwable $E) {
        if ($TransaccionPropia && $Pdo->inTransaction()) { $Pdo->rollBack(); }
        throw $E;
    }
}

function SgceKardexCongelarCiclo(PDO $Pdo, int $CicloId, int $UsuarioId = 0, bool $Forzar = true): int {
    if ($CicloId <= 0) { return 0; }
    $Stmt = $Pdo->prepare("SELECT AlumnoId FROM AlumnoInscripciones WHERE CicloId = ? AND Estado IN ('INSCRITO','PROMOVIDO','EGRESADO') ORDER BY AlumnoId");
    $Stmt->execute([$CicloId]);
    $Total = 0;
    foreach ($Stmt->fetchAll(PDO::FETCH_COLUMN) as $AlumnoId) {
        if (SgceKardexCongelarAlumnoCiclo($Pdo, (int)$AlumnoId, $CicloId, $UsuarioId, $Forzar)) { $Total++; }
    }
    return $Total;
}

function SgceAsegurarEsquemaAcademico(PDO $Pdo): void {
    static $Listo = false;
    if ($Listo) { return; }
    $Listo = true;
    if (!SgceDbTablaExiste($Pdo, 'CiclosEscolares') || !SgceDbTablaExiste($Pdo, 'Grupos') || !SgceDbTablaExiste($Pdo, 'Alumnos')) { return; }

    $CicloId = (int)$Pdo->query('SELECT Id FROM CiclosEscolares WHERE Activo = 1 ORDER BY FechaInicio DESC, Id DESC LIMIT 1')->fetchColumn();
    if ($CicloId <= 0) {
        $Pdo->prepare('INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES (?, ?, ?, 1)')->execute(['CICLO INICIAL', date('Y-01-01'), date('Y-12-31')]);
        $CicloId = (int)$Pdo->lastInsertId();
    }

    if (!SgceDbColumnaExiste($Pdo, 'Grupos', 'CicloId')) {
        SgceDbExecSilencioso($Pdo, 'ALTER TABLE Grupos ADD COLUMN CicloId INT UNSIGNED NULL AFTER Id');
        $Stmt = $Pdo->prepare('UPDATE Grupos SET CicloId = ? WHERE CicloId IS NULL');
        $Stmt->execute([$CicloId]);
        SgceDbExecSilencioso($Pdo, 'ALTER TABLE Grupos MODIFY CicloId INT UNSIGNED NOT NULL');
    }
    if (SgceDbIndiceExiste($Pdo, 'Grupos', 'unico_grupo_turno')) { SgceDbExecSilencioso($Pdo, 'ALTER TABLE Grupos DROP INDEX unico_grupo_turno'); }
    if (!SgceDbIndiceExiste($Pdo, 'Grupos', 'unico_grupo_ciclo_turno')) { SgceDbExecSilencioso($Pdo, 'ALTER TABLE Grupos ADD UNIQUE KEY unico_grupo_ciclo_turno (CicloId, Grado, Grupo, Turno)'); }
    if (!SgceDbIndiceExiste($Pdo, 'Grupos', 'idx_grupos_ciclo')) { SgceDbExecSilencioso($Pdo, 'ALTER TABLE Grupos ADD INDEX idx_grupos_ciclo (CicloId, Activo)'); }
    if (!SgceDbFkExiste($Pdo, 'Grupos', 'fk_grupos_ciclo')) { SgceDbExecSilencioso($Pdo, 'ALTER TABLE Grupos ADD CONSTRAINT fk_grupos_ciclo FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE'); }

    if (!SgceDbTablaExiste($Pdo, 'AlumnoInscripciones')) {
        $Pdo->exec("CREATE TABLE AlumnoInscripciones (
            Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            AlumnoId INT UNSIGNED NOT NULL,
            CicloId INT UNSIGNED NOT NULL,
            GrupoId INT UNSIGNED NOT NULL,
            Estado ENUM('INSCRITO','PROMOVIDO','EGRESADO','BAJA') NOT NULL DEFAULT 'INSCRITO',
            FechaInscripcion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_inscripciones_alumno FOREIGN KEY (AlumnoId) REFERENCES Alumnos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_inscripciones_ciclo FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_inscripciones_grupo FOREIGN KEY (GrupoId) REFERENCES Grupos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
            UNIQUE KEY unico_alumno_ciclo (AlumnoId, CicloId),
            INDEX idx_inscripciones_ciclo_grupo_estado (CicloId, GrupoId, Estado, AlumnoId),
            INDEX idx_inscripciones_alumno_ciclo (AlumnoId, CicloId),
            INDEX idx_inscripciones_grupo_estado (GrupoId, Estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $Pdo->exec("INSERT IGNORE INTO AlumnoInscripciones (AlumnoId, CicloId, GrupoId, Estado)
        SELECT A.Id, G.CicloId, A.GrupoId, 'INSCRITO'
        FROM Alumnos A
        INNER JOIN Grupos G ON G.Id = A.GrupoId
        WHERE A.Activo = 1 AND A.GrupoId IS NOT NULL");

    if (SgceDbTablaExiste($Pdo, 'Asignaciones') && !SgceDbColumnaExiste($Pdo, 'Asignaciones', 'CicloId')) {
        SgceDbExecSilencioso($Pdo, 'ALTER TABLE Asignaciones ADD COLUMN CicloId INT UNSIGNED NULL AFTER Id');
        SgceDbExecSilencioso($Pdo, 'UPDATE Asignaciones A INNER JOIN Grupos G ON G.Id = A.GrupoId SET A.CicloId = G.CicloId WHERE A.CicloId IS NULL');
        SgceDbExecSilencioso($Pdo, 'UPDATE Asignaciones SET CicloId = ' . (int)$CicloId . ' WHERE CicloId IS NULL');
        SgceDbExecSilencioso($Pdo, 'ALTER TABLE Asignaciones MODIFY CicloId INT UNSIGNED NOT NULL');
    }
    if (SgceDbTablaExiste($Pdo, 'Asignaciones')) {
        if (!SgceDbIndiceExiste($Pdo, 'Asignaciones', 'idx_asignaciones_ciclo')) { SgceDbExecSilencioso($Pdo, 'ALTER TABLE Asignaciones ADD INDEX idx_asignaciones_ciclo (CicloId, Activo)'); }
        if (!SgceDbIndiceExiste($Pdo, 'Asignaciones', 'unica_materia_grupo_ciclo')) { SgceDbExecSilencioso($Pdo, 'ALTER TABLE Asignaciones ADD UNIQUE KEY unica_materia_grupo_ciclo (CicloId, GrupoId, MateriaNombre)'); }
        if (!SgceDbFkExiste($Pdo, 'Asignaciones', 'fk_asignaciones_ciclo')) { SgceDbExecSilencioso($Pdo, 'ALTER TABLE Asignaciones ADD CONSTRAINT fk_asignaciones_ciclo FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE'); }
    }

    if (SgceDbTablaExiste($Pdo, 'Asistencias') && !SgceDbColumnaExiste($Pdo, 'Asistencias', 'CicloId')) {
        SgceDbExecSilencioso($Pdo, 'ALTER TABLE Asistencias ADD COLUMN CicloId INT UNSIGNED NULL AFTER Id');
        SgceDbExecSilencioso($Pdo, 'UPDATE Asistencias Asi INNER JOIN Asignaciones A ON A.Id = Asi.AsignacionId SET Asi.CicloId = A.CicloId WHERE Asi.CicloId IS NULL');
        SgceDbExecSilencioso($Pdo, 'UPDATE Asistencias SET CicloId = ' . (int)$CicloId . ' WHERE CicloId IS NULL');
        SgceDbExecSilencioso($Pdo, 'ALTER TABLE Asistencias MODIFY CicloId INT UNSIGNED NOT NULL');
    }
    if (SgceDbTablaExiste($Pdo, 'Asistencias')) {
        if (!SgceDbIndiceExiste($Pdo, 'Asistencias', 'idx_asistencias_ciclo')) { SgceDbExecSilencioso($Pdo, 'ALTER TABLE Asistencias ADD INDEX idx_asistencias_ciclo (CicloId, FechaDia)'); }
        if (!SgceDbFkExiste($Pdo, 'Asistencias', 'fk_asistencias_ciclo')) { SgceDbExecSilencioso($Pdo, 'ALTER TABLE Asistencias ADD CONSTRAINT fk_asistencias_ciclo FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE'); }
    }


    // Catálogo de materias: la materia es estable; el docente solo es el responsable actual.
    if (!SgceDbTablaExiste($Pdo, 'MateriasCatalogo')) {
        $Pdo->exec("CREATE TABLE MateriasCatalogo (
            Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            Nombre VARCHAR(140) NOT NULL,
            Activo TINYINT(1) NOT NULL DEFAULT 1,
            FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unico_materia_nombre (Nombre),
            INDEX idx_materias_activo_nombre (Activo, Nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if (SgceDbTablaExiste($Pdo, 'Asignaciones') && !SgceDbColumnaExiste($Pdo, 'Asignaciones', 'MateriaId')) {
        SgceDbExecSilencioso($Pdo, 'ALTER TABLE Asignaciones ADD COLUMN MateriaId INT UNSIGNED NULL AFTER GrupoId');
    }
    if (SgceDbTablaExiste($Pdo, 'Asignaciones')) {
        $Materias = $Pdo->query("SELECT DISTINCT MateriaNombre FROM Asignaciones WHERE MateriaNombre IS NOT NULL AND TRIM(MateriaNombre) <> ''")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($Materias as $MateriaNombre) { SgceMateriaIdPorNombre($Pdo, (string)$MateriaNombre); }
        SgceDbExecSilencioso($Pdo, "UPDATE Asignaciones A INNER JOIN MateriasCatalogo M ON M.Nombre = A.MateriaNombre SET A.MateriaId = M.Id WHERE A.MateriaId IS NULL");
        if (!SgceDbIndiceExiste($Pdo, 'Asignaciones', 'idx_asignaciones_materia_id')) { SgceDbExecSilencioso($Pdo, 'ALTER TABLE Asignaciones ADD INDEX idx_asignaciones_materia_id (MateriaId)'); }
        if (!SgceDbFkExiste($Pdo, 'Asignaciones', 'fk_asignaciones_materia_catalogo')) { SgceDbExecSilencioso($Pdo, 'ALTER TABLE Asignaciones ADD CONSTRAINT fk_asignaciones_materia_catalogo FOREIGN KEY (MateriaId) REFERENCES MateriasCatalogo(Id) ON DELETE RESTRICT ON UPDATE CASCADE'); }
    }

    if (!SgceDbTablaExiste($Pdo, 'AsignacionDocenteHistorial')) {
        $Pdo->exec("CREATE TABLE AsignacionDocenteHistorial (
            Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            AsignacionId INT UNSIGNED NOT NULL,
            MaestroId INT UNSIGNED NOT NULL,
            FechaInicio DATETIME NULL,
            FechaFin DATETIME NULL,
            TipoMovimiento ENUM('TITULAR','INTERINATO','RELEVO') NOT NULL DEFAULT 'TITULAR',
            Motivo VARCHAR(255) DEFAULT NULL,
            RegistradoPor INT UNSIGNED DEFAULT NULL,
            FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_hist_asignacion FOREIGN KEY (AsignacionId) REFERENCES Asignaciones(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_hist_maestro FOREIGN KEY (MaestroId) REFERENCES Usuarios(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_hist_registrado_por FOREIGN KEY (RegistradoPor) REFERENCES Usuarios(Id) ON DELETE SET NULL ON UPDATE CASCADE,
            INDEX idx_hist_asignacion_fechas (AsignacionId, FechaInicio, FechaFin),
            INDEX idx_hist_maestro (MaestroId, FechaInicio, FechaFin),
            INDEX idx_hist_activo (AsignacionId, FechaFin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if (SgceDbTablaExiste($Pdo, 'AsignacionDocenteHistorial') && SgceDbTablaExiste($Pdo, 'Asignaciones')) {
        $StmtAsignacionesHist = $Pdo->query('SELECT Id, MaestroId FROM Asignaciones WHERE Activo = 1');
        foreach ($StmtAsignacionesHist->fetchAll() as $AsigHist) {
            SgceRegistrarDocenteAsignacionActual($Pdo, (int)$AsigHist['Id'], (int)$AsigHist['MaestroId'], 0, 'TITULAR', 'REGISTRO AUTOMÁTICO DE RESPONSABLE ACTUAL');
        }
    }

    if (!SgceDbTablaExiste($Pdo, 'KardexAlumno')) {
        $Pdo->exec("CREATE TABLE KardexAlumno (
            Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            AlumnoId INT UNSIGNED NOT NULL,
            CicloId INT UNSIGNED NOT NULL,
            GrupoId INT UNSIGNED NOT NULL,
            CicloNombreSnapshot VARCHAR(40) NOT NULL,
            GradoSnapshot VARCHAR(20) NOT NULL,
            GrupoSnapshot VARCHAR(10) NOT NULL,
            TurnoSnapshot VARCHAR(20) NOT NULL,
            EstadoFinal ENUM('INSCRITO','PROMOVIDO','EGRESADO','BAJA') NOT NULL DEFAULT 'INSCRITO',
            PromedioFinal DECIMAL(5,2) DEFAULT NULL,
            GeneradoPor INT UNSIGNED DEFAULT NULL,
            FechaGeneracion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_kardex_alumno FOREIGN KEY (AlumnoId) REFERENCES Alumnos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_kardex_ciclo FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_kardex_grupo FOREIGN KEY (GrupoId) REFERENCES Grupos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_kardex_generado_por FOREIGN KEY (GeneradoPor) REFERENCES Usuarios(Id) ON DELETE SET NULL ON UPDATE CASCADE,
            UNIQUE KEY unico_kardex_alumno_ciclo (AlumnoId, CicloId),
            INDEX idx_kardex_alumno_ciclo (AlumnoId, CicloId),
            INDEX idx_kardex_ciclo_grupo (CicloId, GrupoId)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if (!SgceDbTablaExiste($Pdo, 'KardexDetalle')) {
        $Pdo->exec("CREATE TABLE KardexDetalle (
            Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            KardexId BIGINT UNSIGNED NOT NULL,
            MateriaNombreSnapshot VARCHAR(140) NOT NULL,
            MaestroNombreSnapshot VARCHAR(140) DEFAULT NULL,
            Parcial1 DECIMAL(4,2) DEFAULT NULL,
            Parcial2 DECIMAL(4,2) DEFAULT NULL,
            Parcial3 DECIMAL(4,2) DEFAULT NULL,
            Promedio DECIMAL(5,2) DEFAULT NULL,
            Orden INT UNSIGNED NOT NULL DEFAULT 1,
            CONSTRAINT fk_kardex_detalle FOREIGN KEY (KardexId) REFERENCES KardexAlumno(Id) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_kardex_detalle_kardex_orden (KardexId, Orden)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

function SgceMigrarGrupoSiguienteCiclo(PDO $Pdo, int $GrupoOrigenId, int $CicloDestinoId, bool $CopiarAsignaciones = false): array {
    $Origen = SgceGrupoObtenerPorId($Pdo, $GrupoOrigenId);
    $DestinoCiclo = SgceCicloPorId($Pdo, $CicloDestinoId);
    if (!$Origen) { throw new RuntimeException('El grupo origen no existe.'); }
    if (!$DestinoCiclo || (int)$DestinoCiclo['Activo'] !== 1) { throw new RuntimeException('Debe existir un ciclo destino activo.'); }
    if ((int)$Origen['CicloId'] === $CicloDestinoId) { throw new RuntimeException('El grupo origen ya pertenece al ciclo activo.'); }
    if ((int)$Origen['CicloActivo'] === 1) { throw new RuntimeException('No se puede migrar un grupo de un ciclo que todavía está activo. Primero crea/activa el nuevo ciclo.'); }
    if (!SgceValidarGrado($Origen['Grado'])) { throw new RuntimeException('El grado del grupo origen no es numérico y no puede migrarse automáticamente.'); }

    $GradoOrigen = (int)$Origen['Grado'];
    if ($GradoOrigen <= 0) { throw new RuntimeException('El grado del grupo origen no es válido.'); }

    $Resultado = [
        'GrupoOrigen' => $Origen,
        'GrupoDestinoId' => null,
        'NuevoGrado' => null,
        'Promovidos' => 0,
        'Egresados' => 0,
        'Omitidos' => 0,
        'Conflictos' => 0,
        'AsignacionesCopiadas' => 0,
        'AsignacionesOmitidasDocente' => 0,
        'KardexCongelados' => 0,
        'GrupoCreado' => false,
    ];

    $Alumnos = SgceAlumnosPorGrupoCiclo($Pdo, $GrupoOrigenId, (int)$Origen['CicloId'], ['INSCRITO']);
    // El kardex se congela después de cambiar el estado final de la inscripción
    // para que quede como PROMOVIDO o EGRESADO y no como INSCRITO.

    if ($GradoOrigen >= 3) {
        $StmtEgresar = $Pdo->prepare("UPDATE AlumnoInscripciones SET Estado = 'EGRESADO' WHERE AlumnoId = ? AND CicloId = ? AND GrupoId = ?");
        $StmtAlumnoNull = $Pdo->prepare('UPDATE Alumnos SET GrupoId = NULL WHERE Id = ? AND GrupoId = ?');
        foreach ($Alumnos as $Alumno) {
            $StmtEgresar->execute([(int)$Alumno['Id'], (int)$Origen['CicloId'], $GrupoOrigenId]);
            if (SgceKardexCongelarAlumnoCiclo($Pdo, (int)$Alumno['Id'], (int)$Origen['CicloId'], 0, true)) { $Resultado['KardexCongelados']++; }
            $StmtAlumnoNull->execute([(int)$Alumno['Id'], $GrupoOrigenId]);
            $Resultado['Egresados']++;
        }
        return $Resultado;
    }

    $NuevoGrado = (string)($GradoOrigen + 1);
    $GrupoExistente = SgceGrupoObtenerPorCicloDatos($Pdo, $CicloDestinoId, $NuevoGrado, (string)$Origen['Grupo'], (string)$Origen['Turno']);
    $GrupoDestinoId = SgceGrupoCrearOReactivar($Pdo, $CicloDestinoId, $NuevoGrado, (string)$Origen['Grupo'], (string)$Origen['Turno']);
    $Resultado['GrupoDestinoId'] = $GrupoDestinoId;
    $Resultado['NuevoGrado'] = $NuevoGrado;
    $Resultado['GrupoCreado'] = !$GrupoExistente;

    $StmtPromoverOrigen = $Pdo->prepare("UPDATE AlumnoInscripciones SET Estado = 'PROMOVIDO' WHERE AlumnoId = ? AND CicloId = ? AND GrupoId = ?");
    $StmtActualizarAlumno = $Pdo->prepare('UPDATE Alumnos SET GrupoId = ?, Activo = 1 WHERE Id = ?');
    foreach ($Alumnos as $Alumno) {
        $AlumnoId = (int)$Alumno['Id'];
        if (SgceAlumnoTieneInscripcion($Pdo, $AlumnoId, $CicloDestinoId)) {
            $Resultado['Conflictos']++;
            continue;
        }
        if (SgceAlumnoInscribirEnCiclo($Pdo, $AlumnoId, $CicloDestinoId, $GrupoDestinoId, 'INSCRITO')) {
            $StmtPromoverOrigen->execute([$AlumnoId, (int)$Origen['CicloId'], $GrupoOrigenId]);
            if (SgceKardexCongelarAlumnoCiclo($Pdo, $AlumnoId, (int)$Origen['CicloId'], 0, true)) { $Resultado['KardexCongelados']++; }
            $StmtActualizarAlumno->execute([$GrupoDestinoId, $AlumnoId]);
            $Resultado['Promovidos']++;
        } else {
            $Resultado['Omitidos']++;
        }
    }

    if ($CopiarAsignaciones) {
        $StmtAsignaciones = $Pdo->prepare("SELECT A.MaestroId, A.MateriaNombre, U.Activo AS MaestroActivo
            FROM Asignaciones A
            INNER JOIN Usuarios U ON U.Id = A.MaestroId AND U.Rol = 'maestro'
            WHERE A.CicloId = ? AND A.GrupoId = ? AND A.Activo = 1");
        $StmtAsignaciones->execute([(int)$Origen['CicloId'], $GrupoOrigenId]);
        $StmtInsertAsignacion = $Pdo->prepare('INSERT IGNORE INTO Asignaciones (CicloId, MaestroId, GrupoId, MateriaId, MateriaNombre, Activo) VALUES (?, ?, ?, NULLIF(?,0), ?, 1)');
        foreach ($StmtAsignaciones->fetchAll() as $Asig) {
            if ((int)$Asig['MaestroActivo'] !== 1) {
                $Resultado['AsignacionesOmitidasDocente']++;
                continue;
            }
            $MateriaNombre = (string)$Asig['MateriaNombre'];
            $MateriaId = SgceMateriaIdPorNombre($Pdo, $MateriaNombre);
            $StmtInsertAsignacion->execute([$CicloDestinoId, (int)$Asig['MaestroId'], $GrupoDestinoId, $MateriaId, $MateriaNombre]);
            if ($StmtInsertAsignacion->rowCount() > 0) {
                $NuevaAsignacionId = (int)$Pdo->lastInsertId();
                SgceRegistrarDocenteAsignacionActual($Pdo, $NuevaAsignacionId, (int)$Asig['MaestroId'], 0, 'TITULAR', 'ASIGNACIÓN COPIADA AL NUEVO CICLO');
                $Resultado['AsignacionesCopiadas']++;
            }
        }
    }

    return $Resultado;
}

function SgceMigrarCicloCompleto(PDO $Pdo, int $CicloOrigenId, int $CicloDestinoId, bool $CopiarAsignaciones = false): array {
    $Origen = SgceCicloPorId($Pdo, $CicloOrigenId);
    $Destino = SgceCicloPorId($Pdo, $CicloDestinoId);
    if (!$Origen || (int)$Origen['Activo'] === 1) { throw new RuntimeException('Selecciona un ciclo origen cerrado/inactivo.'); }
    if (!$Destino || (int)$Destino['Activo'] !== 1) { throw new RuntimeException('Debe existir un ciclo destino activo.'); }
    if ($CicloOrigenId === $CicloDestinoId) { throw new RuntimeException('El ciclo origen y destino no pueden ser el mismo.'); }
    $Resumen = ['GruposProcesados' => 0, 'Promovidos' => 0, 'Egresados' => 0, 'Conflictos' => 0, 'Omitidos' => 0, 'AsignacionesCopiadas' => 0, 'AsignacionesOmitidasDocente' => 0, 'KardexCongelados' => 0, 'GruposCreados' => 0];
    foreach (SgceGruposListarPorCiclo($Pdo, $CicloOrigenId, true) as $Grupo) {
        $R = SgceMigrarGrupoSiguienteCiclo($Pdo, (int)$Grupo['Id'], $CicloDestinoId, $CopiarAsignaciones);
        $Resumen['GruposProcesados']++;
        $Resumen['Promovidos'] += (int)$R['Promovidos'];
        $Resumen['Egresados'] += (int)$R['Egresados'];
        $Resumen['Conflictos'] += (int)$R['Conflictos'];
        $Resumen['Omitidos'] += (int)$R['Omitidos'];
        $Resumen['AsignacionesCopiadas'] += (int)$R['AsignacionesCopiadas'];
        $Resumen['AsignacionesOmitidasDocente'] += (int)($R['AsignacionesOmitidasDocente'] ?? 0);
        $Resumen['KardexCongelados'] += (int)($R['KardexCongelados'] ?? 0);
        $Resumen['GruposCreados'] += !empty($R['GrupoCreado']) ? 1 : 0;
    }
    return $Resumen;
}
