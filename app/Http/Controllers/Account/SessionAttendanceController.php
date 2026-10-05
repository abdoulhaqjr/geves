<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Meetings\MeetableRegistry;
use App\Models\EventSession;
use App\Models\SessionAttendance;
use App\Models\SessionReport;
use App\Support\EventLock;
use App\Support\SessionRoster;
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
 * ⚠️ On ne pointe que les **auteurs des communications de la séance**
 * ({@see SessionRoster}), jamais l'effectif de l'événement : un modérateur constate
 * ce qu'il a sous les yeux, et une liste de plusieurs centaines d'inscrits lui
 * demanderait de se prononcer sur des gens absents de sa salle.
 *
 * Ce n'est pas non plus un scan de badge (retiré) : on appelle des gens à la
 * voix, dans n'importe quel ordre. D'où un enregistrement **incrémental et non
 * destructif** - voir `update()`.
 */
class SessionAttendanceController extends Controller
{
    public function edit(EventSession $session): View
    {
        $this->authorizeSessionOfficer($session);

        $session->load('event.series', 'atelier');
        $report = SessionReport::where('session_id', $session->id)->first();

        return view('account.attendance.edit', [
            'session' => $session,
            // ⚠️ Retour vers l'espace que la personne possède VRAIMENT : un
            // rapporteur qui n'anime rien n'a pas de « séances à animer », et le
            // renvoyer là lui montrerait une page vide.
            'backRoute' => $this->isModerator($session) ? 'account.moderation' : 'account.reports',
            // ⚠️ Retour DIRECT dans la salle : on vient d'y pointer des gens, et
            // repasser par la liste des séances pour y revenir est un détour que
            // personne ne fait pendant qu'il anime.
            'meetingUrl' => $session->hasMeeting() ? route('session.meeting', $session) : null,
            'meetingTab' => MeetableRegistry::tabFor($session),
            'roster' => SessionRoster::for($session),
            'verdicts' => SessionAttendance::where('session_id', $session->id)
                ->pluck('status', 'participant_id'),
            // Ceux que la visio a portés présents : le modérateur doit voir ce
            // qu'il n'a pas posé lui-même avant d'endosser le chiffre.
            'viaVisio' => SessionAttendance::where('session_id', $session->id)
                ->where('source', SessionAttendance::SOURCE_MEETING)
                ->pluck('participant_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
            'counts' => $session->presenceCounts(),
            'canWrite' => $this->canWrite($session, $report),
            'notStarted' => ! $session->hasStarted(),
            'frozen' => EventLock::frozen($session->event),
            'validated' => $report?->isValidated() ?? false,
        ]);
    }

    /**
     * Enregistre les verdicts **soumis**, et eux seuls.
     *
     * ⚠️ Non destructif par construction : un participant absent du formulaire
     * garde son verdict. On pointe par petits lots pendant la séance - une
     * sauvegarde qui remettrait à zéro ce qui n'est pas à l'écran effacerait le
     * travail du lot précédent.
     */
    public function update(Request $request, EventSession $session): RedirectResponse
    {
        $this->authorizeSessionOfficer($session);

        EventLock::guard($session->event);
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

        // ⚠️ Un identifiant posté n'est pas une autorisation. La garde est la
        // liste de la SÉANCE, pas celle de l'événement : un modérateur n'a pas à
        // pouvoir se prononcer sur quelqu'un qui ne passe pas chez lui.
        $permis = SessionRoster::participantIds($session)
            ->intersect(array_map('intval', array_keys($data['presence'] ?? [])));

        $user = Auth::guard('account')->user();
        $touches = 0;

        foreach ($permis as $participantId) {
            $statut = (string) ($data['presence'][$participantId] ?? '');

            if ($statut === '') {
                // Retour à « non pointé » : on supprime la ligne plutôt que de
                // garder un verdict vide, qui compterait comme un pointage.
                $touches += SessionAttendance::where('session_id', $session->id)
                    ->where('participant_id', $participantId)->delete();

                continue;
            }

            // ⚠️ `source` repasse à « manual » : à partir du moment où quelqu'un
            // enregistre la ligne, elle est de sa main - même s'il ne fait que
            // confirmer ce que la visio avait posé. C'est lui qui l'endosse.
            SessionAttendance::updateOrCreate(
                ['session_id' => $session->id, 'participant_id' => $participantId],
                [
                    'status' => $statut,
                    'source' => SessionAttendance::SOURCE_MANUAL,
                    'marked_by' => $user?->id,
                    'marked_at' => now(),
                ],
            );
            $touches++;
        }

        $counts = $session->presenceCounts();

        return redirect()->route('account.attendance.edit', $session)
            ->with('ukc_status', trans_choice(
                '{0}Aucun changement de présence.|[1,*]Présence enregistrée : :present présent(s) sur :expected attendu(s).',
                $touches,
                ['present' => $counts['present'], 'expected' => $counts['expected']],
            ));
    }

    /** Le pointage est-il ouvert à l'écriture ? */
    private function canWrite(EventSession $session, ?SessionReport $report): bool
    {
        return $session->hasStarted()
            && ! ($report?->isValidated() ?? false)
            && ! EventLock::frozen($session->event);
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
