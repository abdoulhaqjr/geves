<?php

namespace App\Support;

/** Découpage commun aux imports, formulaires et affichages des mots-clés. */
final class SubmissionKeywords
{
    public const SEPARATORS = [
        'comma' => 'Virgule (,)',
        'semicolon' => 'Point-virgule (;)',
        'both' => 'Virgule et point-virgule',
    ];

    /** @return list<string> */
    public static function parse(string|array|null $value, string $separator = 'comma'): array
    {
        $pattern = match ($separator) {
            'semicolon' => '/[;\r\n]+/u',
            'both' => '/[,;\r\n]+/u',
            default => '/[,\r\n]+/u',
        };
        $raw = is_array($value) ? $value : preg_replace('/^\s*mots[\s-]*cl[ée]s\s*:\s*/iu', '', (string) $value);
        $parts = is_array($raw) ? $raw : (preg_split($pattern, (string) $raw) ?: []);

        return array_values(array_filter(array_map(
            fn ($part) => trim((string) $part),
            $parts,
        ), fn ($part) => $part !== ''));
    }

    public static function join(string|array|null $value, string $separator = 'comma'): ?string
    {
        $items = self::parse($value, $separator);

        return $items === [] ? null : implode($separator === 'semicolon' ? '; ' : ', ', $items);
    }
}
