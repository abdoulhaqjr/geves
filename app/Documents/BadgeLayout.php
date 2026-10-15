<?php

namespace App\Documents;

/** Choisit la maquette du badge selon la photo réellement disponible. */
class BadgeLayout
{
    /**
     * Une catégorie n'a qu'un modèle enregistré. Si ce modèle comporte un calque
     * photo mais que le bénéficiaire n'a pas de photo, utiliser la variante
     * officielle sans image plutôt qu'un cercle vide ou des initiales.
     *
     * @param  array<string, mixed>  $layout  Mise en page déjà sanitizée
     * @return array<string, mixed>
     */
    public static function forRecipient(array $layout, string $type, string $photo): array
    {
        if ($photo !== '' || ! self::hasPhoto($layout)) {
            return $layout;
        }

        $withoutPhoto = LayoutSanitizer::default('badge', $type);
        $width = (int) $layout['width'];
        $height = (int) $layout['height'];
        $baseWidth = (int) $withoutPhoto['width'];
        $baseHeight = (int) $withoutPhoto['height'];
        $scaleX = $width / $baseWidth;
        $scaleY = $height / $baseHeight;

        // Conserver le format choisi dans le designer, y compris A7 ou carte.
        if ($width !== $baseWidth || $height !== $baseHeight) {
            foreach ($withoutPhoto['elements'] as &$element) {
                $element['x'] = (int) round($element['x'] * $scaleX);
                $element['y'] = (int) round($element['y'] * $scaleY);
                if (isset($element['w'])) {
                    $element['w'] = (int) round($element['w'] * $scaleX);
                }
                if (isset($element['h'])) {
                    $element['h'] = (int) round($element['h'] * $scaleY);
                }
                if (isset($element['size']) && $element['type'] === 'qr') {
                    $element['size'] = (int) round($element['size'] * min($scaleX, $scaleY));
                }
                if (isset($element['size']) && $element['type'] === 'text') {
                    $element['size'] = (int) round($element['size'] * min($scaleX, $scaleY));
                }
            }
            unset($element);
            $withoutPhoto['width'] = $width;
            $withoutPhoto['height'] = $height;
        }

        return LayoutSanitizer::sanitize($withoutPhoto, 'badge', $type);
    }

    /** @param array<string, mixed> $layout */
    private static function hasPhoto(array $layout): bool
    {
        foreach ((array) ($layout['elements'] ?? []) as $element) {
            if (($element['type'] ?? '') === 'photo') {
                return true;
            }
            if (($element['type'] ?? '') === 'text') {
                $text = trim((string) ($element['text'] ?? ''));
                if (strcasecmp($text, 'Photo') === 0 || preg_match('/\[recipient\.photo\]/i', $text)) {
                    return true;
                }
            }
        }

        return false;
    }
}
