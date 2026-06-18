<?php
if (!defined('SGCE_INSTALLER')) { http_response_code(403); exit('Acceso directo no permitido.'); }

class InstalarMensajeUsuario extends Exception {}
class InstalarErrorSql extends Exception {}

function HInst($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }

function InstalarCadenaMayusculas($Texto) {
    $Texto = (string)$Texto;
    if (function_exists('mb_strtoupper')) { return mb_strtoupper($Texto, 'UTF-8'); }
    $Texto = strtr($Texto, ['á'=>'Á','é'=>'É','í'=>'Í','ó'=>'Ó','ú'=>'Ú','ü'=>'Ü','ñ'=>'Ñ']);
    return strtoupper($Texto);
}

function InstalarNormalizarMayusculas($Texto) {
    $Texto = trim(preg_replace('/\s+/u', ' ', (string)$Texto));
    return InstalarCadenaMayusculas($Texto);
}

function InstalarTurnosTextoSeguro($Texto) {
    $Turnos = [];
    foreach (preg_split('/[,;\n]+/u', (string)$Texto) as $Turno) {
        $Turno = InstalarNormalizarMayusculas($Turno);
        if ($Turno !== '' && preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ ._\-\/]{1,40}$/u', $Turno) && !in_array($Turno, $Turnos, true)) {
            $Turnos[] = $Turno;
        }
    }
    return implode(PHP_EOL, $Turnos ?: ['MATUTINO','VESPERTINO']);
}


function InstalarCsrfStorePath() {
    return dirname(__DIR__, 2) . '/storage/locks/installer_csrf_tokens.json';
}

function InstalarCsrfLimpiarTokens($Tokens, int $Ahora) {
    $Limpios = [];
    foreach ((array)$Tokens as $Hash => $Tiempo) {
        $Tiempo = (int)$Tiempo;
        if (is_string($Hash) && preg_match('/^[a-f0-9]{64}$/', $Hash) && $Tiempo > 0 && ($Ahora - $Tiempo) <= 7200) {
            $Limpios[$Hash] = $Tiempo;
        }
    }
    return $Limpios;
}

function InstalarCsrfLeerTokens() {
    $Ruta = InstalarCsrfStorePath();
    if (!is_file($Ruta)) { return []; }
    $Json = @file_get_contents($Ruta);
    if (!is_string($Json) || trim($Json) === '') { return []; }
    $Datos = json_decode($Json, true);
    return is_array($Datos) ? $Datos : [];
}

function InstalarCsrfGuardarToken($Token) {
    if (!is_string($Token) || $Token === '') { return; }
    $Ruta = InstalarCsrfStorePath();
    $Dir = dirname($Ruta);
    if (!is_dir($Dir)) { @mkdir($Dir, 0775, true); }
    if (!is_dir($Dir) || !is_writable($Dir)) { return; }

    $Ahora = time();
    $Tokens = InstalarCsrfLimpiarTokens(InstalarCsrfLeerTokens(), $Ahora);
    $Tokens[hash('sha256', $Token)] = $Ahora;
    @file_put_contents($Ruta, json_encode($Tokens, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function InstalarCsrfToken() {
    if (empty($_SESSION['InstalarCsrfToken']) || !is_string($_SESSION['InstalarCsrfToken'])) {
        $_SESSION['InstalarCsrfToken'] = bin2hex(random_bytes(32));
    }
    InstalarCsrfGuardarToken($_SESSION['InstalarCsrfToken']);
    return $_SESSION['InstalarCsrfToken'];
}

function InstalarCampoCsrf() {
    return '<input type="hidden" name="InstalarCsrfToken" value="' . HInst(InstalarCsrfToken()) . '">';
}

function InstalarValidarCsrf($Token) {
    if (!is_string($Token) || $Token === '') { return false; }
    if (isset($_SESSION['InstalarCsrfToken']) && is_string($_SESSION['InstalarCsrfToken']) && hash_equals($_SESSION['InstalarCsrfToken'], $Token)) {
        return true;
    }

    // Fallback controlado para instaladores en servidores donde PHP no conserva la sesión
    // durante el POST inicial. El token sigue siendo aleatorio, temporal y generado por el
    // propio formulario; solo evita falsos bloqueos por session.save_path/cookies en local o Plesk.
    $Hash = hash('sha256', $Token);
    $Ahora = time();
    $Tokens = InstalarCsrfLimpiarTokens(InstalarCsrfLeerTokens(), $Ahora);
    if (isset($Tokens[$Hash])) {
        $Tokens[$Hash] = $Ahora;
        $Ruta = InstalarCsrfStorePath();
        if (is_writable(dirname($Ruta))) {
            @file_put_contents($Ruta, json_encode($Tokens, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        }
        return true;
    }
    return false;
}

function InstalarModoDebug() {
    return getenv('SGCE_DEBUG_INSTALLER') === '1';
}
