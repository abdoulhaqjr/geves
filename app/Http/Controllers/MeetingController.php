<?php

namespace App\Http\Controllers;

use App\Models\EventSession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Page « Rejoindre la visio » d'une session : embarque la salle du fournisseur
 * (Jitsi) via une iframe, selon le rôle calculé du visiteur. L'accès est géré
 * par EventSession (public → tous ; participants → inscrits ; staff/modérateur/
 * présentateur…). Aucune connexion imposée en amont : la page s'adapte (invite à
 * se connecter, indique si la visio n'est pas en cours, propose le replay).
 */
class MeetingController extends Controller
{
    public function join(EventSession $session): View
    {
        $account = Auth::guard('account')->user();
        $isStaff = Auth::guard('web')->check();

        $role = $session->meetingRoleFor($account, $isStaff);
        $canJoinNow = $session->isLive() && $role->canJoin();

        return view('meeting.join', [
            'session' => $session,
            'role' => $role,
            'isStaff' => $isStaff,
            'isGuest' => ! $account && ! $isStaff,
            'joinUrl' => $canJoinNow ? $session->joinUrl($account, $isStaff) : null,
        ]);
    }
}
