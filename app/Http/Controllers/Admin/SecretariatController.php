<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventSession;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Poste de travail du secrétariat (back-office, Blade lecture seule) : file des
 * rapports de séance à valider, séances en attente de rédaction, rapports
 * validés, et accès rapides aux tâches administratives. Scopé aux éditions
 * accessibles à l'utilisateur (capacité `editions_read`).
 */
class SecretariatController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $editions = $user->accessibleEditions('editions_read');
        $editionIds = $editions->pluck('id');

        /** @var Collection<int, EventSession> $sessions */
        $sessions = $editionIds->isEmpty()
            ? collect()
            : EventSession::whereIn('edition_id', $editionIds)
                ->whereNotNull('rapporteur_email')->where('rapporteur_email', '!=', '')
                ->with(['edition.colloque', 'atelier', 'report'])
                ->orderBy('starts_at')->orderBy('id')->get();

        // Files : à valider (soumis, non validé), en attente (commencée mais pas
        // de rapport soumis), validés, à venir (séance pas encore commencée).
        $toValidate = $sessions->filter(fn (EventSession $s) => $s->report
            && $s->report->isSubmitted());
        $awaiting = $sessions->filter(fn (EventSession $s) => $s->hasStarted()
            && (! $s->report || $s->report->status === 'draft'));
        $validated = $sessions->filter(fn (EventSession $s) => $s->report
            && $s->report->isValidated());
        $upcoming = $sessions->filter(fn (EventSession $s) => ! $s->hasStarted());

        $pendingPayments = $editionIds->isEmpty() ? 0
            : Payment::whereIn('edition_id', $editionIds)->where('status', 'pending')->count();

        return view('admin.secretariat', [
            'editions' => $editions,
            'toValidate' => $toValidate->values(),
            'awaiting' => $awaiting->values(),
            'validated' => $validated->sortByDesc(fn (EventSession $s) => $s->report?->validated_at)->take(6)->values(),
            'stats' => [
                'to_validate' => $toValidate->count(),
                'validated' => $validated->count(),
                'awaiting' => $awaiting->count(),
                'upcoming' => $upcoming->count(),
                'pending_payments' => $pendingPayments,
            ],
        ]);
    }
}
