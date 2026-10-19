<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Types de badges et destinataires réels d'un événement. */
class BadgeCatalog
{
    /** @var array<string, array{label: string, title: string, source: string}> */
    public const TYPES = [
        'default' => ['label' => 'Participant', 'title' => 'Badge participant', 'source' => 'Inscriptions réglées'],
        'moderation' => ['label' => 'Modérateur', 'title' => 'Badge modérateur', 'source' => 'Modérateurs de séance'],
        'rapporteur' => ['label' => 'Rapporteur', 'title' => 'Badge rapporteur', 'source' => 'Rapporteurs de séance'],
        'appui_organisation' => ['label' => 'Appui au comité d’organisation', 'title' => 'Badge d’appui à l’organisation', 'source' => 'Équipe d’appui'],
        'accueil' => ['label' => 'Accueil', 'title' => 'Badge accueil', 'source' => 'Équipe d’accueil'],
        'comite_supervision' => ['label' => 'Comité de supervision', 'title' => 'Badge du comité de supervision', 'source' => 'Comité de supervision'],
        'comite_organisation' => ['label' => 'Comité d’organisation', 'title' => 'Badge du comité d’organisation', 'source' => 'Comité d’organisation'],
        'comite_scientifique' => ['label' => 'Comité scientifique', 'title' => 'Badge du comité scientifique', 'source' => 'Comité scientifique'],
    ];

    public static function valid(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    public static function label(string $type): string
    {
        return self::TYPES[$type]['label'] ?? self::TYPES['default']['label'];
    }

    /** Rôle imprimé sur le badge. */
    public static function role(string $type): string
    {
        return self::label($type);
    }

    /**
     * Badges d'un compte sur un événement. La participation figure même avant
     * paiement pour pouvoir afficher son aperçu verrouillé ; les fonctions sont
     * cumulables et correspondent aux mêmes destinataires que la planche admin.
     * Une fonction sans adresse ne peut pas être rattachée sûrement au compte.
     *
     * @return Collection<int, array{type: string, recipient: array{name: string, email: string, institution: string, ref: string}}>
     */
    public static function forAccount(Event $event, User $account, Participant $participant): Collection
    {
        $badges = collect([[
            'type' => 'default',
            'recipient' => [
                // Identité figée si l'événement est archivé, sinon en direct.
                'name' => $participant->displayName(),
                'email' => (string) $participant->email,
                'institution' => $participant->displayInstitution(),
                'ref' => self::reference($event->id.'-'.$participant->id),
            ],
        ]]);

        $emails = collect([$account->email, $participant->email])
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->filter()->unique();

        foreach (array_keys(self::TYPES) as $type) {
            if ($type === 'default') {
                continue;
            }

            $recipient = self::recipients($event, $type)->first(
                fn (array $person) => $emails->contains(mb_strtolower(trim($person['email'])))
                    && trim($person['email']) !== ''
            );
            if ($recipient !== null) {
                // Même personne que la participation : identité figée si archivé.
                $recipient['name'] = $participant->displayName();
                $badges->push(['type' => $type, 'recipient' => $recipient]);
            }
        }

        return $badges;
    }

    /**
     * Un destinataire par personne et par type, sans condition de paiement pour
     * les badges de fonction. Les comités mêlent la liste publique et l'équipe
     * affectée au back-office ; les séances portent leurs propres désignations.
     *
     * ⚠️ **La photo et son mode font partie du destinataire.** Le badge
     * « Participant avec photo » lit `photo`, et `photo_mode` dit si la personne
     * a demandé un badge SANS sa photo (préférence portée par son inscription).
     * Les annoncer ici n'est pas une formalité : la planche les lit
     * (`DocumentController`), et une forme qui les tait fait croire à l'analyse
     * que `photo_mode` n'existe pas - donc que la comparer à « without » est
     * toujours vraie, et que le choix de la personne n'est jamais respecté.
     *
     * ⚠️ **Les deux clés sont TOUJOURS là**, et c'est volontaire : `Collection`
     * n'est pas covariant pour l'analyse statique, donc une branche qui les pose
     * et une autre qui les omet ne rendent pas le même type. Les poser partout -
     * chaîne vide et « with » à défaut - évite d'avoir à les tester à chaque
     * lecture, et c'est bien ce que fait déjà la planche.
     *
     * @return Collection<int, array{name: string, email: string, institution: string, ref: string, photo: string, photo_mode: string}>
     */
    public static function recipients(Event $event, string $type): Collection
    {
        if (! self::valid($type)) {
            return collect();
        }

        if ($type === 'default') {
            return Participant::where('event_id', $event->id)
                ->whereHas('participationPayments', fn ($query) => $query->where('status', 'paid'))
                ->with('user')
                ->orderBy('last_name')->orderBy('first_name')->get()
                ->map(fn (Participant $person) => [
                    // Identité FIGÉE si l'événement est archivé (instantané du participant),
                    // sinon l'identité en direct du compte. Un profil modifié après la
                    // clôture ne retouche donc plus le badge de cet événement.
                    'name' => $person->displayName(),
                    'email' => (string) $person->email,
                    'institution' => $person->displayInstitution(),
                    'ref' => self::reference($event->id.'-'.$person->id),
                    // Le calque photo est facultatif dans le modèle du designer.
                    // ⚠️ Identité d'événement (figée à l'archivage) via displayPhotoUrl().
                    'photo' => $person->displayPhotoUrl(),
                    // ⚠️ La préférence vient d'une colonne JSON : aucun type garanti.
                    // On la ramène à une chaîne ici (cf. type de retour photo_mode: string) -
                    // sinon « sans photo » se comparerait à une valeur de forme inconnue.
                    'photo_mode' => (string) (((array) (($person->meta ?? [])['badge_photo_preferences'] ?? []))[$type] ?? 'with'),
                ])->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }

        if (in_array($type, ['moderation', 'rapporteur'], true)) {
            $prefix = $type === 'moderation' ? 'moderator' : 'rapporteur';

            // Le programme peut conserver des libellés collectifs ou des noms
            // historiques sans compte lié. Seule l'adresse d'affectation ouvre
            // réellement l'espace de fonction et rend la personne éligible à
            // son badge (même règle que pour les attestations de fonction).
            $people = $event->sessions()->get()
                ->filter(fn ($session) => filter_var(trim((string) $session->{$prefix.'_email'}), FILTER_VALIDATE_EMAIL))
                ->map(fn ($session) => [
                    'name' => trim((string) $session->{$prefix.'_name'}),
                    'email' => trim((string) $session->{$prefix.'_email'}),
                    'institution' => (string) '',
                    'ref' => self::reference($event->id.'-'.$type.'-'.md5(mb_strtolower(trim((string) $session->{$prefix.'_email'})))),
                ])->values()->all();

            return collect(self::unique(self::withCurrentAccountNames($event, $type, $people)));
        }

        $teamRoles = match ($type) {
            'appui_organisation' => ['support', 'organizer', 'secretariat', 'accounting'],
            'accueil' => ['reception'],
            'comite_supervision' => ['comite_supervision'],
            'comite_organisation' => ['comite_organisation'],
            'comite_scientifique' => ['scientific'],
            // ⚠️ `valid()` a déjà écarté les types inconnus, et les trois autres
            // sont partis plus haut : cette branche ne se produit pas. Elle existe
            // pour qu'un type AJOUTÉ à TYPES sans fonction d'équipe rende une liste
            // vide - aucun destinataire - plutôt qu'une erreur de correspondance en
            // pleine impression de planche.
            default => [],
        };

        $people = $event->team()->wherePivotIn('role', $teamRoles)->get()
            ->map(fn ($user): array => [
                'name' => EventNameFormat::user($event, $user),
                'email' => (string) $user->email,
                'institution' => (string) $user->affiliation,
                'ref' => self::reference($event->id.'-'.$type.'-user-'.$user->id),
            ]);

        $committeeRole = match ($type) {
            'comite_supervision' => 'supervision',
            'comite_organisation' => 'organisation',
            'comite_scientifique' => 'scientifique',
            default => null,
        };
        if ($committeeRole !== null) {
            $people = $people->concat($event->committeeMembers()->where('role', $committeeRole)->get()
                ->map(fn ($member) => [
                    'name' => trim((string) $member->person_name),
                    'email' => (string) $member->email,
                    'institution' => (string) $member->institution,
                    'ref' => self::reference($event->id.'-'.$type.'-committee-'.$member->id),
                ]));
        }

        return collect(self::unique(self::withCurrentAccountNames($event, $type, $people->all())));
    }

    /**
     * Une affectation garde son nom historique ; le badge lit l'identité actuelle
     * du compte lié - et chaque destinataire ressort avec sa photo et son mode,
     * même sans compte rattaché (cf. {@see recipients()}).
     *
     * ⚠️ **Tableaux et non collections** : `Collection` n'est pas covariant pour
     * l'analyse statique, donc une liste de destinataires dont l'institution est
     * la chaîne vide ne « vaut » pas une liste dont l'institution est une chaîne.
     * Les tableaux, eux, le sont - c'est le remède déjà retenu ailleurs dans le
     * projet (cf. CLAUDE.md), et les appelants rendent une collection à la fin.
     *
     * @param  list<array{name: string, email: string, institution: string, ref: string}>  $people
     * @return list<array{name: string, email: string, institution: string, ref: string, photo: string, photo_mode: string}>
     */
    private static function withCurrentAccountNames(Event $event, string $type, array $people): array
    {
        $emails = collect($people)->pluck('email')
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->filter()->unique()->values();

        $sansCompte = fn (array $person): array => $person + ['photo' => '', 'photo_mode' => 'with'];

        if ($emails->isEmpty()) {
            return array_map($sansCompte, $people);
        }

        $accounts = User::query()->whereIn(DB::raw('LOWER(email)'), $emails->all())
            ->get()->keyBy(fn (User $user) => mb_strtolower(trim((string) $user->email)));
        $participants = Participant::where('event_id', $event->id)
            ->whereIn('user_id', $accounts->pluck('id'))
            ->get()->keyBy('user_id');

        return array_map(function (array $person) use ($accounts, $participants, $type, $sansCompte, $event) {
            $person = $sansCompte($person);
            $account = $accounts->get(mb_strtolower(trim((string) ($person['email'] ?? ''))));
            if ($account) {
                $participation = $participants->get($account->id);
                if ($participation) {
                    // La personne est inscrite : on suit son identité d'événement,
                    // figée à l'archivage (sinon l'identité du compte ci-dessous).
                    $person['name'] = $participation->displayName();
                    $person['photo'] = $participation->displayPhotoUrl();
                    $prefs = (array) (($participation->meta ?? [])['badge_photo_preferences'] ?? []);
                } else {
                    $person['name'] = self::accountName($account, $event);
                    $person['photo'] = $account->photo_path ? Storage::url($account->photo_path) : '';
                    $prefs = [];
                }
                // ⚠️ Préférence JSON → chaîne garantie (cf. type de retour photo_mode: string).
                $person['photo_mode'] = (string) ($prefs[$type] ?? 'with');
            }

            return $person;
        }, $people);
    }

    public static function accountName(User $account, ?Event $event = null): string
    {
        return $event
            ? EventNameFormat::user($event, $account)
            : EventNameFormat::parts((string) $account->first_name, (string) $account->last_name,
                EventNameFormat::FIRST_LAST, trim((string) $account->name) ?: (string) $account->email);
    }

    /** Clé courte et stable, commune à l'aperçu participant et à la planche admin. */
    private static function reference(string $identity): string
    {
        $hex = strtoupper(substr(hash('sha256', 'geves-badge-v1:'.$identity), 0, 16));

        return 'BDG-'.implode('-', str_split($hex, 4));
    }

    /**
     * @param  list<array{name: string, email: string, institution: string, ref: string, photo: string, photo_mode: string}>  $people
     * @return list<array{name: string, email: string, institution: string, ref: string, photo: string, photo_mode: string}>
     */
    private static function unique(array $people): array
    {
        $seenEmails = [];
        $seenNames = [];

        return collect($people)->filter(fn (array $person) => $person['name'] !== '' || $person['email'] !== '')
            ->filter(function (array $person) use (&$seenEmails, &$seenNames) {
                $email = mb_strtolower(trim($person['email']));
                $name = mb_strtolower(trim($person['name']));
                if (($email !== '' && isset($seenEmails[$email]))
                    || ($name !== '' && isset($seenNames[$name]) && ($email === '' || $seenNames[$name] === ''))) {
                    return false;
                }
                if ($email !== '') {
                    $seenEmails[$email] = true;
                }
                if ($name !== '') {
                    $seenNames[$name] = $email;
                }

                return true;
            })
            ->map(function (array $person) {
                if ($person['name'] === '') {
                    $person['name'] = $person['email'];
                }

                return $person;
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
