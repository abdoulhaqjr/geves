<?php

namespace App\Http\Controllers;

use App\Enums\MeetingRole;
use App\Meetings\LiveKitEgress;
use App\Models\EventSession;
use App\Support\EditionMeetingConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Enregistrement d'une session via LiveKit Egress (RoomComposite). Réservé au
 * modérateur (staff ou modérateur assigné). La logique d'appel à l'API Egress
 * vit dans App\Meetings\LiveKitEgress.
 */
class EgressController extends Controller
{
    public function start(EventSession $session): JsonResponse
    {
        return $this->run($session, true);
    }

    public function stop(EventSession $session): JsonResponse
    {
        return $this->run($session, false);
    }

    private function run(EventSession $session, bool $start): JsonResponse
    {
        $account = Auth::guard('account')->user();
        $isStaff = Auth::guard('web')->check();

        if ($session->meetingRoleFor($account, $isStaff) !== MeetingRole::Moderator) {
            return response()->json(['ok' => false, 'message' => 'Réservé au modérateur.'], 403);
        }

        $egress = app(LiveKitEgress::class);

        try {
            // ⚠️ Sous les réglages de l'ÉDITION : une édition qui tient ses salles sur
            // son propre serveur doit y enregistrer aussi. Sans cela, l'Egress
            // s'adresserait au serveur de la plateforme pour une salle qui n'y est pas.
            $result = EditionMeetingConfig::using(
                $session->edition,
                fn () => $start ? $egress->start($session) : $egress->stop($session),
            );
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true] + $result);
    }
}
