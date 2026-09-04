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
            .e($bg).";overflow:hidden;font-family:'Jost',system-ui,sans-serif;\">";

        foreach ((array) ($layout['elements'] ?? []) as $el) {
            $x = (int) ($el['x'] ?? 0);
            $y = (int) ($el['y'] ?? 0);

            $html .= match ($el['type'] ?? '') {
                'rect' => sprintf(
                    '<div style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;background:%s;"></div>',
                    $x, $y, (int) ($el['w'] ?? 100), (int) ($el['h'] ?? 30), e($el['color'] ?? '#223ea4')
                ),
                'text' => sprintf(
                    '<div style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;font-size:%dpx;font-weight:%d;color:%s;text-align:%s;line-height:1.2;overflow:hidden;white-space:pre-line;">%s</div>',
                    $x, $y, (int) ($el['w'] ?? 100), (int) ($el['h'] ?? 30), (int) ($el['size'] ?? 16), (int) ($el['weight'] ?? 500),
                    e($el['color'] ?? '#1a2332'), e($el['align'] ?? 'left'), e(self::substitute((string) ($el['text'] ?? ''), $vars))
                ),
                'image' => ! empty($el['src']) ? sprintf(
                    '<img src="%s" alt="" style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;object-fit:contain;">',
                    e($el['src']), $x, $y, (int) ($el['w'] ?? 100), (int) ($el['h'] ?? 30)
                ) : '',
                'photo' => self::renderPhoto($el, $x, $y, $vars),
                'qr' => self::renderQr($el, $x, $y, $vars),
                default => '',
            };
        }

        return $html.'</div>';
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

    /** Avatar rond (badge) : photo du bénéficiaire, sinon initiales. */
    protected static function renderPhoto(array $el, int $x, int $y, array $vars): string
    {
        $w = (int) ($el['w'] ?? 64);
        $h = (int) ($el['h'] ?? 64);
        $src = (string) ($el['src'] ?? ($vars['recipient.photo'] ?? ''));

        $inner = $src !== ''
            ? sprintf('<img src="%s" alt="" style="width:100%%;height:100%%;object-fit:cover;">', e($src))
            : sprintf('<span style="font-size:%dpx;font-weight:800;color:#94a3b8;">%s</span>', max(10, (int) ($w / 3)), e($vars['initials'] ?? ''));

        return sprintf(
            '<div style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;border-radius:50%%;overflow:hidden;background:#e2e8f0;border:3px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,.12);display:flex;align-items:center;justify-content:center;">%s</div>',
            $x, $y, $w, $h, $inner
        );
    }

    protected static function substitute(string $text, array $vars): string
    {
        // Jetons {key} (schéma attestation).
        $text = preg_replace_callback('/\{(\w+)\}/', fn ($m) => (string) ($vars[$m[1]] ?? ''), $text);

        // Jetons [namespace.key] (schéma de l'éditeur de badges), insensibles à la
        // casse ; si le jeton est en MAJUSCULES, la valeur l'est aussi (ex. rôle).
        return preg_replace_callback('/\[([A-Za-z]+\.[A-Za-z]+)\]/', function ($m) use ($vars) {
            $val = (string) ($vars[mb_strtolower($m[1])] ?? '');
            if ($val !== '' && $m[1] === mb_strtoupper($m[1])) {
                $val = mb_strtoupper($val);
            }

            return $val;
        }, $text);
    }
}
