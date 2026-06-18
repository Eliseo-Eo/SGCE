<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceValorBooleano($Valor): bool {
    if (is_bool($Valor)) { return $Valor; }
    return in_array(strtolower(trim((string)$Valor)), ['1','true','on','yes','si','sí'], true);
}

function SgceCookiePath(): string {
    $Url = defined('SGCE_BASE_URL') ? trim((string)SGCE_BASE_URL) : '';
    if ($Url !== '') {
        $Partes = @parse_url($Url);
        $Path = is_array($Partes) ? (string)($Partes['path'] ?? '') : '';
        $Path = '/' . trim($Path, '/');
        return $Path === '/' ? '/' : $Path . '/';
    }
    $Script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/'));
    $Dir = dirname($Script);
    if ($Dir === '.' || $Dir === '\\') { return '/'; }
    $Dir = '/' . trim($Dir, '/');
    return $Dir === '/' ? '/' : $Dir . '/';
}

function IniciarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => SgceCookiePath(),
                'secure' => EsHttps(),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }
        session_start();
    }
}

function SgceIpEnRangoConfiable(string $Ip, string $Entrada): bool {
    $Entrada = trim($Entrada);
    if ($Entrada === '') { return false; }
    if ($Ip === $Entrada) { return true; }
    if (!str_contains($Entrada, '/')) { return false; }
    [$Red, $Bits] = array_pad(explode('/', $Entrada, 2), 2, '');
    if (!filter_var($Ip, FILTER_VALIDATE_IP) || !filter_var($Red, FILTER_VALIDATE_IP)) { return false; }
    $Bits = (int)$Bits;
    $IpBin = @inet_pton($Ip);
    $RedBin = @inet_pton($Red);
    if ($IpBin === false || $RedBin === false || strlen($IpBin) !== strlen($RedBin)) { return false; }
    $Bytes = intdiv($Bits, 8);
    $Resto = $Bits % 8;
    if ($Bytes > 0 && substr($IpBin, 0, $Bytes) !== substr($RedBin, 0, $Bytes)) { return false; }
    if ($Resto === 0) { return true; }
    if ($Bytes >= strlen($IpBin)) { return true; }
    $Mascara = (0xFF << (8 - $Resto)) & 0xFF;
    return (ord($IpBin[$Bytes]) & $Mascara) === (ord($RedBin[$Bytes]) & $Mascara);
}

function SgceProxyConfiable(): bool {
    if (!defined('SGCE_TRUST_PROXY_HEADERS') || !SgceValorBooleano(SGCE_TRUST_PROXY_HEADERS)) { return false; }
    $Remoto = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($Remoto === '' || !defined('SGCE_TRUSTED_PROXIES')) { return false; }
    $Lista = array_filter(array_map('trim', preg_split('/[,;\s]+/', (string)SGCE_TRUSTED_PROXIES)));
    if (!$Lista) { return false; }
    foreach ($Lista as $ProxyConfiable) {
        if (SgceIpEnRangoConfiable($Remoto, $ProxyConfiable)) { return true; }
    }
    return false;
}

function EsHttps() {
    if (defined('SGCE_FORCE_HTTPS') && SgceValorBooleano(SGCE_FORCE_HTTPS)) { return true; }
    $HttpsDirecto = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
    if ($HttpsDirecto) { return true; }
    if (!SgceProxyConfiable()) { return false; }
    $ProtoProxy = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    $SslProxy = strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
    $PuertoProxy = (int)($_SERVER['HTTP_X_FORWARDED_PORT'] ?? 0);
    return $ProtoProxy === 'https' || $SslProxy === 'on' || $PuertoProxy === 443;
}

function SgceUrlHttpsActual(): string {
    $RequestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $Base = defined('SGCE_BASE_URL') ? trim((string)SGCE_BASE_URL) : '';
    if ($Base !== '') {
        $Partes = @parse_url($Base);
        if (is_array($Partes) && !empty($Partes['host'])) {
            $Host = $Partes['host'];
            $Puerto = isset($Partes['port']) ? ':' . (int)$Partes['port'] : '';
            return 'https://' . $Host . $Puerto . ($RequestUri !== '' ? $RequestUri : '/');
        }
    }
    $Host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($Host === '') { $Host = 'localhost'; }
    return 'https://' . $Host . ($RequestUri !== '' ? $RequestUri : '/');
}

function SgceForzarHttpsRedirect(): void {
    if (php_sapi_name() === 'cli' || headers_sent()) { return; }
    if (!defined('SGCE_FORCE_HTTPS') || !SgceValorBooleano(SGCE_FORCE_HTTPS)) { return; }
    $HttpsDirecto = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
    $HttpsProxy = false;
    if (SgceProxyConfiable()) {
        $ProtoProxy = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        $SslProxy = strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
        $PuertoProxy = (int)($_SERVER['HTTP_X_FORWARDED_PORT'] ?? 0);
        $HttpsProxy = $ProtoProxy === 'https' || $SslProxy === 'on' || $PuertoProxy === 443;
    }
    if ($HttpsDirecto || $HttpsProxy) { return; }
    http_response_code(308);
    header('Location: ' . SgceUrlHttpsActual(), true, 308);
    exit;
}

function EnviarHeadersSeguridad() {
    if (headers_sent()) { return; }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; script-src 'self' https://cdn.jsdelivr.net; font-src 'self' data: https://cdnjs.cloudflare.com https://fonts.gstatic.com; frame-ancestors 'self'; form-action 'self'; base-uri 'self'");
    if (EsHttps()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
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

function SgceNormalizarTokenSesion($Token): string {
    $Token = strtolower(trim((string)$Token));
    return preg_match('/^[a-f0-9]{64}$/', $Token) ? $Token : '';
}

function SgceHashTokenSesion($Token): string {
    $Token = SgceNormalizarTokenSesion($Token);
    return $Token !== '' ? hash('sha256', $Token) : '';
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
            'respaldos', 'bitacora', 'configuracion', 'migracion', 'asistencia', 'asistencia_editar',
            'asistencia_historica', 'calificaciones', 'importar', 'planeaciones', 'conducta'
        ],
        'administrativo' => [
            'admin.panel', 'admin.dashboard', 'catalogos', 'avisos', 'reportes',
            'asistencia', 'asistencia_editar', 'asistencia_historica', 'calificaciones', 'importar', 'planeaciones', 'conducta'
        ],
        'maestro' => ['docente', 'asistencia', 'calificaciones', 'planeaciones', 'conducta'],
    ];
}

function SgceTienePermiso($UserSession, $Permiso) {
    $Rol = SgceRolSesion($UserSession);
    if ($Rol === '') { return false; }
    $Mapa = SgcePermisosPorRol();
    return in_array($Permiso, $Mapa[$Rol] ?? [], true);
}

function SgceSeguridadAssetUrl(string $Ruta): string {
    $Version = defined('SGCE_VERSION') ? (string)SGCE_VERSION : '1.0.185';
    $Separador = str_contains($Ruta, '?') ? '&' : '?';
    return $Ruta . $Separador . 'v=' . rawurlencode($Version);
}

function SgceDenegarAcceso($Mensaje = 'No tienes permiso para entrar a esta sección.') {
    http_response_code(403);
    $MensajeSeguro = HGlobal($Mensaje);
    $Inicio = 'index.php';
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Acceso denegado | SGCE</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link rel="stylesheet" href="' . HGlobal(SgceSeguridadAssetUrl('assets/css/sgce-base.min.css')) . '">
<link rel="stylesheet" href="' . HGlobal(SgceSeguridadAssetUrl('assets/css/sgce-soft-motion.css')) . '"></head><body><main class="container py-5"><section class="card card-custom p-5 text-center mx-auto SgceAccessDeniedCard"><div class="display-5 text-danger mb-3"><i class="fa-solid fa-lock"></i></div><h1 class="fw-black mb-2">Acceso denegado</h1><p class="text-muted fw-semibold mb-4">' . $MensajeSeguro . '</p><a class="SgceBtnVolverInicio mx-auto" href="' . $Inicio . '"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a></section></main></body></html>';
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

function SgcePuedeMigrarCicloEscolar($UserSession) { return SgceTienePermiso($UserSession, 'migracion') && SgceTieneRol($UserSession, ['admin']); }

function SgcePuedeGestionarPlaneaciones($UserSession) { return SgceTienePermiso($UserSession, 'planeaciones') && !SgceTieneRol($UserSession, ['maestro']); }

function SgcePuedeGestionarConducta($UserSession) { return SgceTienePermiso($UserSession, 'conducta') && !SgceTieneRol($UserSession, ['maestro']); }

function SgcePuedeCorregirAsistenciaHistorica($UserSession) { return SgceTienePermiso($UserSession, 'asistencia_historica'); }

function SgcePuedePanelAdmin($UserSession) { return SgceTienePermiso($UserSession, 'admin.panel'); }

function SgceUrlInicioPorRol($UserSession) {
    $Rol = SgceRolSesion($UserSession);
    if ($Rol === 'maestro') { return 'Maestro.php'; }
    if (in_array($Rol, ['admin', 'administrativo'], true)) { return 'Admin.php?Tab=inicio'; }
    return 'index.php';
}

function SgceTabsAdminPermitidas($UserSession = null) {
    $Tabs = ['inicio', 'maestros', 'grupos', 'alumnos', 'materias', 'asignaciones', 'expedientes'];
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

function SgceRegenerarSesionRevalidadaPorCookie(int $UsuarioId, string $TokenHash): void {
    if (session_status() !== PHP_SESSION_ACTIVE || headers_sent()) { return; }
    $MarcaSesion = hash('sha256', $UsuarioId . '|' . $TokenHash);
    if (($_SESSION['SgceCookieAuthMarca'] ?? '') !== $MarcaSesion) {
        session_regenerate_id(true);
        $_SESSION['SgceCookieAuthMarca'] = $MarcaSesion;
    }
    $_SESSION['UsuarioId'] = $UsuarioId;
}

function VerificarSesionCookie($Pdo) {
    if (empty($_COOKIE['AuthToken'])) { return false; }
    $Token = SgceNormalizarTokenSesion($_COOKIE['AuthToken']);
    if ($Token === '') { return false; }

    $TokenHash = SgceHashTokenSesion($Token);
    if ($TokenHash === '') { return false; }

    $Stmt = $Pdo->prepare('SELECT Id, Username, NombreCompleto, Rol, SessionToken FROM Usuarios WHERE SessionToken = ? AND Activo = 1 AND SessionTokenExpira >= NOW() LIMIT 1');
    $Stmt->execute([$TokenHash]);
    $User = $Stmt->fetch() ?: false;

    if ($User) {
        SgceRegenerarSesionRevalidadaPorCookie((int)$User['Id'], $TokenHash);
        unset($User['SessionToken']);
        $User['Rol'] = SgceNormalizarRolSistema($User['Rol'] ?? '');
    }

    return $User;
}

function RateLimitClave($Contexto, $Identificador = '') {
    return hash('sha256', $Contexto . '|' . ObtenerIpCliente() . '|' . trim((string)$Identificador));
}

function RateLimitDisponible($Pdo, $Contexto, $Identificador = '') {
    try {
        $Stmt = $Pdo->prepare('SELECT BloqueadoHasta FROM IntentosSeguridad WHERE Contexto = ? AND ClaveHash = ? LIMIT 1');
        $Stmt->execute([$Contexto, RateLimitClave($Contexto, $Identificador)]);
        $Row = $Stmt->fetch();
        return !$Row || empty($Row['BloqueadoHasta']) || strtotime($Row['BloqueadoHasta']) <= time();
    } catch (Exception $E) { return true; }
}

function RateLimitRegistrarFallo($Pdo, $Contexto, $Identificador = '', $MaxIntentos = 5, $VentanaMinutos = 15) {
    try {
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
        $Stmt = $Pdo->prepare('DELETE FROM IntentosSeguridad WHERE Contexto = ? AND ClaveHash = ?');
        $Stmt->execute([$Contexto, RateLimitClave($Contexto, $Identificador)]);
    } catch (Exception $E) {}
}

