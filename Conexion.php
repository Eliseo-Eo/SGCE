<?php

/*
    Archivo: Conexion.php
    Descripción: Configuración de conexión PDO con MySQL y funciones base de sesión.
    Las contraseñas se mantienen normales porque así se pidió para este proyecto.
*/

// Defino la zona horaria del sistema para fechas de asistencia y reportes.
date_default_timezone_set('America/Mexico_City');

$Host = 'localhost';
$Db = 'ControlEscolar';
$User = 'Eo';
$Pass = 'Eo94?';
$Charset = 'utf8mb4';

$Dsn = "mysql:host=$Host;dbname=$Db;charset=$Charset";

// Opciones de PDO para trabajar con errores controlados y resultados asociativos.
$Options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

// Intento realizar la conexión. Si falla, detengo el sistema con mensaje simple.
try {
    $Pdo = new PDO($Dsn, $User, $Pass, $Options);
} catch (PDOException $E) {
    die("Error De Conexión.");
}

// VERIFICAR SESIÓN
// Verifico si la cookie AuthToken pertenece a un usuario activo.
function VerificarSesionCookie($Pdo) {

    if (!isset($_COOKIE['AuthToken'])) {
        return false;
    }

    $Token = trim((string)$_COOKIE['AuthToken']);

    if ($Token === '' || !preg_match('/^[a-f0-9]{64}$/i', $Token)) {
        return false;
    }

    $Stmt = $Pdo->prepare("
        SELECT
            Id,
            Username,
            NombreCompleto,
            Rol
        FROM Usuarios
        WHERE SessionToken = ?
        AND Activo = 1
        LIMIT 1
    ");

    $Stmt->execute([$Token]);

    return $Stmt->fetch();
}

// Detecto si el sitio está trabajando bajo HTTPS para configurar cookies seguras.
function EsHttps() {
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );
}



// ============================================================
// SEGURIDAD GLOBAL: CSRF, RATE LIMIT, ENCABEZADOS Y HELPERS
// ============================================================

function IniciarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Inicio la sesión desde el principio para que el token CSRF exista antes de imprimir cualquier HTML.
IniciarSesionSegura();

function EnviarHeadersSeguridad() {
    if (headers_sent()) { return; }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

EnviarHeadersSeguridad();

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
    $Token = $_POST['CsrfToken'] ?? '';
    if (!ValidarCsrfToken($Token)) {
        http_response_code(403);
        die('Solicitud inválida. Recarga la página e intenta nuevamente.');
    }
}

function CampoCsrf() {
    return '<input type="hidden" name="CsrfToken" value="' . htmlspecialchars(ObtenerCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function ImprimirCsrfScript() {
    $Token = htmlspecialchars(ObtenerCsrfToken(), ENT_QUOTES, 'UTF-8');
    echo "\n<script>\n";
    echo "document.addEventListener('DOMContentLoaded',function(){";
    echo "document.querySelectorAll('form[method]').forEach(function(Form){";
    echo "var Metodo=(Form.getAttribute('method')||'').toLowerCase();";
    echo "if(Metodo==='post' && !Form.querySelector('input[name=\\\"CsrfToken\\\"]')){";
    echo "var Input=document.createElement('input');Input.type='hidden';Input.name='CsrfToken';Input.value='" . $Token . "';Form.appendChild(Input);";
    echo "}});";
    echo "});\n";
    echo "</script>\n";
}

function ObtenerIpCliente() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function CrearTablaRateLimitSiNoExiste($Pdo) {
    $Pdo->exec("\n        CREATE TABLE IF NOT EXISTS IntentosSeguridad (\n            Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n            ClaveHash CHAR(64) NOT NULL,\n            Contexto VARCHAR(40) NOT NULL,\n            Intentos INT UNSIGNED NOT NULL DEFAULT 0,\n            BloqueadoHasta DATETIME DEFAULT NULL,\n            UltimoIntento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            UNIQUE KEY unico_contexto_clave (Contexto, ClaveHash),\n            INDEX idx_intentos_bloqueado (Contexto, BloqueadoHasta),\n            INDEX idx_intentos_ultimo (UltimoIntento)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");
}

function RateLimitClave($Contexto, $Identificador = '') {
    $Base = $Contexto . '|' . ObtenerIpCliente() . '|' . trim((string)$Identificador);
    return hash('sha256', $Base);
}

function RateLimitDisponible($Pdo, $Contexto, $Identificador = '') {
    try {
        CrearTablaRateLimitSiNoExiste($Pdo);
        $ClaveHash = RateLimitClave($Contexto, $Identificador);
        $Stmt = $Pdo->prepare("SELECT BloqueadoHasta FROM IntentosSeguridad WHERE Contexto = ? AND ClaveHash = ? LIMIT 1");
        $Stmt->execute([$Contexto, $ClaveHash]);
        $Row = $Stmt->fetch();
        if (!$Row || empty($Row['BloqueadoHasta'])) { return true; }
        return strtotime($Row['BloqueadoHasta']) <= time();
    } catch (Exception $E) {
        return true;
    }
}

function RateLimitRegistrarFallo($Pdo, $Contexto, $Identificador = '', $MaxIntentos = 5, $VentanaMinutos = 15) {
    try {
        CrearTablaRateLimitSiNoExiste($Pdo);
        $ClaveHash = RateLimitClave($Contexto, $Identificador);
        $Ahora = date('Y-m-d H:i:s');
        $Stmt = $Pdo->prepare("SELECT Intentos, UltimoIntento FROM IntentosSeguridad WHERE Contexto = ? AND ClaveHash = ? LIMIT 1");
        $Stmt->execute([$Contexto, $ClaveHash]);
        $Row = $Stmt->fetch();

        $Intentos = 1;
        if ($Row && strtotime($Row['UltimoIntento']) >= time() - ($VentanaMinutos * 60)) {
            $Intentos = ((int)$Row['Intentos']) + 1;
        }

        $BloqueadoHasta = null;
        if ($Intentos >= $MaxIntentos) {
            $BloqueadoHasta = date('Y-m-d H:i:s', time() + ($VentanaMinutos * 60));
        }

        $Upsert = $Pdo->prepare("\n            INSERT INTO IntentosSeguridad (Contexto, ClaveHash, Intentos, BloqueadoHasta, UltimoIntento)\n            VALUES (?, ?, ?, ?, ?)\n            ON DUPLICATE KEY UPDATE\n                Intentos = VALUES(Intentos),\n                BloqueadoHasta = VALUES(BloqueadoHasta),\n                UltimoIntento = VALUES(UltimoIntento)\n        ");
        $Upsert->execute([$Contexto, $ClaveHash, $Intentos, $BloqueadoHasta, $Ahora]);
    } catch (Exception $E) {
        // No detengo el sistema por falla secundaria de rate limit.
    }
}

function RateLimitLimpiar($Pdo, $Contexto, $Identificador = '') {
    try {
        CrearTablaRateLimitSiNoExiste($Pdo);
        $ClaveHash = RateLimitClave($Contexto, $Identificador);
        $Stmt = $Pdo->prepare("DELETE FROM IntentosSeguridad WHERE Contexto = ? AND ClaveHash = ?");
        $Stmt->execute([$Contexto, $ClaveHash]);
    } catch (Exception $E) {
        // No detengo el sistema por falla secundaria.
    }
}

function HGlobal($Texto) {
    return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8');
}


// CREAR BITÁCORA SI NO EXISTE
// Esta función garantiza que la tabla de bitácora exista aunque el sistema se haya actualizado sobre una base anterior.
// La dejo sin llave foránea para evitar que una instalación vieja se rompa por diferencias de tipos entre tablas.
function CrearTablaBitacoraSiNoExiste($Pdo) {
    $Pdo->exec("\n        CREATE TABLE IF NOT EXISTS BitacoraMovimientos (\n            Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n            UsuarioId INT UNSIGNED DEFAULT NULL,\n            Rol VARCHAR(30) DEFAULT NULL,\n            Accion VARCHAR(80) NOT NULL,\n            TablaAfectada VARCHAR(80) DEFAULT NULL,\n            RegistroId BIGINT UNSIGNED DEFAULT NULL,\n            Detalle TEXT DEFAULT NULL,\n            Ip VARCHAR(45) DEFAULT NULL,\n            FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n            INDEX idx_bitacora_fecha (FechaRegistro),\n            INDEX idx_bitacora_usuario_fecha (UsuarioId, FechaRegistro),\n            INDEX idx_bitacora_accion_fecha (Accion, FechaRegistro),\n            INDEX idx_bitacora_tabla_registro (TablaAfectada, RegistroId)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");
}

// REGISTRAR BITÁCORA
// Esta función guarda movimientos importantes del sistema.
// Si algo falla, no detengo el sistema, pero intento crear la tabla primero para que sí registre en bases nuevas o actualizadas.
function RegistrarBitacora($Pdo, $UserSession, $Accion, $TablaAfectada = null, $RegistroId = null, $Detalle = null) {
    try {
        CrearTablaBitacoraSiNoExiste($Pdo);

        $UsuarioId = is_array($UserSession) && isset($UserSession['Id']) ? (int)$UserSession['Id'] : null;
        $Rol = is_array($UserSession) && isset($UserSession['Rol']) ? (string)$UserSession['Rol'] : null;
        $Ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $Stmt = $Pdo->prepare("\n            INSERT INTO BitacoraMovimientos\n            (UsuarioId, Rol, Accion, TablaAfectada, RegistroId, Detalle, Ip)\n            VALUES (?, ?, ?, ?, ?, ?, ?)\n        ");

        $Stmt->execute([
            $UsuarioId,
            $Rol,
            (string)$Accion,
            $TablaAfectada,
            $RegistroId,
            $Detalle,
            $Ip
        ]);
    } catch (Exception $E) {
        // No detengo el sistema por errores de bitácora.
        // Esto evita que el usuario pierda registros por una falla secundaria de auditoría.
    }
}


// Helpers comunes SGCE FIX30
require_once __DIR__ . '/includes/SGCE_Helpers.php';
try { SgceAsegurarCicloPeriodos($Pdo); } catch (Exception $E) { /* No detengo el sistema por migración automática. */ }
