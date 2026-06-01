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

function SgcePasswordNecesitaRehash($Hash) {
    return is_string($Hash) && password_needs_rehash($Hash, PASSWORD_DEFAULT);
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
    $Pdo->exec("CREATE TABLE IF NOT EXISTS ConfiguracionSistema (
        Clave VARCHAR(80) NOT NULL PRIMARY KEY,
        Valor TEXT NULL,
        FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_config_fecha (FechaActualizacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
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
        'PlaneacionesCantidad' => '1',
    ];
}

function SgceObtenerConfiguracion($Pdo) {
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
    return $Config;
}

function SgceGuardarConfiguracion($Pdo, $Datos) {
    if (!$Pdo->inTransaction()) { SgceCrearTablaConfiguracionSiNoExiste($Pdo); }
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

function SgceNombreEscuela($Pdo) {
    $Config = SgceObtenerConfiguracion($Pdo);
    return trim((string)$Config['NombreEscuela']);
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
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Acceso denegado | SGCE</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/sgce-base.min.css?cache=sgce2026final"></head><body><main class="container py-5"><section class="card card-custom p-5 text-center mx-auto" style="max-width:680px"><div class="display-5 text-danger mb-3"><i class="fa-solid fa-lock"></i></div><h1 class="fw-black mb-2">Acceso denegado</h1><p class="text-muted fw-semibold mb-4">' . $MensajeSeguro . '</p><a class="SgceBtnVolverInicio mx-auto" href="' . $Inicio . '"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a></section></main></body></html>';
    exit;
}

function SgceExigirPermiso($UserSession, $Permiso, $Mensaje = 'No tienes permiso para entrar a esta sección.') {
    if (!SgceTienePermiso($UserSession, $Permiso)) { SgceDenegarAcceso($Mensaje); }
}

function SgceExigirRol($UserSession, $Roles, $Mensaje = 'No tienes permiso para entrar a esta sección.') {
    if (!SgceTieneRol($UserSession, $Roles)) { SgceDenegarAcceso($Mensaje); }
}

function SgcePuedeGestionarUsuarios($UserSession) { return SgceTienePermiso($UserSession, 'usuarios'); }
function SgcePuedeGestionarCatalogos($UserSession) { return SgceTienePermiso($UserSession, 'catalogos'); }
function SgcePuedeGestionarAvisos($UserSession) { return SgceTienePermiso($UserSession, 'avisos'); }
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

function SgceCicloActivoId($Pdo) {
    $Stmt = $Pdo->query("SELECT Id FROM CiclosEscolares WHERE Activo = 1 ORDER BY FechaInicio DESC, Id DESC LIMIT 1");
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
        INDEX idx_bitacora_tabla_registro (TablaAfectada, RegistroId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
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
    $Stmt = $Pdo->prepare("SELECT A.MateriaNombre,
        GROUP_CONCAT(CONCAT(G.Grado, ' ', G.Grupo, ' - ', G.Turno) ORDER BY G.Turno, G.Grado, G.Grupo SEPARATOR ', ') AS Grupos
        FROM Asignaciones A
        INNER JOIN Grupos G ON G.Id = A.GrupoId
        WHERE A.MaestroId = ? AND A.Activo = 1 AND G.Activo = 1
        GROUP BY A.MateriaNombre
        ORDER BY A.MateriaNombre ASC");
    $Stmt->execute([(int)$MaestroId]);
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

function SgceRutaRaiz() {
    return dirname(__DIR__);
}
