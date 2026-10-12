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
 *  - **les autres pages publiques avec un héros** partagent un bandeau par
 *    défaut. Il sert aussi de repli aux événements sans visuel propre ; un
 *    événement qui pose son bandeau garde toujours son choix.
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

    /** Bandeau par défaut des pages publiques hors accueil. */
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

    /** URL du bandeau partagé par les pages publiques hors accueil, ou null. */
    public static function pageDefault(): ?string
    {
        return self::url(self::EVENT_DEFAULT);
    }

    /** Compatibilité avec les pages d'événement déjà branchées sur ce repli. */
    public static function eventDefault(): ?string
    {
        return self::pageDefault();
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
