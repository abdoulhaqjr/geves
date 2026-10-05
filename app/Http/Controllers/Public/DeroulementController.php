<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

/**
 * Déroulement en direct d'un colloque : « ce qui se passe maintenant » par
 * atelier, programme du jour, indications de salles et annonces. Vue rendue par
 * contrôleur + Blade ; les modales (programme, infos, indications, annonces)
 * sont pilotées côté client par Alpine. Cible l'événement en cours (dates de
 * l'événement encadrant aujourd'hui).
 */
class DeroulementController extends Controller
{
    public function __invoke(): View
    {
        // Événement « en cours » = phase Ongoing (déroulement commencé), cf. §2.2.
        $event = Event::query()
            ->with('series')
            ->whereIn('status', ['open', 'closed', 'archived'])
            ->get()
            ->first(fn (Event $e) => $e->phase()->isOngoing());

        if (! $event) {
            return view('public.deroulement', ['event' => null]);
        }

        $now = Carbon::now();
        $sessions = $event->sessions()->with('atelier')->orderBy('starts_at')->get();

        $isNow = fn (EventSession $s): bool => $s->starts_at && $s->ends_at
            && $s->starts_at->lte($now) && $s->ends_at->gte($now);

        $settings = $event->settings ?? [];

        return view('public.deroulement', [
            'event' => $event,
            'now' => $now,
            'isNow' => $isNow,
            'liveNow' => $sessions->filter($isNow)->values(),
            'byDay' => $sessions->groupBy(fn (EventSession $s) => optional($s->session_day ?? $s->starts_at)->format('Y-m-d') ?? ''),
            'rooms' => $sessions->filter(fn (EventSession $s) => filled($s->room))->groupBy('room'),
            'announcements' => is_array($settings['announcements'] ?? null) ? $settings['announcements'] : [],
        ]);
    }
}
