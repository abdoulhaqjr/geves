<?php

namespace App\Documents;

use App\Models\Event;
use App\Support\EventSettings;

/** Réglages d'apparence indépendants pour chaque modèle du livre. */
final class LivretAppearance
{
    public const DEFAULTS = [
        'font_body' => 'Times New Roman',
        'font_head' => 'Jost',
        'size_body' => 11,
        'size_cover' => 24,
        'size_axis' => 16,
        'size_abstract' => 12,
        'size_authors' => 10,
        'line_height' => '1.15',
        'color_primary' => '#223ea4',
        'color_secondary' => '#009933',
        'align_titles' => 'Centré',
        'align_axis' => 'Centré',
        'align_authors' => 'Centré',
        'align_body' => 'Justifié',
        'cover_mention' => '',
        'cover_logo' => '',
        'show_theme' => true,
        'show_dates' => true,
        'number_abstracts' => false,
        'author_index' => true,
        'page_numbers' => true,
    ];

    public static function activeModel(?Event $event): string
    {
        return (string) EventSettings::get($event, 'livret.template', 'default') ?: 'default';
    }

    public static function get(?Event $event, string $key, mixed $default = null): mixed
    {
        $model = self::activeModel($event);
        $appearances = EventSettings::get($event, 'livret.appearances', []);
        if (is_array($appearances) && is_array($appearances[$model] ?? null) && array_key_exists($key, $appearances[$model])) {
            return $appearances[$model][$key];
        }

        // Avant la première séparation par modèle, les valeurs historiques
        // appartiennent au modèle alors actif. Les autres modèles repartent
        // des valeurs d'usine après le premier changement de modèle.
        if (! is_array($appearances) || $appearances === []) {
            return EventSettings::get($event, 'livret.'.$key, self::DEFAULTS[$key] ?? $default);
        }

        return self::DEFAULTS[$key] ?? $default;
    }

    /** Enregistre les valeurs sur le modèle actif seulement. */
    public static function save(Event $event, array $values): void
    {
        $appearances = EventSettings::get($event, 'livret.appearances', []);
        $appearances = is_array($appearances) ? $appearances : [];
        $model = self::activeModel($event);
        $appearances[$model] = array_replace(
            is_array($appearances[$model] ?? null) ? $appearances[$model] : ($appearances === [] ? self::legacy($event) : self::DEFAULTS),
            array_intersect_key($values, self::DEFAULTS),
        );
        EventSettings::set($event, 'livret.appearances', $appearances);
    }

    /** Capture les anciens réglages avant de changer le modèle actif. */
    public static function captureLegacy(Event $event): void
    {
        $appearances = EventSettings::get($event, 'livret.appearances', []);
        if (is_array($appearances) && $appearances !== []) {
            return;
        }

        EventSettings::set($event, 'livret.appearances', [self::activeModel($event) => self::legacy($event)]);
    }

    public static function forget(Event $event, string $model): void
    {
        $appearances = EventSettings::get($event, 'livret.appearances', []);
        if (! is_array($appearances) || ! array_key_exists($model, $appearances)) {
            return;
        }
        unset($appearances[$model]);
        if ($appearances === []) {
            $appearances[self::activeModel($event)] = self::DEFAULTS;
        }
        EventSettings::set($event, 'livret.appearances', $appearances);
    }

    private static function legacy(Event $event): array
    {
        $values = [];
        foreach (self::DEFAULTS as $key => $default) {
            $values[$key] = EventSettings::get($event, 'livret.'.$key, $default);
        }

        return $values;
    }
}
