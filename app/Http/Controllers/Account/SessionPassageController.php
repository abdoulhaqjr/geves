<?php

namespace App\Http\Controllers\Account;

use App\Actions\ValidateCommunicationPassage;
use App\Http\Controllers\Controller;
use App\Meetings\MeetableRegistry;
use App\Models\EventSession;
use App\Models\Slot;
use App\Support\AttestationPolicy;
use App\Support\EventLock;
use App\Support\SessionPresence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * **Validation du passage d'une communication** par qui tient la séance.
 *
 * Deux portes sur le même geste : la salle de visio (« Valider le passage » de
 * l'onglet Communicateurs) et la page de modération, pour une séance tenue en
 * présentiel. D'où un contrôleur unique qui répond en JSON **ou** en
 * redirection - deux implémentations finiraient par diverger sur ce qui est,
 * justement, le déclencheur des attestations.
 *
 * ⚠️ Mêmes ayants droit que la présence (`SessionPresence::canManage`) :
 * modérateur **ou** rapporteur de la séance. Réserver le geste au seul
 * modérateur laisserait sans recours une séance qu'il a dû quitter.
 */
class SessionPassageController extends Controller
{
    /**
     * Page de validation des passages d'une séance, hors visio (comme la page de
     * présence). Modérateur **ou** rapporteur. Les boutons ne s'activent qu'une
     * fois la séance commencée - un modérateur ne valide pas un passage avant
     * l'heure (la route `session.passage` l'impose aussi côté serveur).
     */
    public function edit(EventSession $session): View
    {
        abort_unless(
            SessionPresence::canManage($session, Auth::guard('account')->user()),
            403,
        );

        $session->load('event.series', 'atelier');

        return view('account.passage.edit', [
            'session' => $session,
            'communications' => $session->scheduledCommunications(),
            'notStarted' => ! $session->hasStarted(),
            'frozen' => EventLock::frozen($session->event),
            // La page doit annoncer ce que « Valider » déclenche, avant le clic.
            'issuesOnPassage' => AttestationPolicy::issuesOnPassage($session->event),
            // Retour vers l'espace modérateur, et accès direct à la salle (onglet
            // réutilisé : on ne rouvre pas une seconde salle).
            'backRoute' => 'account.moderation',
            'meetingUrl' => $session->hasMeeting() ? route('session.meeting', $session) : null,
            'meetingTab' => MeetableRegistry::tabFor($session),
        ]);
    }

    public function store(
        Request $request,
        EventSession $session,
        Slot $slot,
        ValidateCommunicationPassage $action,
    ): JsonResponse|RedirectResponse {
        abort_unless(
            SessionPresence::canManage($session, Auth::guard('account')->user()),
            403,
        );

        // ⚠️ Le créneau doit appartenir À CETTE séance : sans cette vérification,
        // le modérateur d'une salle validerait le passage d'une communication
        // qui se tient dans une autre - et en enverrait les attestations.
        abort_unless((int) $slot->session_id === (int) $session->id, 404);
        abort_unless($slot->submission_id > 0, 422, 'Ce créneau ne porte aucune communication.');
        abort_unless($session->hasStarted(), 403, 'La séance n’a pas commencé.');
        abort_if(EventLock::frozen($session->event), 403, 'Cet événement est archivée.');

        $bilan = $action->handle($slot->load('submission.event', 'session'), Auth::guard('account')->user());

        $message = $bilan['already']
            ? 'Passage déjà validé.'
            : ($bilan['online']
                ? ($bilan['issued'] > 0
                    ? $bilan['issued'].' attestation(s) de communication émise(s).'
                    : 'Passage validé. Aucun auteur éligible à une attestation.')
                : 'Passage validé. Les attestations seront émises et imprimées par le secrétariat.');

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message] + $bilan);
        }

        return back()->with('ukc_status', $message);
    }
}
