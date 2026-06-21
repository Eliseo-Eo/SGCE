<?php
namespace Sgce\Foundation;

final class Path
{
    public static function root(): string
    {
        return defined('SGCE_APP_ROOT') ? SGCE_APP_ROOT : dirname(__DIR__, 2);
    }

    public static function storage(string $relative = ''): string
    {
        $base = self::root() . DIRECTORY_SEPARATOR . 'storage';
        $relative = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative), DIRECTORY_SEPARATOR);
        return $relative === '' ? $base : $base . DIRECTORY_SEPARATOR . $relative;
    }

    public static function backups(): string
    {
        return self::storage('backups');
    }

    public static function logs(): string
    {
        return self::storage('logs');
    }

    public static function planeaciones(): string
    {
        return self::storage('planeaciones');
    }

    public static function normalize(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}
