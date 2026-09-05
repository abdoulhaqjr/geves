<?php

namespace App\Http\Controllers;

use App\Enums\MeetingRole;
use App\Meetings\LiveKitEgress;
use App\Models\EventSession;
use App\Support\Settings;
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

        // Selon le fournisseur : Jitsi (IFrame API), LiveKit (lecteur inline
        // maison), ou lien externe (nouvel onglet).
        $embed = $canJoinNow ? $session->meetingEmbedConfig($account, $isStaff) : null;
        $liveKit = $canJoinNow ? $session->liveKitEmbedConfig($account, $isStaff) : null;
        $externalUrl = ($canJoinNow && ! $embed && ! $liveKit) ? $session->joinUrl($account, $isStaff) : null;

        // Enrichit la config du lecteur LiveKit (lue par le bundle JS) : retour,
        // libellés, communications programmées, droit d'enregistrement.
        if ($liveKit) {
            // « Quitter » ramène toujours à la page de déroulement du colloque.
            $liveKit['backUrl'] = route('public.deroulement');
            $liveKit['sessionLabel'] = (string) $session->label;
            $liveKit['editionTitle'] = (string) ($session->edition->title ?? '');
            $liveKit['communications'] = $session->scheduledCommunications();
            $liveKit['canRecord'] = $role === MeetingRole::Moderator && app(LiveKitEgress::class)->isConfigured();
            $liveKit['egress'] = [
                'start' => route('session.egress.start', $session),
                'stop' => route('session.egress.stop', $session),
            ];
        }

        return view('meeting.join', [
            'session' => $session,
            'role' => $role,
            'isStaff' => $isStaff,
            'isGuest' => ! $account && ! $isStaff,
            'embed' => $embed,
            'liveKit' => $liveKit,
            'externalUrl' => $externalUrl,
            // Arrière-plans virtuels proposés (configurés dans Réglages · Visio).
            'backgrounds' => $this->backgrounds(),
        ]);
    }

    /**
     * Liste d'URL d'images d'arrière-plan configurées par l'admin (une par ligne
     * dans le réglage meetings.backgrounds). L'utilisateur peut aussi charger la
     * sienne depuis la salle.
     *
     * @return list<string>
     */
    private function backgrounds(): array
    {
        return collect(preg_split('/\r?\n/', (string) Settings::get('meetings.backgrounds', '')))
            ->map(fn ($l) => trim($l))
            ->filter(fn ($l) => $l !== '' && preg_match('#^https?://#i', $l))
            ->values()
            ->all();
    }
}
