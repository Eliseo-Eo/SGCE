<?php
if (!defined('SGCE_INSTALLER')) { http_response_code(403); exit('Acceso directo no permitido.'); }

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
