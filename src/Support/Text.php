<?php
namespace Sgce\Support;

final class Text
{
    public static function upper($text): string
    {
        $text = (string)$text;
        if (function_exists('mb_strtoupper')) { return mb_strtoupper($text, 'UTF-8'); }
        $text = strtr($text, [
            'á'=>'Á','é'=>'É','í'=>'Í','ó'=>'Ó','ú'=>'Ú','ü'=>'Ü','ñ'=>'Ñ',
            'à'=>'À','è'=>'È','ì'=>'Ì','ò'=>'Ò','ù'=>'Ù','ä'=>'Ä','ë'=>'Ë','ï'=>'Ï','ö'=>'Ö'
        ]);
        return strtoupper($text);
    }

    public static function length($text): int
    {
        $text = (string)$text;
        return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    }

    public static function normalizeUpper($value): string
    {
        $value = trim((string)$value);
        $value = preg_replace('/\s+/u', ' ', $value);
        return self::upper($value);
    }

    public static function normalizeName($value): string
    {
        $value = self::normalizeUpper($value);
        return (string)preg_replace('/[^A-ZÁÉÍÓÚÜÑ\s]/u', '', $value);
    }

    public static function normalizeGroup($value): string
    {
        $value = str_replace(' ', '', self::normalizeUpper($value));
        return preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ._\-\/]{1,10}$/u', $value) ? $value : '';
    }

    public static function validateGrade($value): bool
    {
        $value = self::normalizeUpper($value);
        return $value !== '' && self::length($value) <= 40 && preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ .º°\-]+$/u', $value) === 1;
    }

    public static function normalizeAcademicStage($value): string
    {
        $value = self::normalizeUpper($value);
        $value = str_replace([' SEMESTRE', ' CUATRIMESTRE', ' AÑO'], [' SEMESTRE', ' CUATRIMESTRE', ' AÑO'], $value);
        return preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ .º°\-]{1,40}$/u', $value) ? $value : '';
    }

    public static function normalizeTurn($value): string
    {
        $value = self::normalizeUpper($value);
        return preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ ._\-\/]{1,40}$/u', $value) ? $value : '';
    }

    public static function normalizeHexColor($color, string $default = '#97051E'): string
    {
        $color = trim((string)$color);
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) { return $default; }
        return strtoupper($color);
    }

    public static function adjustColor($color, $percentage): string
    {
        $color = ltrim(self::normalizeHexColor($color), '#');
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $percentage = max(-100, min(100, (int)$percentage));
        $target = $percentage >= 0 ? 255 : 0;
        $factor = abs($percentage) / 100;
        $r = (int)round($r + ($target - $r) * $factor);
        $g = (int)round($g + ($target - $g) * $factor);
        $b = (int)round($b + ($target - $b) * $factor);
        return sprintf('#%02X%02X%02X', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }

    public static function colorRgb($color): array
    {
        $color = ltrim(self::normalizeHexColor($color), '#');
        return [hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2))];
    }

    public static function normalizePlanningSubject($text): string
    {
        $text = trim((string)preg_replace('/\s+/u', ' ', (string)$text));
        return self::upper($text);
    }

    public static function safeFilename($text): string
    {
        $text = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$text);
        $text = preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', (string)$text);
        $text = trim((string)$text, '._-');
        return $text !== '' ? $text : 'archivo';
    }

    public static function normalizeProgram($value): string
    {
        $value = self::normalizeUpper($value);
        return preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ .º°_\-\/]{1,160}$/u', $value) ? $value : '';
    }
}
