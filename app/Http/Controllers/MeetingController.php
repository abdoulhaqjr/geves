<?php

namespace App\Http\Controllers;

use App\Enums\MeetingRole;
use App\Meetings\Contracts\Meetable;
use App\Meetings\LiveKitEgress;
use App\Models\EventSession;
use App\Models\Formation;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Page « Rejoindre la visio » du module visio, commune à tous les objets
 * `Meetable` : session du programme ET formation. Elle embarque la salle du
 * fournisseur (Jitsi en iframe, LiveKit via le lecteur maison, ou lien externe)
 * selon le rôle calculé du visiteur. Aucune connexion imposée en amont : la page
 * s'adapte (invite à se connecter, indique si la visio n'est pas en cours,
 * propose le replay).
 */
class MeetingController extends Controller
{
    /** Visio d'une session du programme. */
    public function join(EventSession $session): View
    {
        $isStaff = Auth::guard('web')->check();
        $account = Auth::guard('account')->user();
        $role = $session->meetingRoleFor($account, $isStaff);

        $extra = [];
        if ($role->canJoin()) {
            $extra = [
                'communications' => $session->scheduledCommunications(),
                'canRecord' => $role === MeetingRole::Moderator && app(LiveKitEgress::class)->isConfigured(),
                'egress' => [
                    'start' => route('session.egress.start', $session),
                    'stop' => route('session.egress.stop', $session),
                ],
            ];
        }

        return $this->room($session, [
            'label' => (string) $session->label,
            'editionTitle' => (string) ($session->edition->title ?? ''),
            'startsAt' => $session->starts_at,
            'recordingUrl' => $session->hasRecording() ? (string) $session->recording_url : null,
            // « Quitter » ramène toujours à la page de déroulement du colloque.
            'backUrl' => route('public.deroulement'),
            'engagementUrl' => $role->canJoin() ? route('session.engagement', $session) : null,
        ], $extra);
    }

    /**
     * Visio d'une formation : même page, même lecteur, même module. L'accès est
     * calculé par Formation::meetingRoleFor (formateurs = modérateurs, inscrits
     * éligibles = participants ; pas d'accès public).
     */
    public function joinFormation(Formation $formation): View
    {
        return $this->room($formation, [
            'label' => (string) $formation->title,
            'editionTitle' => (string) ($formation->edition->title ?? ''),
            'startsAt' => $formation->starts_at,
            'recordingUrl' => null,
            'backUrl' => route('account.formations'),
            'engagementUrl' => null,
        ]);
    }

    /**
     * Rendu commun de la salle pour un objet `Meetable`.
     *
     * @param  array{label: string, editionTitle: string, startsAt: Carbon|null, recordingUrl: string|null, backUrl: string, engagementUrl: string|null}  $ctx
     * @param  array<string, mixed>  $liveKitExtra  Enrichissements du lecteur LiveKit propres au type.
     */
    private function room(Meetable&Model $meetable, array $ctx, array $liveKitExtra = []): View
    {
        $account = Auth::guard('account')->user();
        $isStaff = Auth::guard('web')->check();

        $role = $meetable->meetingRoleFor($account, $isStaff);
        $canJoinNow = $meetable->isLive() && $role->canJoin();

        // Selon le fournisseur : Jitsi (IFrame API), LiveKit (lecteur inline
        // maison), ou lien externe (nouvel onglet).
        $embed = $canJoinNow ? $meetable->meetingEmbedConfig($account, $isStaff) : null;
        $liveKit = $canJoinNow ? $meetable->liveKitEmbedConfig($account, $isStaff) : null;
        $externalUrl = ($canJoinNow && ! $embed && ! $liveKit) ? $meetable->joinUrl($account, $isStaff) : null;

        // Enrichit la config du lecteur LiveKit (lue par le bundle JS).
        if ($liveKit) {
            $liveKit['backUrl'] = $ctx['backUrl'];
            $liveKit['sessionLabel'] = $ctx['label'];
            $liveKit['editionTitle'] = $ctx['editionTitle'];
            $liveKit = array_merge($liveKit, $liveKitExtra);
        }

        return view('meeting.join', [
            'label' => $ctx['label'],
            'editionTitle' => $ctx['editionTitle'],
            'startsAt' => $ctx['startsAt'],
            'recordingUrl' => $ctx['recordingUrl'],
            'backUrl' => $ctx['backUrl'],
            'engagementUrl' => $ctx['engagementUrl'],
            'isLive' => $meetable->isLive(),
            'statusLabel' => $meetable->meetingStatusEnum()->label(),
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
