<?php

namespace App\Http\Controllers;

use App\Meetings\LiveKitRoom;
use App\Models\EventSession;
use App\Models\SessionAttendance;
use App\Support\EditionMeetingConfig;
use App\Support\SessionPresence;
use App\Support\SessionRoster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Présence **depuis la salle de visio** : le modérateur ou le rapporteur pointe
 * sans quitter la séance.
 *
 * ⚠️ Faire l'aller-retour vers `/compte/seances/{id}/presence` demandait de
 * quitter la salle au moment précis où l'on regarde les gens. Les règles (qui
 * peut pointer, quand, sur qui) ne sont pas redéfinies ici : elles viennent de
 * {@see SessionPresence} et {@see SessionRoster}, comme pour la page.
 */
class SessionPresenceApiController extends Controller
{
    /** Liste à pointer : attendus, verdicts posés, et qui est dans la salle. */
    public function index(EventSession $session, LiveKitRoom $room): JsonResponse
    {
        $this->authorizeOfficer($session);

        // ⚠️ null ≠ salle vide : LiveKit peut être injoignable ou non configuré.
        // Le panneau doit pouvoir dire « on ne sait pas » plutôt que « personne ».
        // Même serveur que celui où la salle est ouverte (cf. EditionMeetingConfig).
        $connectes = EditionMeetingConfig::using($session->edition, fn () => $room->connectedParticipantIds($session));

        // Qui est DANS la salle est porté présent sans que le modérateur ait à le
        // faire - c'est la demande d'origine, et LiveKit permet enfin de la tenir
        // sur une connexion en cours plutôt que sur un clic.
        if ($connectes !== null) {
            SessionPresence::markConnected($session, $connectes);
        }

        $verdicts = SessionAttendance::where('session_id', $session->id)->get()
            ->keyBy('participant_id');

        $lignes = [];
        foreach (SessionRoster::for($session) as $ligne) {
            $participant = $ligne['participant'];

            if (! $participant) {
                continue; // non inscrit : rien à quoi rattacher une présence
            }

            $verdict = $verdicts->get($participant->id);

            $lignes[] = [
                'id' => $participant->id,
                'name' => $ligne['name'],
                'role' => $ligne['corresponding'] ? 'Auteur correspondant' : 'Co-auteur',
                'status' => (string) ($verdict->status ?? ''),
                'auto' => (bool) ($verdict?->isAutomatic() ?? false),
                'inRoom' => $connectes !== null && in_array($participant->id, $connectes, true),
            ];
        }

        return response()->json([
            'rows' => $lignes,
            'counts' => $session->presenceCounts(),
            'canWrite' => SessionPresence::open($session),
            // Dit explicitement si l'occupation de la salle est connue : le panneau
            // ne doit pas afficher « personne en salle » quand il ne sait pas.
            'roomKnown' => $connectes !== null,
        ]);
    }

    /** Pose (ou retire) un verdict sur une personne, depuis la salle. */
    public function store(Request $request, EventSession $session): JsonResponse
    {
        $this->authorizeOfficer($session);

        abort_unless(SessionPresence::open($session), 403, 'Le pointage de cette séance est fermé.');

        $data = $request->validate([
            'participant_id' => ['required', 'integer'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(SessionAttendance::STATUSES))],
        ]);

        $pose = SessionPresence::mark(
            $session,
            (int) $data['participant_id'],
            (string) ($data['status'] ?? ''),
            Auth::guard('account')->user(),
        );

        abort_unless($pose, 422, 'Cette personne n’est pas attendue à cette séance.');

        return response()->json(['ok' => true, 'counts' => $session->presenceCounts()]);
    }

    private function authorizeOfficer(EventSession $session): void
    {
        abort_unless(
            SessionPresence::canManage($session, Auth::guard('account')->user()),
            403,
        );
    }
}
