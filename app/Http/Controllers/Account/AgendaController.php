<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Slot;
use App\Models\Submission;
use App\Support\CalendarExport;
use App\Support\CurrentColloque;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Agenda personnel du participant : ses créneaux de passage (colloque courant)
 * exportés en calendrier ICS (avec rappel), importable partout.
 */
class AgendaController extends Controller
{
    public function ics(CalendarExport $calendar): StreamedResponse
    {
        $events = $this->events();
        $ics = $calendar->ics($events, 'Mon agenda — GEVES');

        return response()->streamDownload(function () use ($ics) {
            echo $ics;
        }, 'mon-agenda.ics', ['Content-Type' => 'text/calendar; charset=UTF-8']);
    }

    /**
     * Événements calendrier des passages du participant sur le colloque courant.
     *
     * @return list<array{uid:string, summary:string, start:Carbon, end:Carbon|null, location:string, description:string}>
     */
    private function events(): array
    {
        $account = Auth::guard('account')->user();
        $current = CurrentColloque::current($account);

        $participantIds = Participant::where('user_id', $account->id)
            ->when($current, fn ($q) => $q->where('edition_id', $current->id))
            ->pluck('id');

        $submissionIds = Submission::whereIn('participant_id', $participantIds)
            ->where('revision_of', 0)->pluck('id');

        $slots = Slot::query()
            ->whereNotNull('starts_at')
            ->where(fn ($q) => $q
                ->whereIn('submission_id', $submissionIds)
                ->orWhereIn('participant_id', $participantIds))
            ->with(['submission', 'session', 'edition'])
            ->orderBy('starts_at')
            ->get();

        return $slots->map(fn (Slot $slot) => self::slotEvent($slot))->values()->all();
    }

    /**
     * Construit l'événement calendrier d'un créneau (réutilisable).
     *
     * @return array{uid:string, summary:string, start:Carbon, end:Carbon|null, location:string, description:string}
     */
    public static function slotEvent(Slot $slot): array
    {
        $title = $slot->submission?->title ?: ($slot->session_label ?: 'Présentation');

        return [
            'uid' => 'slot-'.$slot->id.'@ukcolloques',
            'summary' => 'Présentation — '.Str::limit((string) $title, 80),
            'start' => $slot->starts_at,
            'end' => $slot->ends_at,
            'location' => (string) ($slot->room ?: $slot->session?->room ?: ''),
            'description' => trim(($slot->session_label ? $slot->session_label.' · ' : '').(string) ($slot->edition->title ?? '')),
        ];
    }
}
