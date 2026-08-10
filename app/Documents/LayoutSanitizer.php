<?php

namespace App\Documents;

/**
 * Validation déterministe d'une mise en page (badge / attestation). Quelle que
 * soit la sortie de l'IA, on borne les coordonnées, on liste blanche les types
 * d'éléments et leurs propriétés, et on retombe sur une mise en page par défaut
 * si le résultat est vide/invalide. Le rendu (LayoutRenderer) reste ainsi sûr.
 */
class LayoutSanitizer
{
    public const KINDS = ['badge', 'attestation'];

    public const ELEMENT_TYPES = ['text', 'image', 'qr', 'rect'];

    /** @return array<string, mixed> */
    public static function sanitize(array $raw, string $kind, string $type = 'default'): array
    {
        $default = self::default($kind, $type);

        $w = self::clampInt($raw['width'] ?? null, 120, 2000, $default['width']);
        $h = self::clampInt($raw['height'] ?? null, 120, 2000, $default['height']);
        $bg = self::color($raw['bg'] ?? null, $default['bg']);
        $accent = self::color($raw['accent'] ?? null, $default['accent']);

        $elements = [];
        foreach ((array) ($raw['elements'] ?? []) as $el) {
            $t = is_array($el) ? ($el['type'] ?? '') : '';
            if (! in_array($t, self::ELEMENT_TYPES, true)) {
                continue;
            }

            $e = [
                'type' => $t,
                'x' => self::clampInt($el['x'] ?? 0, 0, $w, 0),
                'y' => self::clampInt($el['y'] ?? 0, 0, $h, 0),
            ];

            if ($t === 'qr') {
                $e['size'] = self::clampInt($el['size'] ?? 96, 24, 400, 96);
                $e['value'] = mb_substr((string) ($el['value'] ?? ''), 0, 200);
            } else {
                $e['w'] = self::clampInt($el['w'] ?? 100, 4, $w, 100);
                $e['h'] = self::clampInt($el['h'] ?? 30, 4, $h, 30);
            }

            if ($t === 'text') {
                $e['text'] = mb_substr((string) ($el['text'] ?? ''), 0, 500);
                $e['size'] = self::clampInt($el['size'] ?? 16, 6, 140, 16);
                $e['weight'] = self::clampInt($el['weight'] ?? 500, 300, 900, 500);
                $e['color'] = self::color($el['color'] ?? null, '#1a2332');
                $align = $el['align'] ?? 'left';
                $e['align'] = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';
            } elseif ($t === 'rect') {
                $e['color'] = self::color($el['color'] ?? null, $accent);
            } elseif ($t === 'image') {
                $e['src'] = filter_var($el['src'] ?? '', FILTER_VALIDATE_URL) ?: '';
            }

            $elements[] = $e;
        }

        if ($elements === []) {
            $elements = $default['elements'];
        }

        return ['width' => $w, 'height' => $h, 'bg' => $bg, 'accent' => $accent, 'elements' => $elements];
    }

    /** Mise en page par défaut fonctionnelle (utilisée si aucun modèle enregistré). */
    public static function default(string $kind, string $type = 'default'): array
    {
        return $kind === 'badge' ? self::defaultBadge() : self::defaultAttestation();
    }

    protected static function defaultBadge(): array
    {
        return [
            'width' => 320, 'height' => 440, 'bg' => '#ffffff', 'accent' => '#223ea4',
            'elements' => [
                ['type' => 'rect', 'x' => 0, 'y' => 0, 'w' => 320, 'h' => 12, 'color' => '#223ea4'],
                ['type' => 'text', 'x' => 20, 'y' => 34, 'w' => 280, 'h' => 20, 'text' => '{org}', 'size' => 12, 'weight' => 700, 'color' => '#5a6577', 'align' => 'center'],
                ['type' => 'text', 'x' => 20, 'y' => 58, 'w' => 280, 'h' => 40, 'text' => '{event}', 'size' => 14, 'weight' => 800, 'color' => '#223ea4', 'align' => 'center'],
                ['type' => 'text', 'x' => 20, 'y' => 210, 'w' => 280, 'h' => 40, 'text' => '{name}', 'size' => 22, 'weight' => 800, 'color' => '#1a2332', 'align' => 'center'],
                ['type' => 'text', 'x' => 20, 'y' => 252, 'w' => 280, 'h' => 24, 'text' => '{role}', 'size' => 13, 'weight' => 600, 'color' => '#5a6577', 'align' => 'center'],
                ['type' => 'qr', 'x' => 128, 'y' => 320, 'size' => 64, 'value' => '{ref}'],
                ['type' => 'text', 'x' => 20, 'y' => 392, 'w' => 280, 'h' => 20, 'text' => '{ref}', 'size' => 11, 'weight' => 700, 'color' => '#8b95a8', 'align' => 'center'],
            ],
        ];
    }

    protected static function defaultAttestation(): array
    {
        return [
            'width' => 900, 'height' => 636, 'bg' => '#ffffff', 'accent' => '#223ea4',
            'elements' => [
                ['type' => 'rect', 'x' => 0, 'y' => 0, 'w' => 900, 'h' => 14, 'color' => '#223ea4'],
                ['type' => 'text', 'x' => 60, 'y' => 60, 'w' => 780, 'h' => 26, 'text' => '{org}', 'size' => 16, 'weight' => 800, 'color' => '#223ea4', 'align' => 'center'],
                ['type' => 'text', 'x' => 60, 'y' => 150, 'w' => 780, 'h' => 50, 'text' => 'Attestation de {type}', 'size' => 34, 'weight' => 900, 'color' => '#1a2332', 'align' => 'center'],
                ['type' => 'text', 'x' => 60, 'y' => 240, 'w' => 780, 'h' => 24, 'text' => 'Décernée à', 'size' => 16, 'weight' => 500, 'color' => '#5a6577', 'align' => 'center'],
                ['type' => 'text', 'x' => 60, 'y' => 274, 'w' => 780, 'h' => 44, 'text' => '{name}', 'size' => 28, 'weight' => 800, 'color' => '#223ea4', 'align' => 'center'],
                ['type' => 'text', 'x' => 120, 'y' => 350, 'w' => 660, 'h' => 60, 'text' => 'pour sa {type} à {event}, {location}.', 'size' => 16, 'weight' => 500, 'color' => '#1a2332', 'align' => 'center'],
                ['type' => 'text', 'x' => 60, 'y' => 560, 'w' => 400, 'h' => 22, 'text' => 'Délivrée le {date}', 'size' => 13, 'weight' => 500, 'color' => '#5a6577', 'align' => 'left'],
                ['type' => 'qr', 'x' => 760, 'y' => 500, 'size' => 90, 'value' => '{verify_url}'],
            ],
        ];
    }

    protected static function clampInt(mixed $v, int $min, int $max, int $default): int
    {
        if (! is_numeric($v)) {
            return $default;
        }

        return (int) max($min, min($max, (int) $v));
    }

    protected static function color(mixed $v, string $default): string
    {
        $v = is_string($v) ? trim($v) : '';

        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $v) ? $v : $default;
    }
}
