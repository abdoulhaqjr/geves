<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\EventSession;
use App\Models\SessionReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Espace rapporteur (côté participant) : « Mes séances à rapporter ». Le
 * rapporteur assigné rédige le rapport de séance (synthèse, points saillants,
 * recommandations), en brouillon puis soumis au comité.
 */
class SessionReportController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('account')->user();

        $sessions = $user->sessionsAsRapporteur()
            ->with(['edition.colloque', 'atelier', 'report'])
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        return view('account.reports.index', [
            'sessions' => $sessions,
        ]);
    }

    public function edit(EventSession $session): View
    {
        $this->authorizeRapporteur($session);

        $session->load('edition.colloque', 'atelier');
        $report = SessionReport::firstOrNew(['session_id' => $session->id]);

        return view('account.reports.edit', [
            'session' => $session,
            'report' => $report,
            'communications' => $session->scheduledCommunications(),
            'canWrite' => $session->hasStarted() && ! $report->isValidated(),
            'locked' => $report->isValidated(),
            'notStarted' => ! $session->hasStarted(),
        ]);
    }

    public function update(Request $request, EventSession $session): RedirectResponse
    {
        $this->authorizeRapporteur($session);

        // Fenêtre de rédaction : seulement une fois la séance commencée, et tant
        // que le secrétariat n'a pas validé/verrouillé le rapport.
        $report = SessionReport::firstOrNew(['session_id' => $session->id]);
        abort_unless($session->hasStarted(), 403, 'La rédaction du rapport ouvrira au début de la séance.');
        abort_if($report->isValidated(), 403, 'Le rapport a été validé par le secrétariat et n’est plus modifiable.');

        $data = $request->validate([
            'summary' => ['nullable', 'string', 'max:20000'],
            'highlights' => ['nullable', 'string', 'max:5000'],
            'attendance' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'action' => ['required', 'in:draft,submit'],
        ]);

        $user = Auth::guard('account')->user();
        $submit = $data['action'] === 'submit';

        $report->edition_id = $session->edition_id;
        $report->reported_by = $user->id;
        $report->summary = ($data['summary'] ?? null) ?: null;
        $report->highlights = ($data['highlights'] ?? null) ?: null;
        $report->attendance = $data['attendance'] ?? null;
        $report->status = $submit ? 'submitted' : 'draft';
        $report->submitted_at = $submit ? now() : null;
        $report->save();

        return redirect()->route('account.reports')->with(
            'ukc_status',
            $submit ? 'Rapport de séance soumis. Merci !' : 'Brouillon enregistré.'
        );
    }

    /** Seul le rapporteur assigné (par e-mail) accède au rapport de la séance. */
    private function authorizeRapporteur(EventSession $session): void
    {
        $user = Auth::guard('account')->user();
        abort_unless(
            $user !== null
            && filled($session->rapporteur_email)
            && mb_strtolower((string) $session->rapporteur_email) === mb_strtolower((string) $user->email),
            403
        );
    }
}
