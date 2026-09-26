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
    /**
     * @param  array<string, string|list<array{name?: string, src?: string}>>  $vars
     *                                                                                Valeurs des jetons {…}. `signatures` est la seule valeur
     *                                                                                structurée : elle alimente l'élément « signatures ».
     */
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
                'signatures' => self::renderSignatures($el, $x, $y, $vars),
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

    /**
     * Bloc de signatures (attestations de formation) : les signatures des
     * formateurs côte à côte, chacune au-dessus de son nom. Les signatures sont
     * figées à l'émission (`$vars['signatures']`), donc l'attestation ne change
     * pas si l'équipe évolue ensuite.
     *
     * @param  array<string, mixed>  $el
     * @param  array<string, mixed>  $vars
     */
    protected static function renderSignatures(array $el, int $x, int $y, array $vars): string
    {
        /** @var list<array{name?: string, src?: string}> $signatures */
        $signatures = array_values(array_filter(
            (array) ($vars['signatures'] ?? []),
            fn ($s) => is_array($s) && filled($s['src'] ?? null),
        ));

        if ($signatures === []) {
            return '';
        }

        $w = (int) ($el['w'] ?? 420);
        $h = (int) ($el['h'] ?? 80);
        $showNames = (bool) ($el['names'] ?? true);
        $cell = (int) floor($w / max(1, count($signatures)));

        $cells = '';
        foreach ($signatures as $s) {
            $cells .= sprintf(
                '<div style="display:inline-block;width:%dpx;vertical-align:top;text-align:center;">'
                .'<img src="%s" alt="" style="max-width:%dpx;max-height:%dpx;object-fit:contain;">'
                .'%s</div>',
                $cell,
                e((string) $s['src']),
                max(20, $cell - 16),
                $showNames ? max(20, $h - 22) : $h,
                $showNames ? sprintf(
                    '<div style="border-top:1px solid #1a2332;margin:2px 10px 0;padding-top:3px;font-size:%dpx;color:#1a2332;">%s</div>',
                    (int) ($el['size'] ?? 11),
                    e((string) ($s['name'] ?? ''))
                ) : ''
            );
        }

        return sprintf(
            '<div style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;white-space:nowrap;">%s</div>',
            $x, $y, $w, $h, $cells
        );
    }

    /** @param array<string, mixed> $vars */
    protected static function substitute(string $text, array $vars): string
    {
        // Jetons {key} (schéma attestation). Une valeur non scalaire (ex. le bloc
        // de signatures) n'a pas de rendu textuel : le jeton est vidé.
        $text = preg_replace_callback('/\{(\w+)\}/', function ($m) use ($vars) {
            $v = $vars[$m[1]] ?? '';

            return is_scalar($v) ? (string) $v : '';
        }, $text);

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
