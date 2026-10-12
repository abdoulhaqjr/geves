<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Slot;
use App\Support\CalendarExport;
use App\Support\CurrentEvent;
use App\Support\ParticipantDashboard;
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
        $ics = $calendar->ics($events, 'Mon agenda - '.config('app.name'));

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

        // ⚠️ **Mêmes créneaux que « Mon prochain passage »** : le tableau de bord
        // et l'export calendrier décidaient chacun de leur côté de ce qui compte
        // comme un passage. Depuis qu'un co-auteur y figure (il co-présente), un
        // bouton « Ajouter à mon agenda » posé sous un créneau que le .ics
        // n'exportait pas aurait rendu un fichier amputé, sans rien dire.
        $tdb = new ParticipantDashboard($account, CurrentEvent::current($account));

        return $tdb->passages()->map(fn (Slot $slot) => self::slotEvent($slot))->values()->all();
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
            'summary' => 'Présentation - '.Str::limit((string) $title, 80),
            'start' => $slot->starts_at,
            'end' => $slot->ends_at,
            'location' => (string) ($slot->room ?: $slot->session?->room ?: ''),
            'description' => trim(($slot->session_label ? $slot->session_label.' - ' : '').(string) ($slot->event->title ?? '')),
        ];
    }
}
