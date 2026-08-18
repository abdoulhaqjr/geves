<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\EventSession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

/**
 * Déroulement en direct d'un colloque : « ce qui se passe maintenant » par
 * atelier, programme du jour, indications de salles et annonces. Vue rendue par
 * contrôleur + Blade ; les modales (programme, infos, indications, annonces)
 * sont pilotées côté client par Alpine. Cible l'édition en cours (dates de
 * l'événement encadrant aujourd'hui).
 */
class DeroulementController extends Controller
{
    public function __invoke(): View
    {
        // Édition « en cours » = phase Ongoing (déroulement commencé), cf. §2.2.
        $edition = Edition::query()
            ->with('colloque')
            ->whereIn('status', ['open', 'closed', 'archived'])
            ->get()
            ->first(fn (Edition $e) => $e->phase()->isOngoing());

        if (! $edition) {
            return view('public.deroulement', ['edition' => null]);
        }

        $now = Carbon::now();
        $sessions = $edition->sessions()->with('atelier')->orderBy('starts_at')->get();

        $isNow = fn (EventSession $s): bool => $s->starts_at && $s->ends_at
            && $s->starts_at->lte($now) && $s->ends_at->gte($now);

        $settings = $edition->settings ?? [];

        return view('public.deroulement', [
            'edition' => $edition,
            'now' => $now,
            'isNow' => $isNow,
            'liveNow' => $sessions->filter($isNow)->values(),
            'byDay' => $sessions->groupBy(fn (EventSession $s) => optional($s->session_day ?? $s->starts_at)->format('Y-m-d') ?? ''),
            'rooms' => $sessions->filter(fn (EventSession $s) => filled($s->room))->groupBy('room'),
            'announcements' => is_array($settings['announcements'] ?? null) ? $settings['announcements'] : [],
        ]);
    }
}
