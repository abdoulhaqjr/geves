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
                'name' => self::accountName($account),
                'email' => (string) $participant->email,
                'institution' => (string) $participant->institution,
                'ref' => $event->id.'-'.$participant->id,
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
                $recipient['name'] = self::accountName($account);
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
     * @return Collection<int, array{name: string, email: string, institution: string, ref: string, photo?: string}>
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
                    'name' => $person->user ? self::accountName($person->user) : $person->full_name,
                    'email' => (string) $person->email,
                    'institution' => (string) $person->institution,
                    'ref' => $event->id.'-'.$person->id,
                    // Le calque photo est facultatif dans le modèle du designer.
                    'photo' => ($u = $person->user) && $u->photo_path ? Storage::url($u->photo_path) : '',
                    'photo_mode' => ((array) (($person->meta ?? [])['badge_photo_preferences'] ?? []))[$type] ?? 'with',
                ])->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }

        if (in_array($type, ['moderation', 'rapporteur'], true)) {
            $prefix = $type === 'moderation' ? 'moderator' : 'rapporteur';

            return self::unique(self::withCurrentAccountNames($event, $type, $event->sessions()->get()->map(fn ($session) => [
                'name' => trim((string) $session->{$prefix.'_name'}),
                'email' => trim((string) $session->{$prefix.'_email'}),
                'institution' => '',
                'ref' => $event->id.'-'.$type.'-'.md5(mb_strtolower(trim((string) ($session->{$prefix.'_email'} ?: $session->{$prefix.'_name'})))),
            ])));
        }

        $teamRoles = match ($type) {
            'appui_organisation' => ['support', 'organizer', 'secretariat', 'accounting'],
            'accueil' => ['reception'],
            'comite_supervision' => ['comite_supervision'],
            'comite_organisation' => ['comite_organisation'],
            'comite_scientifique' => ['scientific'],
        };

        $people = $event->team()->wherePivotIn('role', $teamRoles)->get()
            ->map(fn ($user) => [
                'name' => trim((string) $user->first_name.' '.(string) $user->last_name) ?: (string) $user->name,
                'email' => (string) $user->email,
                'institution' => (string) $user->affiliation,
                'ref' => $event->id.'-'.$type.'-user-'.$user->id,
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
                    'ref' => $event->id.'-'.$type.'-committee-'.$member->id,
                ]));
        }

        return self::unique(self::withCurrentAccountNames($event, $type, $people));
    }

    /** Une affectation garde son nom historique ; le badge lit l'identité actuelle du compte lié. */
    private static function withCurrentAccountNames(Event $event, string $type, Collection $people): Collection
    {
        $emails = $people->pluck('email')
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->filter()->unique()->values();

        if ($emails->isEmpty()) {
            return $people;
        }

        $accounts = User::query()->whereIn(DB::raw('LOWER(email)'), $emails->all())
            ->get()->keyBy(fn (User $user) => mb_strtolower(trim((string) $user->email)));
        $participants = Participant::where('event_id', $event->id)
            ->whereIn('user_id', $accounts->pluck('id'))
            ->get()->keyBy('user_id');

        return $people->map(function (array $person) use ($accounts, $participants, $type) {
            $account = $accounts->get(mb_strtolower(trim((string) ($person['email'] ?? ''))));
            if ($account) {
                $person['name'] = self::accountName($account);
                $person['photo'] = $account->photo_path ? Storage::url($account->photo_path) : '';
                $participation = $participants->get($account->id);
                $person['photo_mode'] = ((array) (($participation?->meta ?? [])['badge_photo_preferences'] ?? []))[$type] ?? 'with';
            }

            return $person;
        });
    }

    public static function accountName(User $account): string
    {
        return trim(trim((string) $account->first_name).' '.trim((string) $account->last_name))
            ?: (trim((string) $account->name) ?: (string) $account->email);
    }

    /** @param Collection<int, array{name: string, email: string, institution: string, ref: string}> $people */
    private static function unique(Collection $people): Collection
    {
        $seenEmails = [];
        $seenNames = [];

        return $people->filter(fn (array $person) => $person['name'] !== '' || $person['email'] !== '')
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
            ->values();
    }
}
