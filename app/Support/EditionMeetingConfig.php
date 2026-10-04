<?php

namespace App\Support;

use App\Models\Edition;
use Illuminate\Support\Facades\Config;

/**
 * Surcharge, au runtime, la configuration de visioconférence à partir des réglages
 * PROPRES à une édition (`settings.meetings`) : fournisseur par défaut, identifiants
 * LiveKit / Jitsi, et qualité vidéo. Les champs laissés VIDES retombent sur la
 * configuration globale (réglages plateforme). Même esprit - et mêmes pièges - que
 * {@see EditionPaymentConfig} : à appeler AVANT de résoudre ou d'utiliser un
 * fournisseur pour une salle rattachée à une édition.
 */
class EditionMeetingConfig
{
    /** Clés d'identifiants par fournisseur (mêmes que la config globale). */
    public const LIVEKIT_KEYS = ['url', 'api_key', 'api_secret'];

    public const JITSI_KEYS = ['domain', 'app_id', 'app_secret', 'room_prefix'];

    /**
     * Qualités de capture proposées.
     *
     * ⚠️ C'est la résolution de CAPTURE, donc la couche simulcast la plus haute :
     * rien dans la salle ne peut être plus net qu'elle, quelle que soit la caméra.
     * Les couches basses (180p, 360p) restent publiées pour les petites vignettes
     * et les connexions faibles - c'est ce qui rend une couche haute abordable.
     *
     * ⚠️ Le coût retombe sur la LIAISON MONTANTE de celui qui diffuse : monter la
     * qualité d'un colloque dont les communicants sont sur une connexion modeste
     * dégraderait leur image au lieu de l'améliorer. D'où un réglage par édition,
     * et non une valeur unique pour toute la plateforme.
     */
    public const QUALITIES = ['sd', 'md', 'hd', 'fhd'];

    public const QUALITY_DEFAULT = 'md';

    /**
     * Réglages de visio propres à une édition, ou null.
     *
     * @return array<string, mixed>|null
     */
    public static function settings(?Edition $edition): ?array
    {
        $m = $edition?->settings['meetings'] ?? null;

        return is_array($m) ? $m : null;
    }

    /** L'édition définit-elle au moins un réglage de visio ? */
    public static function configures(?Edition $edition): bool
    {
        return filled(self::settings($edition));
    }

    /**
     * Qualité vidéo retenue pour cette édition, sinon celle de la plateforme.
     *
     * ⚠️ Une valeur inconnue retombe sur le défaut : un réglage saisi hier et
     * retiré du code demain ne doit pas rendre la salle muette d'image.
     */
    public static function quality(?Edition $edition): string
    {
        $propre = self::settings($edition)['quality'] ?? null;
        $globale = config('meetings.quality', self::QUALITY_DEFAULT);

        foreach ([$propre, $globale] as $valeur) {
            if (is_string($valeur) && in_array($valeur, self::QUALITIES, true)) {
                return $valeur;
            }
        }

        return self::QUALITY_DEFAULT;
    }

    public static function apply(?Edition $edition): void
    {
        $m = self::settings($edition);
        if (! $m) {
            return; // aucune config propre : on garde celle de la plateforme
        }

        if (filled($m['provider'] ?? null)) {
            Config::set('meetings.default', (string) $m['provider']);
        }

        foreach (self::LIVEKIT_KEYS as $k) {
            if (filled($m['livekit'][$k] ?? null)) {
                Config::set("meetings.livekit.$k", $m['livekit'][$k]);
            }
        }
        foreach (self::JITSI_KEYS as $k) {
            if (filled($m['jitsi'][$k] ?? null)) {
                Config::set("meetings.jitsi.$k", $m['jitsi'][$k]);
            }
        }
    }

    /**
     * Exécute $fn avec la configuration de l'édition appliquée, puis RESTAURE la
     * précédente.
     *
     * ⚠️ Indispensable dès qu'une même requête touche plusieurs éditions (une liste
     * de séances, un tableau de bord) : `apply()` n'écrase que les champs remplis,
     * donc sans restauration les identifiants d'une édition resteraient actifs pour
     * la suivante - et une salle s'ouvrirait sur le serveur d'un autre colloque.
     *
     * @template T
     *
     * @param  callable(): T  $fn
     * @return T
     */
    public static function using(?Edition $edition, callable $fn): mixed
    {
        if (! self::configures($edition)) {
            return $fn(); // rien à poser, rien à restaurer
        }

        $snapshot = [
            'meetings.default' => config('meetings.default'),
            'meetings.livekit' => config('meetings.livekit'),
            'meetings.jitsi' => config('meetings.jitsi'),
        ];

        try {
            self::apply($edition);

            return $fn();
        } finally {
            Config::set($snapshot);
        }
    }
}
