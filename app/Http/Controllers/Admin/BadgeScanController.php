<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\SessionAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Pointage par scan du QR de badge (contrôleur + Blade + endpoints JSON, sans
 * Livewire). Le QR encode « {edition}-{participant} » ; la recherche et le
 * pointage se font par fetch depuis l'Alpine `badgeScan`, la caméra restant
 * gérée par scanner.js (html5-qrcode).
 *
 * Deux modes : « accueil » (présence globale au colloque, `checked_in_at`) et
 * « séance » (présence effective à une session précise → SessionAttendance),
 * cette dernière servant à conditionner l'attestation de participation.
 */
class BadgeScanController extends Controller
{
    public function page(): View
    {
        // Séances datées, proposées au sélecteur « mode séance » (jour courant en tête).
        $sessions = EventSession::query()
            ->with('edition')
            ->whereNotNull('session_day')
            ->orderByDesc('session_day')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (EventSession $s) => [
                'id' => (int) $s->id,
                'label' => trim(($s->label ?: 'Séance').' — '.($s->session_day?->translatedFormat('D j M') ?? '')
                    .($s->room ? ' · '.$s->room : '')),
                'edition' => $s->edition?->title ?: ('Édition '.$s->edition_id),
            ])
            ->values();

        return view('admin.badge-scan', [
            'checkedCount' => Participant::whereNotNull('checked_in_at')->count(),
            'totalCount' => Participant::count(),
            'sessions' => $sessions,
        ]);
    }

    /** Recherche un participant depuis un code scanné/saisi. */
    public function lookup(Request $request): JsonResponse
    {
        $raw = trim((string) $request->input('code'));
        if ($raw === '') {
            return response()->json(['found' => null, 'error' => null]);
        }

        // Format du QR : « {editionId}-{participantId} » ; on tolère un id seul.
        $pid = null;
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $raw, $m)) {
            $pid = (int) $m[2];
        } elseif (ctype_digit($raw)) {
            $pid = (int) $raw;
        }

        $participant = $pid ? Participant::with('edition')->find($pid) : null;

        if (! $participant) {
            return response()->json(['found' => null, 'error' => 'Badge non reconnu : '.$raw]);
        }

        return response()->json(['found' => $this->present($participant, $this->sessionFrom($request))]);
    }

    /**
     * Enregistre la présence du participant scanné.
     * En « mode séance » (session_id fourni), enregistre aussi la présence à la
     * séance ; la présence à une séance vaut présence à l'accueil.
     */
    public function checkIn(Participant $participant, Request $request): JsonResponse
    {
        $session = $this->sessionFrom($request);

        // Mode séance : la séance doit relever de la même édition que le participant.
        if ($session && (int) $session->edition_id !== (int) $participant->edition_id) {
            return response()->json([
                'found' => $this->present($participant->fresh(), $session),
                'error' => $participant->full_name.' n’est pas inscrit à l’édition de cette séance.',
            ], 422);
        }

        $wasCheckedIn = $participant->isCheckedIn();
        if (! $wasCheckedIn) {
            $participant->update(['checked_in_at' => now()]);
        }

        $newlyInSession = false;
        if ($session) {
            $attendance = SessionAttendance::firstOrCreate(
                ['session_id' => $session->id, 'participant_id' => $participant->id],
                ['edition_id' => $participant->edition_id, 'scanned_by' => Auth::id(), 'scanned_at' => now()],
            );
            $newlyInSession = $attendance->wasRecentlyCreated;
        }

        $fresh = $participant->fresh();
        $name = $fresh->full_name;

        if ($session) {
            $message = $newlyInSession
                ? $name.' — présence enregistrée pour « '.$session->label.' ».'
                : $name.' était déjà pointé à cette séance.';
            $newly = $newlyInSession;
        } else {
            $message = $name.($wasCheckedIn ? ' était déjà pointé.' : ' — présence enregistrée.');
            $newly = ! $wasCheckedIn;
        }

        return response()->json([
            'found' => $this->present($fresh, $session),
            'message' => $message,
            'newlyCheckedIn' => $newly,
            'recentEntry' => $newly ? ['name' => $name, 'at' => now()->format('H:i')] : null,
        ]);
    }

    /** Séance ciblée par le « mode séance », si valide. */
    private function sessionFrom(Request $request): ?EventSession
    {
        $id = (int) $request->input('session_id');

        return $id > 0 ? EventSession::find($id) : null;
    }

    /** @return array<string, mixed> */
    private function present(Participant $p, ?EventSession $session = null): array
    {
        return [
            'id' => (int) $p->id,
            'name' => $p->full_name,
            'role' => $p->roleLabel(),
            'institution' => (string) $p->institution,
            'email' => (string) $p->email,
            'paid' => $p->payments()->where('status', 'paid')->exists(),
            'checkedIn' => $p->isCheckedIn(),
            'checkedAt' => $p->checked_in_at?->translatedFormat('d/m H:i'),
            'editionTitle' => $p->edition?->title,
            'inSession' => $session
                ? SessionAttendance::where('session_id', $session->id)->where('participant_id', $p->id)->exists()
                : null,
            'sessionMismatch' => $session ? (int) $session->edition_id !== (int) $p->edition_id : false,
        ];
    }
}
