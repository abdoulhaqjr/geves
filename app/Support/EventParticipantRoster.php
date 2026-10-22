<?php

namespace App\Support;

use App\Models\Participant;
use App\Models\Submission;
use Illuminate\Support\Collection;

/** Une personne par événement, classée d'après ses communications réelles. */
class EventParticipantRoster
{
    /**
     * @return Collection<int, array{participant: Participant, role: string, participationMode: string|null, participationModeLabel: string|null, paid: bool, invited: bool, createdAt: mixed, name: string, email: string, userId: int|null}>
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
            if (isset($byParticipantId[$submission->participant_id])) {
                $groups[$byParticipantId[$submission->participant_id]]['corresponding'] = true;
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
                if ($key !== null) {
                    $groups[$key][$link->role === 'corresponding' ? 'corresponding' : 'coauthor'] = true;
                }
            }

            // Les anciennes communications n'ont pas toujours de lignes submission_authors.
            foreach ($submission->authors ?? [] as $index => $author) {
                $email = $this->email($author['email'] ?? null);
                if ($email === '' || isset($declinedEmails[$email])) {
                    continue;
                }
                if (isset($byEmail[$email]) && ! isset($declinedKeys[$byEmail[$email]])) {
                    $groups[$byEmail[$email]][$index === 0 ? 'corresponding' : 'coauthor'] = true;
                }
            }
        }

        return collect($groups)->map(function (array $group) {
            $rows = collect($group['rows']);
            $paid = $rows->contains(fn (Participant $p) => $p->participationPayments->contains('status', 'paid'));
            if (! $group['corresponding'] && ! $group['coauthor']
                && ! $paid && ! $rows->contains('role', 'attendee')) {
                return null;
            }

            $role = $group['corresponding'] ? 'author' : ($group['coauthor'] ? 'coauthor' : 'attendee');
            $representative = $rows->sortByDesc(fn (Participant $p) => (int) ($p->role === $role) * 8
                + (int) filled($p->user_id) * 4
                + (int) $p->participationPayments->contains('status', 'paid') * 2
                + (int) filled($p->email))->first();
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
                'createdAt' => $rows->min('created_at'),
                'name' => $representative->full_name,
                'email' => (string) $representative->email,
                'userId' => $group['userId'],
            ];
        })->filter()->values();
    }

    private function email(?string $email): string
    {
        return mb_strtolower(trim((string) $email));
    }
}
