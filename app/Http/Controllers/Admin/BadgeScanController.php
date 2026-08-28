<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pointage à l'accueil par scan du QR de badge (contrôleur + Blade + endpoints
 * JSON, sans Livewire). Le QR encode « {edition}-{participant} » ; la recherche
 * et le pointage se font par fetch depuis l'Alpine `badgeScan`, la caméra
 * restant gérée par scanner.js (html5-qrcode).
 */
class BadgeScanController extends Controller
{
    public function page(): View
    {
        return view('admin.badge-scan', [
            'checkedCount' => Participant::whereNotNull('checked_in_at')->count(),
            'totalCount' => Participant::count(),
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

        return response()->json(['found' => $this->present($participant)]);
    }

    /** Enregistre la présence du participant scanné. */
    public function checkIn(Participant $participant): JsonResponse
    {
        $already = $participant->isCheckedIn();
        if (! $already) {
            $participant->update(['checked_in_at' => now()]);
        }

        $fresh = $participant->fresh();

        return response()->json([
            'found' => $this->present($fresh),
            'message' => $fresh->full_name.($already ? ' était déjà pointé.' : ' — présence enregistrée.'),
            'newlyCheckedIn' => ! $already,
            'recentEntry' => ! $already ? ['name' => $fresh->full_name, 'at' => now()->format('H:i')] : null,
        ]);
    }

    /** @return array<string, mixed> */
    private function present(Participant $p): array
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
        ];
    }
}
