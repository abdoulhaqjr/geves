<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Aide au traitement du HTML éditable (résumés WYSIWYG…). Un sous-ensemble très
 * restreint de balises est autorisé (mise en forme de base, sans attribut ni
 * script), suffisant pour un texte scientifique. Fournit aussi une conversion en
 * texte brut pour les consommateurs non-HTML (documents Word, exports).
 */
class Html
{
    /** Balises de mise en forme autorisées (sans attributs). */
    private const ALLOWED = '<p><br><strong><em><b><i><u><ul><ol><li>';

    /** Nettoie un fragment HTML : balises en liste blanche, sans attributs. */
    public static function clean(?string $html): string
    {
        $html = strip_tags((string) $html, self::ALLOWED);

        // Retire tout attribut des balises ouvrantes conservées (anti-XSS :
        // ni style, ni on*, ni href/src). Normalise <br/> en <br>.
        $html = preg_replace('#<([a-z0-9]+)(?:\s[^>]*)?/?>#i', '<$1>', $html) ?? '';

        return trim($html);
    }

    /** Convertit du HTML éditable en texte brut (sauts de ligne préservés). */
    public static function toText(?string $html): string
    {
        $html = (string) $html;
        $html = preg_replace('#<\s*br\s*/?>#i', "\n", $html) ?? $html;
        $html = preg_replace('#</\s*(p|li|div|h[1-6])\s*>#i', "\n", $html) ?? $html;
        $html = preg_replace('#<\s*li[^>]*>#i', '- ', $html) ?? $html;

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /** Longueur du texte visible (hors balises), pour la validation. */
    public static function textLength(?string $html): int
    {
        return Str::length(trim(preg_replace('/\s+/u', ' ', self::toText($html)) ?? ''));
    }

    /**
     * Rendu sûr pour affichage : HTML assaini si le contenu en contient, sinon
     * texte échappé avec sauts de ligne (compat des anciens résumés en texte).
     */
    public static function render(?string $value): HtmlString
    {
        $value = (string) $value;

        if ($value === strip_tags($value)) {
            return new HtmlString(nl2br(e($value)));
        }

        return new HtmlString(self::clean($value));
    }
}
