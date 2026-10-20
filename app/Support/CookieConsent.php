<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Bandeau d'information sur les cookies, et INVENTAIRE de ce que la plateforme
 * dépose réellement.
 *
 * ⚠️⚠️ **GEVES NE POSE QUE DES COOKIES STRICTEMENT NÉCESSAIRES**, et c'est ce
 * qui dicte la forme du bandeau : la session (connexion) et le jeton CSRF. Il
 * n'y a ni mesure d'audience, ni régie, ni contenu tiers embarqué sur le site
 * public. Le bandeau INFORME donc, il ne demande pas un consentement qui n'a
 * rien à gouverner.
 *
 * ⚠️ **Le « Refuser » d'un bandeau du commerce efface tous les cookies du
 * domaine** puis invite à recharger la page. Transposé ici, il déconnecterait
 * le participant et ferait échouer sa requête suivante sur le jeton CSRF - pour
 * refuser des cookies qui n'existent pas. Le jour où un outil de mesure arrive,
 * c'est ICI qu'on ajoutera le choix, et c'est `accepted()` qui le portera : un
 * seul point de passage, pas un bouton par écran.
 */
final class CookieConsent
{
    /** Le bandeau est-il activé pour cette installation ? */
    public static function enabled(): bool
    {
        return (bool) config('cookies.notice_enabled', true);
    }

    public static function cookieName(): string
    {
        return FirstPartyCookieNames::notice((string) config('cookies.cookie_name', 'geves_cookie_notice'));
    }

    public static function lifetimeDays(): int
    {
        return max(1, (int) config('cookies.lifetime_days', 180));
    }

    /**
     * Le visiteur a-t-il déjà écarté le bandeau ?
     *
     * ⚠️ Lu par les tests et par un futur appelant serveur - **jamais par la vue**,
     * qui doit rendre le même balisage pour tout le monde (cf. l'entête de
     * `components/ukc/cookie-notice`, et la cacheabilité des pages publiques).
     */
    public static function acknowledged(?Request $request = null): bool
    {
        return ($request ?? request())->cookie(self::cookieName()) !== null;
    }

    /**
     * ⚠️ `third` est une DONNÉE, pas une déduction depuis le libellé : la vue
     * distinguait le tiers en comparant `party` à une chaîne traduite, donc la
     * distinction tombait dès qu'on lisait la page en anglais - et c'est la seule
     * ligne du tableau qui engage quelqu'un d'autre que nous.
     *
     * Inventaire affiché sur `/cookies`, construit depuis la CONFIGURATION
     * RÉELLE et non recopié à la main : le nom du cookie de session et sa durée
     * changent avec `config/session.php`, et une page qui l'ignorerait
     * annoncerait autre chose que ce que le navigateur reçoit.
     *
     * @return list<array{name: string, party: string, third: bool, purpose: string, duration: string}>
     */
    public static function inventory(): array
    {
        $minutes = max(1, (int) config('session.lifetime', 120));
        $sessionDuree = $minutes >= 1440
            ? __(':n jour(s)', ['n' => (int) round($minutes / 1440)])
            : __(':n minute(s) d’inactivité', ['n' => $minutes]);

        $lignes = [
            [
                'name' => (string) config('session.cookie', 'geves_session'),
                'party' => __('Première partie'),
                'third' => false,
                'purpose' => __('Tient votre session : connexion à votre espace, étapes d’un formulaire en cours. Sans lui, impossible de rester connecté.'),
                'duration' => $sessionDuree,
            ],
            [
                'name' => FirstPartyCookieNames::csrf(),
                'party' => __('Première partie'),
                'third' => false,
                'purpose' => __('Jeton anti-falsification de requête : il prouve que le formulaire envoyé vient bien de cette page.'),
                'duration' => $sessionDuree,
            ],
            [
                'name' => self::cookieName(),
                'party' => __('Première partie'),
                'third' => false,
                'purpose' => __('Retient que vous avez lu cette information, pour ne plus afficher le bandeau.'),
                'duration' => __(':n jour(s)', ['n' => self::lifetimeDays()]),
            ],
        ];

        // ⚠️ Turnstile n'est listé que s'il est CONFIGURÉ : sans ses deux clés,
        // le widget ne se charge sur aucune page et annoncer son dépôt serait
        // annoncer ce qui n'arrive pas.
        if (Turnstile::configured()) {
            $lignes[] = [
                'name' => 'Cloudflare Turnstile',
                'party' => __('Tierce partie - Cloudflare'),
                'third' => true,
                'purpose' => __('Vérifie que le formulaire d’inscription ou de contact n’est pas envoyé par un automate. Ce contrôle ne sert pas à vous suivre d’un site à l’autre.'),
                'duration' => __('Le temps du contrôle'),
            ];
        }

        return $lignes;
    }
}
