<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/** Types de badges et destinataires réels d'un événement. */
class BadgeCatalog
{
    /** @var array<string, array{label: string, title: string, source: string}> */
    public const TYPES = [
        'default' => ['label' => 'Participant', 'title' => 'Badge participant', 'source' => 'Inscriptions réglées'],
        'participant_photo' => ['label' => 'Participant avec photo', 'title' => 'Badge participant (avec photo)', 'source' => 'Inscriptions réglées'],
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

    /**
     * Rôle IMPRIMÉ sur le badge (≠ libellé de menu) : le modèle « Participant avec
     * photo » reste un badge de PARTICIPANT, on imprime donc « Participant », pas le
     * nom du modèle. Les autres types impriment leur libellé.
     */
    public static function role(string $type): string
    {
        return $type === 'participant_photo' ? self::TYPES['default']['label'] : self::label($type);
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
                'name' => $participant->full_name,
                'email' => (string) $participant->email,
                'institution' => (string) $participant->institution,
                'ref' => $event->id.'-'.$participant->id,
            ],
        ]]);

        $emails = collect([$account->email, $participant->email])
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->filter()->unique();

        foreach (array_keys(self::TYPES) as $type) {
            // 'participant_photo' est un MODÈLE d'impression côté admin (mêmes
            // destinataires que la participation) : on ne double pas le badge
            // participant dans l'espace de la personne.
            if ($type === 'default' || $type === 'participant_photo') {
                continue;
            }

            $recipient = self::recipients($event, $type)->first(
                fn (array $person) => $emails->contains(mb_strtolower(trim($person['email'])))
                    && trim($person['email']) !== ''
            );
            if ($recipient !== null) {
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

        // Le modèle « Participant avec photo » vise les MÊMES destinataires que le
        // badge participant (inscriptions réglées) ; seule la maquette diffère.
        if ($type === 'default' || $type === 'participant_photo') {
            return Participant::where('event_id', $event->id)
                ->whereHas('participationPayments', fn ($query) => $query->where('status', 'paid'))
                ->with('user')
                ->orderBy('last_name')->orderBy('first_name')->get()
                ->map(fn (Participant $person) => [
                    'name' => $person->full_name,
                    'email' => (string) $person->email,
                    'institution' => (string) $person->institution,
                    'ref' => $event->id.'-'.$person->id,
                    // Photo de profil (pour le badge avec photo) ; vide = initiales.
                    'photo' => ($u = $person->user) && $u->photo_path ? Storage::url($u->photo_path) : '',
                ]);
        }

        if (in_array($type, ['moderation', 'rapporteur'], true)) {
            $prefix = $type === 'moderation' ? 'moderator' : 'rapporteur';

            return self::unique($event->sessions()->get()->map(fn ($session) => [
                'name' => trim((string) $session->{$prefix.'_name'}),
                'email' => trim((string) $session->{$prefix.'_email'}),
                'institution' => '',
                'ref' => $event->id.'-'.$type.'-'.md5(mb_strtolower(trim((string) ($session->{$prefix.'_email'} ?: $session->{$prefix.'_name'})))),
            ]));
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

        return self::unique($people);
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
