<?php

namespace App\Documents;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use FontLib\Font;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Rendu déterministe d'une mise en page (badge / attestation) en HTML positionné
 * absolu, avec substitution des variables {…} et génération des QR. Le HTML texte
 * est échappé ; seuls les QR (SVG de confiance) sont injectés.
 */
class LayoutRenderer
{
    /**
     * Fichier de police dont dompdf tire ses métriques, par clé du concepteur.
     * ⚠️ Seules les polices **hébergées** (`public/fonts`) y figurent : les
     * autres retombent sur les polices natives de dompdf.
     *
     * @var array<string, string>
     */
    private const PDF_FONTS = [
        'jost' => 'jost-400.ttf',
        'playfair' => 'playfair-700.ttf',
    ];

    /**
     * @param  array<string, string|list<array{name?: string, src?: string}>>  $vars
     *                                                                                Valeurs des jetons {…}. `signatures` est la seule valeur
     *                                                                                structurée : elle alimente l'élément « signatures ».
     * @param  bool  $pdf  Rendu destiné à dompdf, qui **ne va chercher aucune image
     *                     distante** : les adresses servies par le site sont alors
     *                     résolues en chemins de fichier.
     */
    public static function render(array $layout, array $vars = [], bool $pdf = false): string
    {
        // ⚠️ Les alias `[espace.cle]` sont ajoutés ICI, et non chez les appelants :
        // chacun fournissait sa propre liste, et un modèle dessiné avec la palette
        // du concepteur sortait avec des jetons vides sur le document délivré.
        $vars = DocumentVars::expand($vars);

        $w = (int) $layout['width'];
        $h = (int) $layout['height'];
        $bg = $layout['bg'] ?? '#ffffff';

        $html = "<div class=\"ukc-doc-canvas\" style=\"position:relative;width:{$w}px;height:{$h}px;background:"
            .e($bg).";overflow:hidden;font-family:'Jost',system-ui,sans-serif;\">";

        // Fond illustré, sous tous les éléments.
        if (filled($layout['bg_image'] ?? '')) {
            $html .= sprintf(
                // Étiré sur la page, comme dans le concepteur. ⚠️ Pas d'`object-fit` :
                // dompdf l'ignore, et le PDF ne ressemblait plus à l'écran.
                '<img src="%s" alt="" style="position:absolute;left:0;top:0;width:%dpx;height:%dpx;">',
                e(self::imageSrc((string) $layout['bg_image'], $pdf)), $w, $h
            );
        }

        foreach ((array) ($layout['elements'] ?? []) as $el) {
            $x = (int) ($el['x'] ?? 0);
            $y = (int) ($el['y'] ?? 0);

            $html .= match ($el['type'] ?? '') {
                'rect' => self::renderRect($el, $x, $y),
                'text' => self::renderText($el, $x, $y, $vars, $pdf),
                'image' => self::renderImage($el, $x, $y, $pdf),
                'photo' => self::renderPhoto($el, $x, $y, $vars),
                'signatures' => self::renderSignatures($el, $x, $y, $vars),
                'qr' => self::renderQr($el, $x, $y, $vars, $pdf),
                default => '',
            };
        }

        return $html.'</div>';
    }

    /**
     * Hauteur de ligne propre à une police, telle que dompdf la calcule :
     * `(ascendante - descendante + interligne) / em`, multipliée par son
     * `font_height_ratio`. Vaut 1 pour les polices qu'il ne charge pas depuis
     * un fichier (elles retombent sur ses polices natives, de hauteur neutre).
     */
    protected static function fontHeightRatio(string $key): float
    {
        static $cache = [];

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $file = public_path('fonts/'.(self::PDF_FONTS[$key] ?? ''));
        if (! isset(self::PDF_FONTS[$key]) || ! is_file($file)) {
            return $cache[$key] = 1.0;
        }

        try {
            $font = Font::load($file);
            $font->parse();
            $em = (int) $font->getData('head', 'unitsPerEm');
            $height = (int) $font->getData('hhea', 'ascent')
                - (int) $font->getData('hhea', 'descent')
                + (int) $font->getData('hhea', 'lineGap');
        } catch (\Throwable) {
            return $cache[$key] = 1.0;
        }

        $ratio = (float) config('dompdf.options.font_height_ratio', 1.1);

        return $cache[$key] = $em > 0 && $height > 0 ? $height / $em * $ratio : 1.0;
    }

    /**
     * Image, à ses proportions dans sa boîte.
     *
     * ⚠️ **dompdf ignore `object-fit`** : le navigateur insérait l'image dans sa
     * boîte sans la déformer, dompdf l'étirait aux dimensions de la boîte - un
     * logo large dans une boîte plus carrée sortait écrasé du PDF téléchargé,
     * alors qu'il était juste à l'écran. Pour le PDF on calcule donc la boîte
     * nous-mêmes, à partir des dimensions réelles du fichier.
     */
    protected static function renderImage(array $el, int $x, int $y, bool $pdf): string
    {
        $src = (string) ($el['src'] ?? '');
        if ($src === '') {
            return '';
        }

        $w = (int) ($el['w'] ?? 100);
        $h = (int) ($el['h'] ?? 30);
        $resolved = self::imageSrc($src, $pdf);

        if ($pdf) {
            $size = @getimagesize($resolved);
            if ($size && $size[0] > 0 && $size[1] > 0) {
                $scale = min($w / $size[0], $h / $size[1]);
                $fitW = (int) round($size[0] * $scale);
                $fitH = (int) round($size[1] * $scale);
                // Centrée dans la boîte, comme le fait `object-fit: contain`.
                $x += (int) round(($w - $fitW) / 2);
                $y += (int) round(($h - $fitH) / 2);
                $w = $fitW;
                $h = $fitH;
            }

            return sprintf(
                '<img src="%s" alt="" style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;">',
                e($resolved), $x, $y, $w, $h
            );
        }

        return sprintf(
            '<img src="%s" alt="" style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;object-fit:contain;">',
            e($resolved), $x, $y, $w, $h
        );
    }

    /**
     * Adresse d'image utilisable par le moteur de rendu.
     *
     * ⚠️ **dompdf ne télécharge aucune image distante** (`enable_remote` est
     * faux, et l'activer ferait sortir le PDF du bac à sable). Une image du
     * concepteur, servie en `/storage/…`, sortait donc du PDF alors qu'elle
     * s'affichait bien sur la page. On lui passe le fichier.
     */
    protected static function imageSrc(string $src, bool $pdf): string
    {
        if (! $pdf || $src === '') {
            return $src;
        }

        $path = str_starts_with($src, '/') ? $src : (string) parse_url($src, PHP_URL_PATH);
        $file = public_path(ltrim($path, '/'));

        // Une image hébergée ailleurs reste telle quelle : dompdf la laissera
        // tomber, mais la remplacer par un chemin inexistant n'aiderait pas.
        return $path !== '' && is_file($file) ? $file : $src;
    }

    /**
     * Bloc coloré, avec son arrondi et son filet.
     *
     * ⚠️ Un cadre dessiné dans le concepteur (bordure seule, coins arrondis)
     * ressortait en aplat rectangulaire : ces deux propriétés manquaient.
     */
    protected static function renderRect(array $el, int $x, int $y): string
    {
        $border = (int) ($el['border'] ?? 0);

        return sprintf(
            '<div style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;background:%s;border-radius:%dpx;%s"></div>',
            $x, $y, (int) ($el['w'] ?? 100), (int) ($el['h'] ?? 30),
            e($el['color'] ?? '#223ea4'), (int) ($el['radius'] ?? 0),
            $border > 0 ? sprintf('border:%dpx solid %s;', $border, e($el['border_color'] ?? '#223ea4')) : ''
        );
    }

    /**
     * Texte, avec toute sa mise en forme.
     *
     * ⚠️ Police, italique, souligné, interlignage, interlettrage et capitales
     * étaient perdus : un titre dessiné en Playfair italique ressortait en Jost
     * droit, et le document délivré ne ressemblait plus à la maquette.
     */
    protected static function renderText(array $el, int $x, int $y, array $vars, bool $pdf = false): string
    {
        $font = LayoutSanitizer::FONTS[$el['font'] ?? 'jost'] ?? LayoutSanitizer::FONTS['jost'];
        $text = self::substitute((string) ($el['text'] ?? ''), $vars);

        $extra = '';
        if (! empty($el['italic'])) {
            $extra .= 'font-style:italic;';
        }
        if (! empty($el['underline'])) {
            $extra .= 'text-decoration:underline;';
        }
        if (! empty($el['upper'])) {
            // ⚠️ dompdf ne connaît pas `text-transform` : les capitales se
            // perdaient dans le PDF alors qu'elles s'affichaient à l'écran.
            if ($pdf) {
                $text = mb_strtoupper($text);
            } else {
                $extra .= 'text-transform:uppercase;';
            }
        }
        if (($el['spacing'] ?? 0) != 0) {
            $extra .= sprintf('letter-spacing:%.2fpx;', (float) $el['spacing']);
        }

        $size = (int) ($el['size'] ?? 16);
        $leading = (float) ($el['leading'] ?? 1.2);

        if ($pdf) {
            // ⚠️ **dompdf n'applique PAS `line-height` au corps du texte** : il le
            // multiplie par la hauteur propre de la police. Avec Jost (1,445 em,
            // × le `font_height_ratio` de 1,1) l'interligne sortait 59 % trop
            // grand, et un paragraphe de deux lignes perdait la seconde, coupée
            // par sa boîte. On compense pour retrouver le pas de l'écran.
            $lineHeight = sprintf('%.2fpx', $size * $leading / self::fontHeightRatio((string) ($el['font'] ?? 'jost')));
            // Et on ne rogne pas : mieux vaut un texte qui déborde qu'un texte
            // disparu sans un mot.
            $box = sprintf('left:%dpx;top:%dpx;width:%dpx;', $x, $y, (int) ($el['w'] ?? 100));
        } else {
            $lineHeight = sprintf('%.2f', $leading);
            $box = sprintf('left:%dpx;top:%dpx;width:%dpx;height:%dpx;overflow:hidden;', $x, $y, (int) ($el['w'] ?? 100), (int) ($el['h'] ?? 30));
        }

        return sprintf(
            '<div style="position:absolute;%sfont-family:%s;font-size:%dpx;'
            .'font-weight:%d;color:%s;text-align:%s;line-height:%s;white-space:pre-line;%s">%s</div>',
            $box, e($font), $size, (int) ($el['weight'] ?? 500),
            e($el['color'] ?? '#1a2332'), e($el['align'] ?? 'left'),
            $lineHeight, $extra, e($text)
        );
    }

    protected static function renderQr(array $el, int $x, int $y, array $vars, bool $pdf = false): string
    {
        $size = (int) ($el['size'] ?? 96);
        $value = self::substitute((string) ($el['value'] ?? ''), $vars);
        if ($value === '') {
            $value = $vars['verify_url'] ?? $vars['ref'] ?? '';
        }

        $inner = match (true) {
            $value === '' => '',
            $pdf => self::qrBoxes($value, $size),
            default => (string) QrCode::format('svg')->size($size)->margin(0)->generate($value),
        };

        return sprintf(
            '<div style="position:absolute;left:%dpx;top:%dpx;width:%dpx;height:%dpx;">%s</div>',
            $x, $y, $size, $size, $inner
        );
    }

    /**
     * QR dessiné en rectangles, pour le PDF.
     *
     * ⚠️ **dompdf ne rend pas le SVG** du QR - ni en ligne, ni par `<img>`, ni en
     * `data:` : le QR était tout simplement **absent du PDF téléchargé**, alors
     * qu'il s'affiche sur la page. Or c'est lui qui mène à la vérification : une
     * attestation imprimée sans QR n'est plus vérifiable. On dessine donc la
     * même matrice en blocs, que dompdf sait tracer.
     *
     * Les modules sombres d'une même ligne sont fusionnés en un seul bloc :
     * quelques centaines de rectangles au lieu de plusieurs milliers.
     */
    protected static function qrBoxes(string $value, int $size): string
    {
        try {
            $matrix = Encoder::encode($value, ErrorCorrectionLevel::L(), Encoder::DEFAULT_BYTE_MODE_ECODING)->getMatrix();
        } catch (\Throwable) {
            return '';
        }

        $cols = $matrix->getWidth();
        $rows = $matrix->getHeight();
        if ($cols < 1 || $rows < 1) {
            return '';
        }

        $unit = $size / $cols;
        $html = sprintf('<div style="position:absolute;left:0;top:0;width:%dpx;height:%dpx;background:#fff;"></div>', $size, $size);

        for ($row = 0; $row < $rows; $row++) {
            $start = null;
            for ($col = 0; $col <= $cols; $col++) {
                $dark = $col < $cols && $matrix->get($col, $row) === 1;

                if ($dark && $start === null) {
                    $start = $col;
                } elseif (! $dark && $start !== null) {
                    $html .= sprintf(
                        '<div style="position:absolute;left:%.2fpx;top:%.2fpx;width:%.2fpx;height:%.2fpx;background:#000;"></div>',
                        $start * $unit, $row * $unit, ($col - $start) * $unit, $unit
                    );
                    $start = null;
                }
            }
        }

        return $html;
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

        // Jetons [namespace.key] (schéma de l'éditeur), insensibles à la casse ; si
        // le jeton est en MAJUSCULES, la valeur l'est aussi (ex. rôle).
        // ⚠️ Les chiffres et le souligné font partie du nom : sans eux,
        // `[certificate.issued_on]` n'était pas reconnu et s'affichait tel quel
        // sur l'attestation délivrée.
        return preg_replace_callback('/\[([A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+)\]/', function ($m) use ($vars) {
            $val = (string) ($vars[mb_strtolower($m[1])] ?? '');
            if ($val !== '' && $m[1] === mb_strtoupper($m[1])) {
                $val = mb_strtoupper($val);
            }

            return $val;
        }, $text);
    }
}
