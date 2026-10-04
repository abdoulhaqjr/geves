<?php

namespace App\Meetings;

use App\Meetings\Contracts\Meetable;
use App\Models\EventSession;
use App\Models\Formation;
use Illuminate\Database\Eloquent\Model;

/**
 * Objets dont la salle de visio est servie par une adresse générique
 * (`/visio/{type}/{id}/…`) : fil de discussion, renouvellement de jeton.
 *
 * ⚠️ Liste **fermée**. Le type vient de l'URL, et on ne résout jamais une classe
 * à partir de ce que le client a écrit. C'est aussi le seul endroit où le
 * vocabulaire (« session », « formation ») est défini : il sert dans les deux
 * sens - l'URL vers l'objet, et l'objet vers l'URL.
 */
class MeetableRegistry
{
    /** @var array<string, class-string<Meetable&Model>> */
    public const TYPES = [
        'session' => EventSession::class,
        'formation' => Formation::class,
    ];

    /** L'objet visé, ou null si le type est inconnu ou l'objet absent. */
    public static function find(string $type, int|string $id): (Meetable&Model)|null
    {
        $classe = self::TYPES[$type] ?? null;
        if ($classe === null) {
            return null;
        }

        /** @var (Meetable&Model)|null $objet */
        $objet = $classe::query()->find($id);

        return $objet;
    }

    /**
     * Nom de l'ONGLET qui tient la salle de cet objet, ou null s'il n'en a pas.
     *
     * ⚠️ La salle se nomme elle-même avec cette chaîne (`window.name`), et les
     * pages qui ramènent vers elle la posent en `target` : le navigateur
     * **réutilise** alors l'onglet déjà ouvert au lieu d'en créer un second -
     * deux onglets sur la même salle, c'est deux connexions, deux vignettes et
     * de l'écho. D'où une **définition unique** : deux chaînes construites
     * chacune de leur côté se décaleraient un jour, et l'onglet cesserait
     * d'être retrouvé sans que rien ne casse visiblement.
     */
    public static function tabFor(Meetable&Model $objet): ?string
    {
        $cle = self::keyFor($objet);

        return $cle === null ? null : 'geves-visio-'.$cle.'-'.$objet->getKey();
    }

    /** Clé d'URL de cet objet (« session », « formation »), ou null s'il n'en a pas. */
    public static function keyFor(Meetable&Model $objet): ?string
    {
        $cle = array_search($objet::class, self::TYPES, true);

        return $cle === false ? null : $cle;
    }
}
