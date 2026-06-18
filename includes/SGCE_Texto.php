<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

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
    $Valor = str_replace(' ', '', $Valor);
    return preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ._\-\/]{1,10}$/u', $Valor) ? $Valor : '';
}


function SgceValidarGrado($Valor) {
    // El campo Grado funciona como etiqueta académica configurable:
    // puede representar grados, semestres, cuatrimestres, módulos o niveles.
    $Valor = SgceNormalizarMayusculas($Valor);
    return $Valor !== '' && SgceLongitudTexto($Valor) <= 40 && preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ .º°\-]+$/u', $Valor) === 1;
}


function SgceNormalizarEtapaAcademica($Valor) {
    $Valor = SgceNormalizarMayusculas($Valor);
    $Valor = str_replace([' SEMESTRE', ' CUATRIMESTRE', ' AÑO'], [' SEMESTRE', ' CUATRIMESTRE', ' AÑO'], $Valor);
    return preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ .º°\-]{1,40}$/u', $Valor) ? $Valor : '';
}


function SgceNormalizarTurno($Valor) {
    $Valor = SgceNormalizarMayusculas($Valor);
    return preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ ._\-\/]{1,40}$/u', $Valor) ? $Valor : '';
}


function SgceNormalizarTextoUsuarios($Valor) {
    return SgceNormalizarMayusculas($Valor);
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


function SgceNormalizarMateriaPlaneacion($Texto) {
    $Texto = trim(preg_replace('/\s+/u', ' ', (string)$Texto));
    return SgceCadenaMayusculas($Texto);
}


function SgceNombreArchivoSeguro($Texto) {
    $Texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$Texto);
    $Texto = preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', (string)$Texto);
    $Texto = trim($Texto, '._-');
    return $Texto !== '' ? $Texto : 'archivo';
}


function SgceNormalizarPrograma($Valor): string {
    $Valor = SgceNormalizarMayusculas($Valor);
    return preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ .º°_\-\/]{1,160}$/u', $Valor) ? $Valor : '';
}

