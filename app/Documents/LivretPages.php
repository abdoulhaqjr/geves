<?php

namespace App\Documents;

use App\Models\Event;
use Illuminate\Support\Facades\Crypt;

/**
 * Pages supplémentaires du livre des résumés, conçues dans DocSpace.
 *
 * ⚠️⚠️ **MÊME GRAMMAIRE QUE LA PAGE DE GARDE** ({@see LivretCover}) : un fichier
 * .docx par page, édité dans l'éditeur Word en ligne, propre à l'événement. La
 * seule chose en plus - et c'est tout l'objet de cette classe - est que la page
 * ne va plus forcément EN TÊTE : le gestionnaire dit où elle se pose.
 *
 * Deux façons de le dire, et elles ne se valent pas :
 *
 * - **Par section** (`mode = section`) : « avant les Résumés », « après le
 *   Programme ». L'ancre suit le livre : on ajoute un résumé, le livre grossit,
 *   la page reste à sa place.
 * - **Par numéro de page** (`mode = page`) : « en page 7 ». ⚠️ Le livre est
 *   RECONSTRUIT À CHAQUE EXPORT à partir des communications acceptées : une
 *   communication de plus, et la page 7 d'hier n'est plus celle d'aujourd'hui.
 *   Le réglage reste exact - il place bien en page 7 - mais ce qu'il y a autour
 *   a bougé. C'est pour cela que l'écran recommande la première façon.
 *
 * ⚠️ **Lecture DIRECTE des réglages de l'événement, jamais `EventSettings::get`.**
 * Celui-ci retombe sur le réglage GLOBAL quand l'événement n'a rien : des pages
 * déposées une fois au niveau plateforme s'inviteraient alors dans le livre de
 * TOUS les événements qui n'en ont pas. Un livre ne se remplit pas des pages
 * d'un autre colloque.
 */
final class LivretPages
{
    /**
     * Les points d'ancrage du livre, DANS L'ORDRE où ils apparaissent.
     *
     * ⚠️ Cette liste est la SOURCE UNIQUE : le PDF (`documents.livret-pdf`) et le
     * .docx ({@see LivretDocx::build()}) posent leurs repères aux mêmes endroits
     * et sous les mêmes clés. Deux listes se décaleraient, et la page atterrirait
     * à un endroit dans le PDF, à un autre dans le Word.
     *
     * @var array<string, string>
     */
    public const SECTIONS = [
        'garde' => 'Page de garde',
        'patronage' => 'Haut patronage',
        'remerciements' => 'Remerciements',
        'avant-propos' => 'Avant-propos',
        'bienvenue' => 'Mot de bienvenue',
        'president' => 'Mot du président',
        'commissions' => 'Commissions et programmes',
        'programme' => 'Programme',
        'resumes' => 'Résumés des communications',
        'index' => 'Index des auteurs',
    ];

    /** @var array<string, string> */
    public const POSITIONS = ['avant' => 'Avant', 'apres' => 'Après'];

    /** Schéma des repères posés dans le PDF, relus ensuite par pdf-lib. */
    public const URI = 'ukc-livret://';

    /**
     * Combien de pages ajoutées au plus.
     *
     * ⚠️ Chaque page coûte une conversion DocSpace **à chaque export** du livre,
     * et l'export est déjà le document le plus lourd de la plateforme. Un livre
     * qui met trois minutes à se télécharger ne se télécharge plus.
     */
    public const MAX = 10;

    /**
     * Toutes les pages déclarées, normalisées et dans l'ordre d'affichage.
     *
     * @return list<array<string, mixed>>
     */
    public static function all(Event $event): array
    {
        $raw = $event->settings['doc']['livret.pages'] ?? [];
        if (! is_array($raw)) {
            return [];
        }

        $pages = [];
        foreach ($raw as $entry) {
            if (! is_array($entry) || trim((string) ($entry['id'] ?? '')) === '') {
                continue;
            }
            $pages[] = self::normalise($entry);
        }

        return $pages;
    }

    /**
     * Les pages réellement portées au livre : actives ET rattachées à un fichier.
     *
     * ⚠️ Une page désactivée n'est pas supprimée : le gestionnaire la retire du
     * livre le temps d'une relecture et la remet, sans refaire sa mise en page.
     *
     * @return list<array<string, mixed>>
     */
    public static function active(Event $event): array
    {
        return array_values(array_filter(
            self::all($event),
            fn (array $page) => $page['active'] && $page['file_id'] !== '',
        ));
    }

    /** @return array<string, mixed>|null */
    public static function find(Event $event, string $id): ?array
    {
        foreach (self::all($event) as $page) {
            if ($page['id'] === $id) {
                return $page;
            }
        }

        return null;
    }

    /** @param list<array<string, mixed>> $pages */
    public static function save(Event $event, array $pages): void
    {
        $settings = $event->settings ?? [];
        $bag = $settings['doc'] ?? [];
        $bag['livret.pages'] = array_map(self::normalise(...), $pages);
        $settings['doc'] = $bag;
        $event->settings = $settings;
        $event->save();
    }

    /** Identifiant stable, porté par les repères du PDF : sans espace ni ponctuation. */
    public static function newId(): string
    {
        return 'p'.bin2hex(random_bytes(6));
    }

    /** @param array<string, mixed> $page */
    public static function token(array $page): string
    {
        $encrypted = (string) ($page['token'] ?? '');

        return $encrypted === '' ? '' : Crypt::decryptString($encrypted);
    }

    public static function encryptedToken(string $token): string
    {
        return Crypt::encryptString($token);
    }

    /**
     * Clé du point d'insertion d'une page ancrée à une section : « avant:resumes ».
     *
     * ⚠️ Les pages en mode `page` n'ont PAS de clé : elles ne se posent pas dans
     * le flux du document, elles se glissent après coup dans le PDF assemblé.
     *
     * @param  array<string, mixed>  $page
     */
    public static function slot(array $page): string
    {
        return $page['mode'] === 'section' ? $page['position'].':'.$page['anchor'] : '';
    }

    /**
     * Libellé lisible de l'emplacement, pour l'écran et pour les messages.
     *
     * @param  array<string, mixed>  $page
     */
    public static function placementLabel(array $page): string
    {
        if ($page['mode'] === 'page') {
            return 'En page '.$page['page'];
        }

        return (self::POSITIONS[$page['position']] ?? 'Avant').' « '
            .(self::SECTIONS[$page['anchor']] ?? $page['anchor']).' »';
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    private static function normalise(array $entry): array
    {
        $mode = ($entry['mode'] ?? 'section') === 'page' ? 'page' : 'section';
        $anchor = (string) ($entry['anchor'] ?? 'resumes');
        $position = (string) ($entry['position'] ?? 'avant');

        return [
            'id' => (string) $entry['id'],
            'title' => trim((string) ($entry['title'] ?? '')) ?: 'Page ajoutée',
            'file_id' => (string) ($entry['file_id'] ?? ''),
            'token' => (string) ($entry['token'] ?? ''),
            'mode' => $mode,
            'anchor' => $anchor = array_key_exists($anchor, self::SECTIONS) ? $anchor : 'resumes',
            // ⚠️ **RIEN NE PASSE DEVANT LA COUVERTURE.** « Avant la page de garde »
            // ferait de la page ajoutée la première page du livre, et la couverture
            // DocSpace - qui prend la place de la PREMIÈRE page - s'écraserait sur
            // elle. Une page de garde qui n'est plus la garde n'est plus une garde.
            'position' => $anchor === 'garde' ? 'apres'
                : (array_key_exists($position, self::POSITIONS) ? $position : 'avant'),
            // ⚠️ La page 1 est la couverture : une page ajoutée « en page 1 » la
            // repousserait sans qu'on l'ait demandé. On part donc de 2, et on borne
            // haut pour qu'un nombre fantaisiste ne sorte pas l'insertion du livre.
            'page' => max(2, min(9999, (int) ($entry['page'] ?? 2))),
            'active' => filter_var($entry['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
