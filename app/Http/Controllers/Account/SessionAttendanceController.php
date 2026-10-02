<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\SessionAttendance;
use App\Models\SessionReport;
use App\Support\EditionLock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Présence d'une séance, tenue pendant la séance par son **modérateur ou son
 * rapporteur** - les deux, parce que c'est l'un ou l'autre qui a la salle sous
 * les yeux, et que l'organisation ne répartit pas ce geste de la même façon
 * partout.
 *
 * ⚠️ Ce n'est pas un scan de badge (retiré) : on pointe des gens appelés à la
 * voix, dans n'importe quel ordre, par petits lots. D'où un enregistrement
 * **incrémental et non destructif** - voir `update()`.
 */
class SessionAttendanceController extends Controller
{
    /** Taille d'une page de liste : au-delà, on cherche plutôt qu'on défile. */
    private const PAR_PAGE = 60;

    public function edit(Request $request, EventSession $session): View
    {
        $this->authorizeSessionOfficer($session);

        $session->load('edition.colloque', 'atelier');
        $recherche = trim((string) $request->query('q', ''));

        $participants = Participant::where('edition_id', $session->edition_id)
            ->when($recherche !== '', function ($q) use ($recherche) {
                $motif = '%'.$recherche.'%';
                $q->where(fn ($w) => $w->where('last_name', 'like', $motif)
                    ->orWhere('first_name', 'like', $motif)
                    ->orWhere('email', 'like', $motif)
                    ->orWhere('institution', 'like', $motif));
            })
            ->orderBy('last_name')->orderBy('first_name')->orderBy('id')
            ->paginate(self::PAR_PAGE)
            ->withQueryString();

        $report = SessionReport::where('session_id', $session->id)->first();

        return view('account.attendance.edit', [
            'session' => $session,
            // ⚠️ Retour vers l'espace que la personne possède VRAIMENT : un
            // rapporteur qui n'anime rien n'a pas de « séances à animer », et le
            // renvoyer là lui montrerait une page vide.
            'backRoute' => $this->isModerator($session) ? 'account.moderation' : 'account.reports',
            'participants' => $participants,
            'recherche' => $recherche,
            // Verdicts déjà posés, par participant_id - la liste est paginée, pas
            // le comptage : « 12 présents » doit porter sur la séance entière.
            'verdicts' => SessionAttendance::where('session_id', $session->id)
                ->pluck('status', 'participant_id'),
            'counts' => $session->presenceCounts(),
            'canWrite' => $this->canWrite($session, $report),
            'notStarted' => ! $session->hasStarted(),
            'frozen' => EditionLock::frozen($session->edition),
            'validated' => $report?->isValidated() ?? false,
        ]);
    }

    /**
     * Enregistre les verdicts **soumis**, et eux seuls.
     *
     * ⚠️ Non destructif par construction : un participant absent du formulaire
     * (page suivante, recherche en cours) garde son verdict. Un pointage se fait
     * par petits lots pendant la séance - une sauvegarde qui remettrait à zéro ce
     * qui n'est pas à l'écran effacerait le travail du lot précédent.
     */
    public function update(Request $request, EventSession $session): RedirectResponse
    {
        $this->authorizeSessionOfficer($session);

        EditionLock::guard($session->edition);
        abort_unless($session->hasStarted(), 403, 'Le pointage ouvrira au début de la séance.');

        $report = SessionReport::where('session_id', $session->id)->first();
        abort_if(
            $report?->isValidated() ?? false,
            403,
            'Le rapport de séance a été validé par le secrétariat : la présence n’est plus modifiable.'
        );

        $data = $request->validate([
            'presence' => ['array'],
            'presence.*' => ['nullable', 'in:'.implode(',', array_keys(SessionAttendance::STATUSES))],
        ]);

        // ⚠️ Un identifiant posté n'est pas une autorisation : on n'accepte que
        // des participants de CETTE édition (le formulaire est public côté client).
        $permis = Participant::where('edition_id', $session->edition_id)
            ->whereIn('id', array_keys($data['presence'] ?? []))
            ->pluck('id')
            ->all();

        $user = Auth::guard('account')->user();
        $poses = 0;
        $effaces = 0;

        foreach ($permis as $participantId) {
            $statut = (string) ($data['presence'][$participantId] ?? '');

            if ($statut === '') {
                // Retour à « non pointé » : on supprime la ligne plutôt que de
                // garder un verdict vide, qui compterait comme un pointage.
                $effaces += SessionAttendance::where('session_id', $session->id)
                    ->where('participant_id', $participantId)->delete();

                continue;
            }

            SessionAttendance::updateOrCreate(
                ['session_id' => $session->id, 'participant_id' => $participantId],
                ['status' => $statut, 'marked_by' => $user?->id, 'marked_at' => now()],
            );
            $poses++;
        }

        $counts = $session->presenceCounts();

        return redirect()->route('account.attendance.edit', ['session' => $session->id, 'q' => $request->input('q')])
            ->with('ukc_status', trans_choice(
                '{0}Aucun changement de présence.|[1,*]Présence enregistrée : :present présent(s) sur :expected inscrit(s).',
                $poses + $effaces,
                ['present' => $counts['present'], 'expected' => $counts['expected']],
            ));
    }

    /** Le pointage est-il ouvert à l'écriture ? */
    private function canWrite(EventSession $session, ?SessionReport $report): bool
    {
        return $session->hasStarted()
            && ! ($report?->isValidated() ?? false)
            && ! EditionLock::frozen($session->edition);
    }

    /**
     * Modérateur **ou** rapporteur assigné de la séance (rapprochés par e-mail,
     * comme partout ailleurs dans les espaces de fonction).
     */
    private function authorizeSessionOfficer(EventSession $session): void
    {
        abort_unless($this->isModerator($session) || $this->isRapporteur($session), 403);
    }

    private function isModerator(EventSession $session): bool
    {
        return $this->matchesCurrentAccount($session->moderator_email);
    }

    private function isRapporteur(EventSession $session): bool
    {
        return $this->matchesCurrentAccount($session->rapporteur_email);
    }

    /** Rapprochement par e-mail, comme dans tous les espaces de fonction. */
    private function matchesCurrentAccount(?string $email): bool
    {
        $compte = mb_strtolower((string) Auth::guard('account')->user()?->email);

        return $compte !== '' && filled($email) && mb_strtolower($email) === $compte;
    }
}
