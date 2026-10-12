<?php

namespace App\Support;

use App\Models\User;

/**
 * Annuaire NOMINATIF des COMPTES pour le copilote d'administration (« Marc ») :
 * qui est qui, avec quel niveau d'accès, quel rôle, quel état et quelle dernière
 * connexion. De quoi répondre « qui est gérant du colloque ? », « combien de
 * comptes ne sont pas vérifiés ? », « quand untel s'est-il connecté ? ».
 *
 * ⚠️ **Marc n'en dit ni plus ni moins que la page.** Le contenu est calqué sur
 * les colonnes d'`admin/utilisateurs` (nom, e-mail, niveau, rôles, vérification,
 * dernière connexion, IP, statut) et sur ses compteurs. La règle est simple à
 * tenir et à vérifier : si une donnée n'est pas sur la page, elle n'est pas ici.
 *
 * ⚠️ **La permission est vérifiée ICI, pas chez l'appelant.** `AskAdmin` est
 * joignable par tout le staff ayant « Tableau de bord » sur l'événement -
 * relecteur, secrétariat, comptabilité. Laisser la garde au point d'appel serait
 * l'oublier un jour. {@see digest()} rend une chaîne vide à qui n'a pas
 * `users_roles`, la permission qui garde déjà la page Utilisateurs & rôles :
 * l'assistant n'ouvre ainsi aucune porte que l'interface tient fermée.
 *
 * ⚠️ Borné (le prompt est reconstruit à CHAQUE message) : au-delà des plafonds,
 * on renvoie vers les filtres de la page plutôt que de gonfler le contexte. Les
 * 263 comptes participants d'un colloque importé n'ont rien à faire dans un
 * prompt - leurs données nominatives viennent déjà d'{@see AdminDirectory},
 * bornées à l'événement en cours.
 */
class UserDirectory
{
    /** Au-delà, on renvoie vers les filtres de la page. */
    public const MAX_PERSONNEL = 60;

    public const MAX_LISTE = 25;

    /**
     * L'annuaire, ou une chaîne vide si le demandeur n'a pas à le voir.
     */
    public static function digest(?User $staff, int $maxPersonnel = self::MAX_PERSONNEL, int $maxListe = self::MAX_LISTE): string
    {
        if (! self::autorise($staff)) {
            return '';
        }

        $blocs = array_filter([
            self::personnel($maxPersonnel),
            self::chiffres(),
            self::recents($maxListe),
            self::aRelancer($maxListe),
        ]);

        if ($blocs === []) {
            return '';
        }

        return "COMPTES DE LA PLATEFORME - tu peux répondre nominativement sur les comptes ci-dessous.\n\n"
            .implode("\n\n", $blocs);
    }

    /**
     * ⚠️ La porte est celle de la PAGE, pas un niveau d'accès : `users_roles` est
     * ce que le middleware `can:users_roles` exige d'`admin/utilisateurs`. Un
     * administrateur de colloque qui n'a pas cette permission est en 403 sur la
     * page ; il ne doit pas l'obtenir en passant par l'assistant.
     */
    public static function autorise(?User $staff): bool
    {
        return $staff !== null && $staff->can('users_roles');
    }

    /** Les comptes qui portent un rôle UKC ou un niveau d'accès élevé. */
    private static function personnel(int $max): string
    {
        $query = User::query()
            ->with('roles')
            ->where(fn ($q) => $q->whereHas('roles')->orWhere('access_level', '!=', User::ACCESS_USER))
            ->orderBy('name');

        $total = (clone $query)->count();
        if ($total === 0) {
            return '';
        }

        $lignes = ['PERSONNEL ET COMPTES À NIVEAU D’ACCÈS ('.$total.') : « Nom <e-mail> - niveau - rôle(s) - état - dernière connexion »'];

        foreach ($query->limit($max)->get() as $u) {
            $lignes[] = '- '.self::ligne($u);
        }

        if ($total > $max) {
            $lignes[] = '… ('.($total - $max).' autre(s) non listé(s) - utiliser les filtres de la page Utilisateurs & rôles)';
        }

        return implode("\n", $lignes);
    }

    /** Les compteurs, qui répondent aux questions de volume sans lister personne. */
    private static function chiffres(): string
    {
        $total = User::count();
        if ($total === 0) {
            return '';
        }

        $parRole = collect(array_keys(StaffRoles::ROLES))
            ->mapWithKeys(fn (string $r) => [$r => User::whereHas('roles', fn ($q) => $q->where('name', $r))->count()])
            ->filter();

        $lignes = [
            'RÉPARTITION DES COMPTES : '.$total.' au total'
                .' - '.User::where('is_active', true)->count().' actif(s)'
                .' - '.User::where('is_active', false)->count().' désactivé(s)'
                .' - '.User::whereNull('email_verified_at')->count().' non vérifié(s)'
                .' - '.User::whereNull('last_login_at')->count().' jamais connecté(s)',
        ];

        if ($parRole->isNotEmpty()) {
            $lignes[] = 'PAR RÔLE : '.$parRole
                ->map(fn (int $n, string $r) => StaffRoles::label($r).' : '.$n)
                ->implode(', ');
        }

        $niveaux = collect(User::accessLevels())
            ->mapWithKeys(fn (string $libelle, string $niveau) => [$libelle => User::where('access_level', $niveau)->count()])
            ->filter();

        $lignes[] = 'PAR NIVEAU D’ACCÈS : '.$niveaux->map(fn (int $n, string $l) => $l.' : '.$n)->implode(', ');

        return implode("\n", $lignes);
    }

    /** Les derniers comptes créés : « qui s'est inscrit cette semaine ? ». */
    private static function recents(int $max): string
    {
        $recents = User::query()->latest('created_at')->limit($max)->get();
        if ($recents->isEmpty()) {
            return '';
        }

        $lignes = ['DERNIERS COMPTES CRÉÉS ('.$recents->count().' plus récents) : « Nom <e-mail> - créé le - état »'];
        foreach ($recents as $u) {
            $lignes[] = '- '.trim((string) $u->name).' <'.$u->email.'> - créé le '
                .($u->created_at?->format('d/m/Y') ?? 'date inconnue')
                .' - '.self::etat($u);
        }

        return implode("\n", $lignes);
    }

    /** Les comptes sur lesquels il y a quelque chose à faire. */
    private static function aRelancer(int $max): string
    {
        $query = User::query()
            ->where(fn ($q) => $q->whereNull('email_verified_at')->orWhere('is_active', false))
            ->orderBy('name');

        $total = (clone $query)->count();
        if ($total === 0) {
            return '';
        }

        $lignes = ['COMPTES NON VÉRIFIÉS OU DÉSACTIVÉS ('.$total.') : « Nom <e-mail> - état »'];
        foreach ($query->limit($max)->get() as $u) {
            $lignes[] = '- '.trim((string) $u->name).' <'.$u->email.'> - '.self::etat($u);
        }

        if ($total > $max) {
            $lignes[] = '… ('.($total - $max).' autre(s) - filtrer sur « Non vérifié » / « Désactivé » depuis la page Utilisateurs & rôles)';
        }

        return implode("\n", $lignes);
    }

    /** Une ligne de compte, calquée sur la rangée du tableau. */
    private static function ligne(User $u): string
    {
        $roles = $u->roles->pluck('name')->map(fn ($n) => StaffRoles::label((string) $n))->filter()->implode(', ');

        $connexion = $u->last_login_at
            ? 'dernière connexion le '.$u->last_login_at->format('d/m/Y à H:i')
                .($u->last_login_ip ? ' depuis '.$u->last_login_ip : '')
            : 'jamais connecté';

        return implode(' - ', array_filter([
            trim((string) $u->name) !== '' ? trim((string) $u->name) : 'Sans nom',
            '<'.$u->email.'>',
            User::accessLevels()[$u->access_level] ?? (string) $u->access_level,
            $roles !== '' ? $roles : null,
            self::etat($u),
            $connexion,
        ]));
    }

    /** Actif/désactivé et vérifié/non vérifié, les deux pastilles de la page. */
    private static function etat(User $u): string
    {
        return ($u->is_active ? 'actif' : 'DÉSACTIVÉ')
            .', '.($u->hasVerifiedEmail() ? 'vérifié' : 'non vérifié');
    }

    /** Utilisé par le prompt pour annoncer ce que Marc sait faire. */
    public static function resume(?User $staff): string
    {
        if (! self::autorise($staff)) {
            return '';
        }

        return 'Tu as accès à l’annuaire des COMPTES (niveaux d’accès, rôles, vérification, '
            .'dernières connexions). Réponds nominativement quand on te le demande. ⚠️ Tu ne crées, '
            .'ne modifies ni ne désactives aucun compte : pour toute écriture, renvoie vers l’onglet '
            .'Utilisateurs & rôles. Au-delà des comptes listés, dis-le et renvoie vers les filtres de '
            .'cette page plutôt que d’inventer.';
    }
}
