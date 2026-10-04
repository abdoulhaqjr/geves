<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Images d'habillage d'une édition : **bandeau (hero)** et **bannière officielle**.
 *
 * Les deux champs stockent une **URL**, parce qu'une partie des éditions pointe
 * vers une image hébergée ailleurs (affiche du colloque, visuel d'un partenaire).
 * L'admin peut donc au choix **téléverser** un fichier ou **coller une URL** : le
 * téléversement l'emporte, puisqu'il est l'action la plus explicite.
 *
 * ⚠️ On ne supprime du disque que **nos** fichiers (`editions/`) : une URL
 * externe ne nous appartient pas, et l'ancien fichier d'une édition resterait
 * servi publiquement si on l'oubliait.
 */
class EditionArtwork
{
    private const DISK = 'public';

    private const DIR = 'editions';

    /**
     * Valeur à enregistrer pour une image d'habillage.
     *
     * @param  UploadedFile|null  $file  fichier téléversé, prioritaire
     * @param  string|null  $typed  URL saisie à la main
     * @param  string|null  $previous  valeur actuellement enregistrée
     * @param  bool  $remove  l'admin a demandé la suppression
     */
    public static function resolve(?UploadedFile $file, ?string $typed, ?string $previous, bool $remove = false): ?string
    {
        $previous = trim((string) $previous) ?: null;
        $typed = trim((string) $typed) ?: null;

        if ($remove) {
            self::forget($previous);

            return null;
        }

        if ($file !== null) {
            $path = $file->store(self::DIR, self::DISK);
            self::forget($previous);

            return Storage::disk(self::DISK)->url($path);
        }

        // Une URL saisie ne remplace le fichier précédent que si elle en diffère :
        // ré-enregistrer la fiche sans toucher au champ ne doit rien supprimer.
        if ($typed !== null && $typed !== $previous) {
            self::forget($previous);

            return $typed;
        }

        return $typed ?? $previous;
    }

    /**
     * Plafond de téléversement **réellement applicable**, en kilo-octets.
     *
     * ⚠️ Une règle `max:` plus généreuse que `upload_max_filesize` ne sert à
     * rien : PHP coupe la requête avant la validation, et l'admin reçoit une
     * page vide au lieu d'un message. On annonce donc la limite du serveur, et
     * c'est elle qui est affichée dans le formulaire.
     */
    public static function maxUploadKb(): int
    {
        $limits = array_filter([
            self::iniKb('upload_max_filesize'),
            self::iniKb('post_max_size'),
        ]);

        return $limits === [] ? 10240 : (int) min($limits);
    }

    /**
     * Supprime nos fichiers qu'une nouvelle liste ne référence plus.
     *
     * ⚠️ Pour un répéteur - les logos de partenaires - on ne peut PAS comparer
     * ligne à ligne : l'index d'une ligne ne désigne plus la même entrée dès
     * qu'on réordonne ou qu'on retire, et le ménage effacerait le logo du
     * voisin. On compare donc les deux listes dans leur ENSEMBLE : tout fichier
     * à nous disparu de la nouvelle est orphelin, et resterait servi
     * publiquement si on l'oubliait.
     *
     * @param  list<string|null>  $avant
     * @param  list<string|null>  $apres
     */
    public static function forgetMissing(array $avant, array $apres): void
    {
        $garde = array_filter(array_map(fn ($u) => trim((string) $u), $apres));

        foreach ($avant as $url) {
            $url = trim((string) $url);

            if ($url !== '' && ! in_array($url, $garde, true)) {
                self::forget($url);
            }
        }
    }

    /** Valeur d'un réglage php.ini de taille, en kilo-octets (0 = illimité). */
    private static function iniKb(string $key): int
    {
        $raw = trim((string) ini_get($key));
        if ($raw === '' || $raw === '-1') {
            return 0;
        }

        $unit = strtolower(substr($raw, -1));
        $value = (float) $raw;

        return (int) match ($unit) {
            'g' => $value * 1024 * 1024,
            'm' => $value * 1024,
            'k' => $value,
            default => $value / 1024,
        };
    }

    /** Supprime le fichier précédent, s'il était hébergé par nous. */
    private static function forget(?string $url): void
    {
        $path = self::pathOf($url);

        if ($path !== null && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Chemin disque d'une URL que nous avons produite, sinon null.
     *
     * L'URL publique du disque peut être absolue (`https://site/storage/…`) ou
     * relative selon la configuration : on compare donc sur le préfixe calculé.
     */
    private static function pathOf(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $base = Storage::disk(self::DISK)->url(self::DIR.'/');
        $relative = Str::after($url, $base);

        return ($relative !== $url && $relative !== '')
            ? self::DIR.'/'.ltrim($relative, '/')
            : null;
    }
}
