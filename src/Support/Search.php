<?php
namespace Sgce\Support;

final class Search
{
    public static function normalize($text): string
    {
        $text = Text::normalizeUpper((string)$text);
        $text = strtr($text, [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
            'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U','ü'=>'U','ñ'=>'N'
        ]);
        $text = preg_replace('/[^A-Z0-9 ]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', trim((string)$text));
        return (string)$text;
    }

    public static function likePrefix($text): string
    {
        return self::normalize($text) . '%';
    }

    public static function booleanFullText($text): string
    {
        $text = self::normalize($text);
        $parts = preg_split('/\s+/', $text) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $part = preg_replace('/[^A-Z0-9]/', '', (string)$part);
            if ($part === '' || strlen($part) < 2) { continue; }
            $tokens[] = '+' . $part . '*';
        }
        return implode(' ', $tokens);
    }
}
