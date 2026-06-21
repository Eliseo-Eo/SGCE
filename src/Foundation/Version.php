<?php
namespace Sgce\Foundation;

final class Version
{
    public const CURRENT = '1.0.200';
    public const NAME = 'Cierre real de producción';

    public static function current(): string
    {
        return self::CURRENT;
    }

    public static function label(): string
    {
        return 'SGCE ' . self::CURRENT . ' - ' . self::NAME;
    }
}
