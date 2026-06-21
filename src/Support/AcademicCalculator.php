<?php
namespace Sgce\Support;

/**
 * Cálculos académicos puros para pruebas PHPUnit y migración progresiva.
 * No accede a base de datos ni modifica el flujo legacy existente.
 */
final class AcademicCalculator
{
    public static function normalizeScore($score, float $min = 5.0, float $max = 10.0): ?float
    {
        if ($score === null) { return null; }
        if (is_string($score)) {
            $score = trim($score);
            if ($score === '' || strtoupper($score) === 'NC') { return null; }
            $score = str_replace(',', '.', $score);
        }
        if (!is_numeric($score)) { return null; }
        $value = round((float)$score, 2);
        if ($value < $min || $value > $max) { return null; }
        return $value;
    }

    public static function average(array $scores, int $precision = 2, float $min = 5.0, float $max = 10.0): ?float
    {
        $valid = [];
        foreach ($scores as $score) {
            $normalized = self::normalizeScore($score, $min, $max);
            if ($normalized !== null) { $valid[] = $normalized; }
        }
        if (!$valid) { return null; }
        return round(array_sum($valid) / count($valid), max(0, min(4, $precision)));
    }

    public static function isPassing($score, float $passing = 6.0, float $min = 5.0, float $max = 10.0): bool
    {
        $normalized = self::normalizeScore($score, $min, $max);
        return $normalized !== null && $normalized >= $passing;
    }

    public static function finalStatus(array $scores, float $passing = 6.0, float $min = 5.0, float $max = 10.0): string
    {
        $average = self::average($scores, 2, $min, $max);
        if ($average === null) { return 'NC'; }
        return $average >= $passing ? 'APROBADO' : 'NO APROBADO';
    }
}
