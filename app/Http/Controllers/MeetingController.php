<?php

namespace App\Http\Controllers;

use App\Enums\MeetingRole;
use App\Meetings\Contracts\Meetable;
use App\Meetings\LiveKitEgress;
use App\Meetings\MeetableRegistry;
use App\Meetings\MeetingProviderRegistry;
use App\Meetings\Providers\LiveKitProvider;
use App\Models\EventSession;
use App\Models\Formation;
use App\Models\MeetingMessage;
use App\Support\AttestationPolicy;
use App\Support\EventMeetingConfig;
use App\Support\SessionPresence;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
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
        // ⚠️ Réconciliation paresseuse : à l'entrée, une séance « programmée » dont
        // l'horaire approche passe « en direct », et une séance passée bascule
        // « terminée » (accès coupé) - même entre deux passages de la commande
        // planifiée, ou sans planificateur.
        $session->syncMeetingStatus();

        $isStaff = Auth::guard('web')->check();
        $account = Auth::guard('account')->user();
        $role = $session->meetingRoleFor($account, $isStaff);

        // ⚠️ Entrer dans la visio VAUT présence : le modérateur n'a pas à pointer
        // à la main quelqu'un qui est déjà dans la salle. Ce qu'on enregistre est
        // une entrée depuis GEVES, pas une occupation en temps réel (aucun
        // fournisseur ne la donne ici) - cf. App\Support\SessionPresence.
        if ($session->isLive() && $role->canJoin()) {
            SessionPresence::markFromMeeting($session, $account);
        }

        $extra = [];
        if ($role->canJoin()) {
            $extra = [
                'communications' => $session->scheduledCommunications(),
                'canRecord' => $role === MeetingRole::Moderator && EventMeetingConfig::using(
                    $session->event,
                    fn () => app(LiveKitEgress::class)->isConfigured(),
                ),
                // ⚠️ Présence TENUE DANS LA SALLE : l'aller-retour vers la page de
                // présence obligeait à quitter la séance au moment précis où l'on
                // regarde les gens. Même règle que la page sur qui peut pointer -
                // le staff anime la salle mais ne tient pas la présence.
                'presence' => SessionPresence::canManage($session, $account) ? [
                    'list' => route('session.presence.list', $session),
                    'mark' => route('session.presence.mark', $session),
                    'page' => route('account.attendance.edit', $session),
                ] : null,
                // ⚠️ Valider le passage d'une communication ÉMET les attestations de
                // ses auteurs : la salle ne se contente plus de l'annoncer sur le
                // canal de données, elle le fait constater par le serveur. `SLOT` est
                // remplacé par le créneau au moment du clic (même procédé que le chat).
                'passage' => SessionPresence::canManage($session, $account)
                    ? route('session.passage', [$session, 'SLOT'])
                    : null,
                // Mode de délivrance : la salle doit dire ce que le clic déclenche.
                'attestationsEnLigne' => AttestationPolicy::issuesOnPassage($session->event),
                'egress' => [
                    'start' => route('session.egress.start', $session),
                    'stop' => route('session.egress.stop', $session),
                ],
                // ⚠️ Les échanges (questions votées, sondages) se tiennent DANS la
                // salle : ils accompagnent la séance, et le lien qui y menait faisait
                // SORTIR de la visio pour y participer - on quittait ce dont on
                // voulait parler. Ce sont les mêmes points d'entrée que la page
                // autonome : aucune règle n'est réécrite ici, le serveur revérifie
                // qui peut poser, voter et modérer.
                'engagement' => [
                    'questions' => route('session.engagement.questions', $session),
                    'ask' => route('session.engagement.ask', $session),
                    'polls' => route('session.engagement.polls', $session),
                    'createPoll' => route('session.engagement.polls.create', $session),
                    // Les gestes qui visent un objet précis composent leur adresse à
                    // partir de ces bases (vote, modération, ouverture/clôture).
                    'question' => url('session/'.$session->id.'/echanges/questions'),
                    'poll' => url('session/'.$session->id.'/echanges/sondages'),
                ],
            ];
        }

        return $this->room($session, [
            'label' => (string) $session->label,
            'editionTitle' => (string) ($session->event->title ?? ''),
            'startsAt' => $session->starts_at,
            'recordingUrl' => $session->hasRecording() ? (string) $session->recording_url : null,
            // ⚠️ « Quitter » ramène le modérateur à SES séances, et non à la page
            // publique de déroulement : il sort d'une séance pour enchaîner sur la
            // suivante, ou pour en écrire le rapport. Le test porte sur la séance
            // confiée, pas sur le rôle en salle - le staff y est modérateur aussi,
            // et n'a pas d'espace personnel où atterrir.
            'backUrl' => $session->isModeratedBy($account)
                ? route('account.moderation')
                : route('public.deroulement'),
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
            'editionTitle' => (string) ($formation->event->title ?? ''),
            'startsAt' => $formation->starts_at,
            'recordingUrl' => null,
            'backUrl' => route('account.formations'),
        ]);
    }

    /**
     * Nouveau jeton d'accès à la salle, pour se reconnecter sans recharger.
     *
     * ⚠️ Le jeton de connexion expire (2 h par défaut). Jusqu'ici, une coupure
     * réseau après ce délai laissait le participant devant une salle morte, sans
     * rien d'autre à faire que recharger la page - en pleine séance. Les droits
     * sont **recalculés** à chaque appel : un rôle retiré entre-temps ne revient
     * pas par la porte du renouvellement.
     */
    public function token(string $type, int $id): JsonResponse
    {
        $salle = MeetableRegistry::find($type, $id);
        abort_if($salle === null, 404);

        // Réconciliation paresseuse du statut de la séance (cf. join()).
        if ($salle instanceof EventSession) {
            $salle->syncMeetingStatus();
        }

        $account = Auth::guard('account')->user();
        $isStaff = Auth::guard('web')->check();
        $role = $salle->meetingRoleFor($account, $isStaff);

        abort_unless($role->canJoin() && $salle->isLive(), 403);

        $provider = app(MeetingProviderRegistry::class)->for($salle);
        abort_unless($provider instanceof LiveKitProvider, 404);

        return response()->json([
            'token' => $provider->token($salle, $role, $salle->meetingDisplayName($account, $isStaff, null), null, $account?->id),
        ]);
    }

    /**
     * Rendu commun de la salle pour un objet `Meetable`.
     *
     * @param  array{label: string, editionTitle: string, startsAt: Carbon|null, recordingUrl: string|null, backUrl: string}  $ctx
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
            // Fil de discussion archivé : la clé de type vient de la liste
            // fermée du contrôleur, pour n'avoir qu'un seul vocabulaire.
            $cle = MeetableRegistry::keyFor($meetable);
            // Renouvellement du jeton : la salle en redemande un pour se
            // reconnecter après une coupure (cf. commentaire sur `jeton`).
            $liveKit['tokenUrl'] = $cle === null ? null : route('meeting.token', [$cle, $meetable->getKey()]);
            $liveKit['chat'] = $cle === null ? null : [
                'list' => route('meeting.chat.list', [$cle, $meetable->getKey()]),
                'post' => route('meeting.chat.post', [$cle, $meetable->getKey()]),
                'react' => route('meeting.chat.react', [$cle, $meetable->getKey(), 'UID']),
                'delete' => route('meeting.chat.delete', [$cle, $meetable->getKey(), 'UID']),
                'export' => route('meeting.chat.export', [$cle, $meetable->getKey()]),
                'emojis' => MeetingMessage::EMOJIS,
            ];
        }

        return view('meeting.join', [
            'label' => $ctx['label'],
            // Nom de cet onglet : il permet aux pages de la séance d'y revenir
            // sans ouvrir une seconde salle (cf. MeetableRegistry::tabFor()).
            'onglet' => MeetableRegistry::tabFor($meetable),
            'editionTitle' => $ctx['editionTitle'],
            'startsAt' => $ctx['startsAt'],
            'recordingUrl' => $ctx['recordingUrl'],
            'backUrl' => $ctx['backUrl'],
            'isLive' => $meetable->isLive(),
            // Statut de la visio : la page « hors salle » adapte son contenu dessus
            // (programmée → salle d'attente, terminée → replay, réservée, etc.).
            'meetingStatus' => $meetable->meetingStatusEnum(),
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
