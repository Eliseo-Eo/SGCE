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
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
}

function EnviarHeadersSeguridad() {
    if (headers_sent()) { return; }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; frame-ancestors 'self'; form-action 'self'; base-uri 'self'");
    if (EsHttps()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

function HGlobal($Texto) {
    return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8');
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
        http_response_code(403);
        die('Solicitud inválida. Recarga la página e intenta nuevamente.');
    }
}

function CampoCsrf() {
    return '<input type="hidden" name="CsrfToken" value="' . HGlobal(ObtenerCsrfToken()) . '">';
}

function ImprimirCsrfScript() {
    $Token = HGlobal(ObtenerCsrfToken());
    echo "\n<script>document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('form[method]').forEach(function(Form){var Metodo=(Form.getAttribute('method')||'').toLowerCase();if(Metodo==='post'&&!Form.querySelector('input[name=\\\"CsrfToken\\\"]')){var Input=document.createElement('input');Input.type='hidden';Input.name='CsrfToken';Input.value='" . $Token . "';Form.appendChild(Input);}});});</script>\n";
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

function SgceNormalizarMayusculas($Valor) {
    $Valor = trim((string)$Valor);
    $Valor = preg_replace('/\s+/u', ' ', $Valor);
    return mb_strtoupper($Valor, 'UTF-8');
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
    return trim(preg_replace('/\s+/u', ' ', (string)$Valor));
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
        'ColorInstitucional' => '#7A0818',
        'SistemaNombre' => 'SGCE',
    ];
}

function SgceObtenerConfiguracion($Pdo) {
    $Config = SgceConfiguracionDefault();
    try {
        SgceCrearTablaConfiguracionSiNoExiste($Pdo);
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
    SgceCrearTablaConfiguracionSiNoExiste($Pdo);
    $Permitidas = array_keys(SgceConfiguracionDefault());
    $Stmt = $Pdo->prepare('INSERT INTO ConfiguracionSistema (Clave, Valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE Valor = VALUES(Valor), FechaActualizacion = CURRENT_TIMESTAMP');
    foreach ($Permitidas as $Clave) {
        if (array_key_exists($Clave, $Datos)) {
            $Stmt->execute([$Clave, (string)$Datos[$Clave]]);
        }
    }
}

function SgceNombreEscuela($Pdo) {
    $Config = SgceObtenerConfiguracion($Pdo);
    return trim((string)$Config['NombreEscuela']);
}

function SgceRolesSistema() {
    return [
        'admin' => 'ADMINISTRADOR',
        'maestro' => 'MAESTRO',
        'director' => 'DIRECTOR',
        'secretario' => 'SECRETARIO',
        'coordinador' => 'COORDINADOR',
        'prefecto' => 'PREFECTO',
    ];
}

function SgceValidarRolUsuario($Rol, $Roles = null) {
    $Roles = $Roles ?: SgceRolesSistema();
    return array_key_exists((string)$Rol, $Roles);
}

function SgceTieneRol($UserSession, $Roles) {
    return is_array($UserSession) && isset($UserSession['Rol']) && in_array($UserSession['Rol'], (array)$Roles, true);
}

function SgcePermisosPorRol() {
    return [
        'admin' => ['admin', 'usuarios', 'catalogos', 'periodos', 'avisos', 'reportes', 'respaldos', 'bitacora', 'configuracion', 'asistencia', 'asistencia_editar', 'asistencia_historica', 'calificaciones', 'importar'],
        'director' => ['periodos', 'avisos', 'reportes', 'respaldos', 'bitacora', 'asistencia', 'asistencia_editar', 'asistencia_historica'],
        'secretario' => ['catalogos', 'avisos', 'reportes'],
        'coordinador' => ['avisos', 'reportes', 'asistencia', 'asistencia_editar', 'asistencia_historica'],
        'prefecto' => ['reportes', 'asistencia', 'asistencia_editar', 'asistencia_historica'],
        'maestro' => ['docente', 'asistencia', 'calificaciones'],
    ];
}

function SgceTienePermiso($UserSession, $Permiso) {
    if (!is_array($UserSession) || empty($UserSession['Rol'])) { return false; }
    $Mapa = SgcePermisosPorRol();
    return in_array($Permiso, $Mapa[$UserSession['Rol']] ?? [], true);
}

function SgcePuedeGestionarUsuarios($UserSession) { return SgceTienePermiso($UserSession, 'usuarios'); }
function SgcePuedeGestionarAvisos($UserSession) { return SgceTienePermiso($UserSession, 'avisos'); }
function SgcePuedeAdministrarReportes($UserSession) { return SgceTienePermiso($UserSession, 'reportes') || SgceTieneRol($UserSession, ['admin']); }
function SgcePuedeAdministrarPeriodos($UserSession) { return SgceTienePermiso($UserSession, 'periodos'); }
function SgcePuedeRespaldos($UserSession) { return SgceTienePermiso($UserSession, 'respaldos'); }
function SgcePuedeBitacora($UserSession) { return SgceTienePermiso($UserSession, 'bitacora'); }
function SgcePuedeImportarCatalogos($UserSession) { return SgceTienePermiso($UserSession, 'importar') || SgceTieneRol($UserSession, ['admin']); }
function SgcePuedeConfigurarSistema($UserSession) { return SgceTienePermiso($UserSession, 'configuracion') || SgceTieneRol($UserSession, ['admin']); }
function SgcePuedeCorregirAsistenciaHistorica($UserSession) { return SgceTienePermiso($UserSession, 'asistencia_historica'); }

function SgceTabAdminPermitida($Tab) {
    $Permitidas = ['inicio', 'maestros', 'grupos', 'alumnos', 'expedientes', 'asignaciones', 'bitacora'];
    return in_array($Tab, $Permitidas, true) ? $Tab : 'inicio';
}

function SgceRedirectAdminTab($Tab) {
    header('Location: Admin.php?Tab=' . urlencode(SgceTabAdminPermitida($Tab)));
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
    return $Stmt->fetch() ?: false;
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
        CrearTablaBitacoraSiNoExiste($Pdo);
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
    return true;
}

function SgceGenerarBackupAutomatico($Pdo) {
    $Dir = defined('SGCE_BACKUP_DIR') ? SGCE_BACKUP_DIR : dirname(__DIR__) . '/storage/backups';
    if (!is_dir($Dir)) { @mkdir($Dir, 0755, true); }
    $Archivo = $Dir . '/AutoBackup_' . date('Ymd') . '.sql';
    if (is_file($Archivo)) { return; }
    SgceCrearRespaldoSql($Pdo, $Archivo, false);
    $Archivos = glob($Dir . '/AutoBackup_*.sql') ?: [];
    rsort($Archivos);
    foreach (array_slice($Archivos, 10) as $Viejo) { @unlink($Viejo); }
}


function SgceFirmaRespaldoValida($Sql) {
    return is_string($Sql) && preg_match('/SGCE_EXPORT_SIGNATURE=SGCE_PRODUCCION/', $Sql) === 1;
}

function SgceRutaRaiz() {
    return dirname(__DIR__);
}
