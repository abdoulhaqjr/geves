<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Bandeaux (« heros ») de la plateforme, téléversables depuis
 * **Réglages › Bannières**.
 *
 * Deux images, et deux seulement, parce qu'elles répondent à deux questions
 * différentes :
 *  - **l'accueil** a un bandeau qui lui est propre : c'est la première image du
 *    site, elle ne dépend d'aucun événement ;
 *  - **les événements** ont chacun le leur (onglet « Identité visuelle » de la
 *    fiche), mais la plupart n'en téléversent pas. Celle-ci est le **repli
 *    commun** : elle habille tous ceux qui n'ont rien choisi, au lieu de les
 *    laisser sur un aplat dégradé. Un événement qui pose son propre bandeau
 *    garde le sien : le repli ne passe jamais devant un choix explicite.
 *
 * ⚠️ **Les clés n'ont pas de point**, contrairement à `site.facebook` et
 * consorts : la clé sert aussi de **nom de champ** du formulaire, et Laravel lit
 * un point comme un niveau de tableau (`hero.home` deviendrait
 * `['hero' => ['home' => …]]`), ce qui casse `hasFile()`. Même raison que pour
 * les clés de `Branding`.
 *
 * ⚠️ **Jamais d'image cassée** : un réglage qui pointe vers un fichier disparu
 * rend `null`, et l'appelant retombe sur son dégradé. Même garde que
 * `Branding::stored()`.
 *
 * ⚠️ Un bandeau n'a **pas d'historique** : on remplace le fichier précédent au
 * lieu d'empiler, sinon les anciens resteraient servis publiquement.
 */
class SiteBanners
{
    /** Bandeau de la page d'accueil. */
    public const HOME = 'hero_home_image';

    /** Bandeau par défaut des pages d'événement. */
    public const EVENT_DEFAULT = 'hero_event_default_image';

    /** Toutes les clés téléversables (la clé sert aussi de nom de champ). */
    public const KEYS = [self::HOME, self::EVENT_DEFAULT];

    /** Dossier de stockage, sur le disque public. */
    public const DIR = 'banners';

    /** Bandeau de la page d'accueil, ou null (dégradé de la charte). */
    public static function home(): ?string
    {
        return self::url(self::HOME);
    }

    /**
     * Bandeau par défaut d'une page d'événement, ou null.
     *
     * ⚠️ Rend une **URL**, pas un chemin : c'est ce que stocke
     * `settings.hero_image` d'un événement (cf. `EventArtwork`), et le repli
     * doit pouvoir s'y substituer sans conversion.
     */
    public static function eventDefault(): ?string
    {
        return self::url(self::EVENT_DEFAULT);
    }

    /** URL du fichier téléversé pour cette clé, ou null s'il n'existe plus. */
    public static function url(string $key): ?string
    {
        $path = Settings::get($key);

        return filled($path) && Storage::disk('public')->exists((string) $path)
            ? Storage::disk('public')->url((string) $path)
            : null;
    }
}
