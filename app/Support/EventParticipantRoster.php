<?php

namespace App\Support;

use App\Models\Participant;
use App\Models\Submission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Une personne par événement, classée d'après ses communications réelles. */
class EventParticipantRoster
{
    /**
     * @return Collection<int, array{participant: Participant, role: string, participationMode: string|null, participationModeLabel: string|null, paid: bool, invited: bool, createdAt: Carbon|null, name: string, email: string, userId: int|null}>
     */
    public function forEvent(string $eventId): Collection
    {
        if ($eventId === '') {
            return collect();
        }

        $participants = Participant::query()->where('event_id', $eventId)
            ->with(['user', 'participationPayments'])->get();

        $emailToUser = [];
        foreach ($participants as $participant) {
            if ($participant->user_id) {
                foreach ([$participant->email, $participant->user?->email] as $email) {
                    $email = $this->email($email);
                    if ($email !== '') {
                        $emailToUser[$email] = (int) $participant->user_id;
                    }
                }
            }
        }

        $groups = [];
        $byParticipantId = [];
        $byUserId = [];
        $byEmail = [];

        foreach ($participants as $participant) {
            $email = $this->email($participant->email);
            $userId = $participant->user_id ?: ($emailToUser[$email] ?? null);
            $key = $userId ? 'u:'.$userId : ($email !== '' ? 'e:'.$email : 'p:'.$participant->id);

            $groups[$key]['rows'][] = $participant;
            $groups[$key]['userId'] = $userId ? (int) $userId : null;
            $groups[$key]['corresponding'] ??= false;
            $groups[$key]['coauthor'] ??= false;
            $groups[$key]['deposited'] ??= false;
            $byParticipantId[$participant->id] = $key;
            if ($userId) {
                $byUserId[$userId] = $key;
            }
            if ($email !== '') {
                $byEmail[$email] = $key;
            }
            $userEmail = $this->email($participant->user?->email);
            if ($userEmail !== '') {
                $byEmail[$userEmail] = $key;
            }
        }

        $submissions = Submission::query()->where('event_id', $eventId)->canonical()
            ->with('authorLinks')->get(['id', 'participant_id', 'authors']);

        foreach ($submissions as $submission) {
            // Le participant_id désigne le déposant, pas nécessairement un auteur.
            $deposant = $byParticipantId[$submission->participant_id] ?? null;
            if ($deposant !== null) {
                $groups[$deposant]['deposited'] = true;
            }

            $declinedEmails = [];
            $declinedKeys = [];
            foreach ($submission->authorLinks as $link) {
                if ($link->status === 'declined') {
                    $declinedEmails[$this->email($link->email)] = true;
                    if ($link->user_id && isset($byUserId[$link->user_id])) {
                        $declinedKeys[$byUserId[$link->user_id]] = true;
                    }

                    continue;
                }

                $key = ($link->user_id ? ($byUserId[$link->user_id] ?? null) : null)
                    ?? ($byEmail[$this->email($link->email)] ?? null);
                if ($key !== null && in_array($link->role, ['corresponding', 'coauthor'], true)) {
                    $groups[$key][$link->role === 'corresponding' ? 'corresponding' : 'coauthor'] = true;
                }
            }

            // Les anciennes communications n'ont pas toujours de lignes submission_authors.
            foreach ($submission->authors ?? [] as $index => $author) {
                $email = $this->email($author['email'] ?? null);
                // Un ancien dépôt « pour soi » peut porter ce marqueur explicite
                // sans e-mail. Il constitue alors une preuve d'auteur suffisante.
                if ($index === 0 && $email === '' && ($author['is_submitter'] ?? false) === true
                    && $deposant !== null && ! isset($declinedKeys[$deposant])) {
                    $groups[$deposant]['corresponding'] = true;
                }
                if ($email === '' || isset($declinedEmails[$email])) {
                    continue;
                }
                if (isset($byEmail[$email]) && ! isset($declinedKeys[$byEmail[$email]])) {
                    $groups[$byEmail[$email]][$index === 0 ? 'corresponding' : 'coauthor'] = true;
                }
            }
        }

        // On assemble un tableau, puis on l'enveloppe : une ligne du registre se
        // lit mieux dans une méthode nommée que dans une fermeture de cinquante
        // lignes, et le `null` d'une personne écartée se voit au point d'appel.
        // ⚠️⚠️ **CE N'EST PAS LE REMÈDE À L'ERREUR PHPSTAN DE `forEvent()`**,
        // et il ne faut pas le croire : elle PERSISTE. `Collection` n'est pas
        // covariante, le message affiche DEUX TYPES IDENTIQUES, et ni ce
        // passage par tableau ni le typage de `createdAt` ci-dessous ne l'ont
        // levée - les deux ont été essayés et mesurés. La cause est ailleurs,
        // probablement dans la déclaration de `forEvent()` elle-même.
        $lignes = [];

        foreach ($groups as $group) {
            $ligne = $this->ligne($group);
            if ($ligne !== null) {
                $lignes[] = $ligne;
            }
        }

        return collect($lignes);
    }

    /**
     * Une personne du registre, ou `null` si rien ne l'y fait entrer.
     *
     * ⚠️ La forme annoncée est EXACTEMENT celle de `forEvent()`, et les deux
     * doivent rester d'accord - c'est ce qui rend la méthode relisible. ⚠️ Cela
     * n'a PAS levé l'erreur de covariance de `forEvent()` : voir le commentaire
     * de son assemblage.
     *
     * @param  array<string, mixed>  $group
     * @return array{participant: Participant, role: string, participationMode: string|null, participationModeLabel: string|null, paid: bool, invited: bool, createdAt: Carbon|null, name: string, email: string, userId: int|null}|null
     */
    private function ligne(array $group): ?array
    {
        $rows = collect($group['rows']);
        $paid = $rows->contains(fn (Participant $p) => $p->participationPayments->contains('status', 'paid'));
        // Un déposant non auteur entre comme auditeur seulement s'il a payé.
        // Les inscriptions simples sans dépôt gardent leur statut habituel.
        if (! $group['corresponding'] && ! $group['coauthor'] && ! $paid
            && (! $rows->contains('role', 'attendee') || $group['deposited'])) {
            return null;
        }

        $role = $group['corresponding'] ? 'author' : ($group['coauthor'] ? 'coauthor' : 'attendee');
        $representative = $rows->sortByDesc(fn (Participant $p) => (int) ($p->role === $role) * 8
            + (int) filled($p->user_id) * 4
            + (int) $p->participationPayments->contains('status', 'paid') * 2
            + (int) filled($p->email))->first();

        // ⚠️ Un groupe naît TOUJOURS d'au moins une inscription (il est créé
        // en parcourant les participants), donc `first()` rend quelqu'un.
        // Mais l'analyse ne peut pas le savoir, et tout ce qui suit
        // déréférence ce représentant : on nomme l'invariant au lieu de le
        // supposer - sans quoi un groupe vide ferait tomber la page entière.
        if (! $representative) {
            return null;
        }

        $modeParticipant = in_array($representative->participation_mode, ['online', 'onsite'], true)
            ? $representative
            : $rows->first(fn (Participant $p) => in_array($p->participation_mode, ['online', 'onsite'], true));

        return [
            'participant' => $representative,
            'role' => $role,
            'participationMode' => $modeParticipant?->participation_mode,
            'participationModeLabel' => $modeParticipant?->participationModeLabel(),
            'paid' => $paid,
            'invited' => $rows->contains(fn (Participant $p) => $p->isInvited()),
            // `$rows->min('created_at')` ne se type pas : il rend `mixed`. On
            // nomme donc la date (d'où `@property Carbon|null $created_at` sur le
            // modèle, la convention du projet) - même valeur, type connu.
            // ⚠️ Posé pour lever l'erreur de covariance de `forEvent()`, ce
            // typage ne l'a PAS levée : il reste bon pour lui-même, pas comme
            // explication de cette erreur.
            'createdAt' => $rows->map(fn (Participant $p) => $p->created_at)->filter()->sort()->first(),
            'name' => $representative->full_name,
            'email' => (string) $representative->email,
            'userId' => $group['userId'],
        ];
    }

    private function email(?string $email): string
    {
        return mb_strtolower(trim((string) $email));
    }
}
