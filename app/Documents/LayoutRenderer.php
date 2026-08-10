<?php

namespace App\Documents;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Rendu déterministe d'une mise en page (badge / attestation) en HTML positionné
 * absolu, avec substitution des variables {…} et génération des QR. Le HTML texte
 * est échappé ; seuls les QR (SVG de confiance) sont injectés.
 */
class LayoutRenderer
{
    /** @param array<string, string> $vars */
    public static function render(array $layout, array $vars = []): string
    {
        $w = (int) $layout['width'];
        $h = (int) $layout['height'];
        $bg = $layout['bg'] ?? '#ffffff';

        $html = "<div class=\"ukc-doc-canvas\" style=\"position:relative;width:{$w}px;height:{$h}px;background:"
            . e($bg) . ";overflow:hidden;font-family:'Jost',system-ui,sans-serif;\">";

        foreach ((array) ($layout['elements'] ?? []) as $el) {
            $x = (int) ($el['x'] ?? 0);
            $y = (int) ($el['y'] ?? 0);

            $html .= match ($el['type'] ?? '') {
                'rect' => sprintf(
                    '<div style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;background:%s;"></div>',
                    $x, $y, (int) ($el['w'] ?? 100), (int) ($el['h'] ?? 30), e($el['color'] ?? '#223ea4')
                ),
                'text' => sprintf(
                    '<div style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;font-size:%dpx;font-weight:%d;color:%s;text-align:%s;line-height:1.2;overflow:hidden;">%s</div>',
                    $x, $y, (int) ($el['w'] ?? 100), (int) ($el['h'] ?? 30), (int) ($el['size'] ?? 16), (int) ($el['weight'] ?? 500),
                    e($el['color'] ?? '#1a2332'), e($el['align'] ?? 'left'), e(self::substitute((string) ($el['text'] ?? ''), $vars))
                ),
                'image' => ! empty($el['src']) ? sprintf(
                    '<img src="%s" alt="" style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;object-fit:contain;">',
                    e($el['src']), $x, $y, (int) ($el['w'] ?? 100), (int) ($el['h'] ?? 30)
                ) : '',
                'qr' => self::renderQr($el, $x, $y, $vars),
                default => '',
            };
        }

        return $html . '</div>';
    }

    protected static function renderQr(array $el, int $x, int $y, array $vars): string
    {
        $size = (int) ($el['size'] ?? 96);
        $value = self::substitute((string) ($el['value'] ?? ''), $vars);
        if ($value === '') {
            $value = $vars['verify_url'] ?? $vars['ref'] ?? '';
        }

        $svg = $value !== '' ? (string) QrCode::format('svg')->size($size)->margin(0)->generate($value) : '';

        return sprintf(
            '<div style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;">%s</div>',
            $x, $y, $size, $size, $svg
        );
    }

    protected static function substitute(string $text, array $vars): string
    {
        return preg_replace_callback('/\{(\w+)\}/', fn ($m) => (string) ($vars[$m[1]] ?? ''), $text);
    }
}
