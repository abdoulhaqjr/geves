<?php

namespace App\Http\Controllers\Account;

use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Documents\LivretDocx;
use App\Http\Controllers\Controller;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use App\Models\User;
use App\Support\AttestationPolicy;
use App\Support\BadgeCatalog;
use App\Support\CurrentEvent;
use App\Support\EventSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Documents de l'événement accessibles au participant connecté : livret des
 * résumés d'un événement à laquelle il a participé (contrôle de propriété).
 */
class DocumentController extends Controller
{
    /**
     * « Mes documents » (Blade, fidèle au mockup) : espace consolidé - attestations
     * vérifiables, reçus de paiement, et documents des événements du participant
     * (ressources + livret des résumés). Lecture seule → Blade.
     */
    public function index(): View
    {
        $account = Auth::guard('account')->user();

        $participants = Participant::where('user_id', $account->id)->with('event')->get();
        $participantIds = $participants->pluck('id');

        // Événement de contexte = colloque courant sélectionné (repli : participation
        // la plus récente).
        $current = CurrentEvent::current($account);
        $participant = ($current ? $participants->firstWhere('event_id', $current->id) : null)
            ?? $participants->sortByDesc('event_id')->first();
        $event = $participant?->event;

        if (! $event) {
            return view('account.documents', ['event' => null]);
        }

        $edParticipantIds = $participants->where('event_id', $event->id)->pluck('id');

        // ⚠️ Seuls les paiements de PARTICIPATION ouvrent le badge et les documents :
        // une formation réglée est rattachée au même participant mais ne donne
        // aucun droit sur l'événement.
        $paid = Payment::whereIn('participant_id', $edParticipantIds)
            ->forParticipation()->where('status', 'paid')->exists();
        $payment = Payment::whereIn('participant_id', $edParticipantIds)
            ->forParticipation()->where('status', 'paid')->latest('id')->first();

        $submissions = Submission::where('event_id', $event->id)
            ->whereIn('participant_id', $edParticipantIds)
            ->where('revision_of', 0)->latest('id')->get();

        // Attestations émises et valides de cet événement.
        $attestations = Attestation::whereIn('participant_id', $edParticipantIds)
            ->where('event_id', $event->id)->where('revoked', false)
            ->with('formation')
            ->orderBy('id')
            ->get();

        // Types dont il n'existe qu'un exemplaire par personne (participation…).
        $atts = $attestations->keyBy('type');

        // ⚠️ Les attestations de COMMUNICATION sont une par communication : indexées
        // par type, celle de la dernière s'affichait en face de toutes les autres,
        // et deux communications renvoyaient au même document.
        $attsParCommunication = $attestations->where('type', 'communication')->keyBy('submission_id');

        // ⚠️ Formations et animations : une par formation suivie ou animée. La page
        // n'en montrait qu'UNE, sans dire laquelle - quelqu'un qui a suivi trois
        // ateliers n'en voyait qu'un seul, et pouvait croire les autres perdus.
        $attsFormation = $attestations
            ->whereIn('type', ['formation', 'formation_animation'])
            ->sortBy(fn (Attestation $a) => $a->type.'-'.$a->formationTitle())
            ->values();

        $hasBooklet = $event->submissions()
            ->where('revision_of', 0)->where('status', 'accepted')->exists();

        // Média « appel à communications » de l'événement (le plus pertinent) + tous les médias.
        $event->loadMissing(['media' => fn ($q) => $q->orderBy('ordre')->orderBy('id')]);

        $didIssuer = (string) EventSettings::get($event, 'attestations.did_issuer', 'did:web:univkara.tg:colloques');

        // Le participant peut cumuler plusieurs fonctions. Chaque badge reprend
        // le type, le destinataire et la référence de la planche administrateur.
        $badges = BadgeCatalog::forAccount($event, $account, $participant)
            ->map(function (array $entry) use ($event, $account, $participant, $paid) {
                $type = $entry['type'];
                $recipient = $entry['recipient'];
                $rendered = $this->renderBadge($event, $account, $participant, $type, $recipient);

                return [
                    'type' => $type,
                    'title' => BadgeCatalog::TYPES[$type]['title'],
                    'role' => BadgeCatalog::role($type),
                    'ref' => $recipient['ref'],
                    // Le badge participant (avec OU sans photo) reste verrouillé tant
                    // que l'inscription n'est pas réglée ; les fonctions ne le sont pas.
                    'locked' => in_array($type, ['default', 'participant_photo'], true) && ! $paid,
                    'html' => $rendered['html'],
                    'w' => $rendered['w'],
                    'h' => $rendered['h'],
                ];
            });

        return view('account.documents', [
            'account' => $account,
            'event' => $event,
            'participant' => $participant,
            'paid' => $paid,
            'payment' => $payment,
            'submissions' => $submissions,
            'atts' => $atts,
            'attsParCommunication' => $attsParCommunication,
            'attsFormation' => $attsFormation,
            // Délivrance physique : le participant VOIT son attestation et peut la
            // vérifier, mais l'exemplaire qui fait foi lui est remis en main propre.
            'papier' => AttestationPolicy::isPaper($event),
            'hasBooklet' => $hasBooklet,
            'media' => $event->media,
            'badges' => $badges,
            // Choix « Badge sans photo de profil » (visible seulement si une photo existe).
            'badgeHasPhoto' => filled($account->photo_path),
            'badgeWithoutPhoto' => BadgeCatalog::prefersNoPhoto($account),
            'didIssuer' => $didIssuer,
        ]);
    }

    /**
     * Préférence « Badge sans photo de profil » (globale au compte, dans `meta`).
     * Quand elle est active, le badge participant utilise le modèle SANS photo même
     * si une photo de profil existe. Sans photo, le modèle sans photo est de toute
     * façon le seul retenu.
     */
    public function badgePhotoPreference(Request $request): RedirectResponse
    {
        $account = $request->user('account');
        $meta = (array) ($account->meta ?? []);
        $meta['badge_without_photo'] = $request->boolean('without_photo');
        $account->meta = $meta;
        $account->save();

        return back(fallback: route('account.documents'))
            ->with('ukc_success', __('Préférence du badge enregistrée.'));
    }

    /**
     * Rend l'aperçu du badge à partir du modèle enregistré pour l'événement (kind
     * « badge »), ou du modèle par défaut, avec les vraies données du participant.
     * Retourne le HTML positionné + les dimensions natives (pour la mise à l'échelle).
     *
     * @return array{html: string, w: int, h: int}
     */
    private function renderBadge(Event $event, User $account, Participant $participant, string $type, array $recipient): array
    {
        $badgeRef = $recipient['ref'];
        $badgeRole = BadgeCatalog::role($type);
        $template = DocumentTemplate::ensureFor((int) $event->id, 'badge', $type, BadgeCatalog::TYPES[$type]['title']);

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'badge', $type);

        $photoSrc = $account->photo_path ? Storage::url($account->photo_path) : '';
        $verifyUrl = url('/verifier?ref='.urlencode($badgeRef).'&pid='.$participant->id.'&ed='.$event->id);

        // Compatibilité avec les anciens modèles : photo enregistrée comme texte,
        // QR contenant seulement {ref} ou une ancienne URL générique.
        $layout['elements'] = array_map(function (array $el) use ($photoSrc, $verifyUrl) {
            if (($el['type'] ?? '') === 'text') {
                $t = trim((string) ($el['text'] ?? ''));
                if (strcasecmp($t, 'Photo') === 0 || preg_match('/\[recipient\.photo\]/i', $t)) {
                    $el['type'] = 'photo';
                    $el['src'] = $photoSrc;
                }
            }
            if (($el['type'] ?? '') === 'qr') {
                $v = (string) ($el['value'] ?? '');
                if ($v === '' || $v === '{ref}' || str_contains($v, 'univkara.tg/colloque/badge')) {
                    $el['value'] = $verifyUrl;
                }
            }

            return $el;
        }, (array) ($layout['elements'] ?? []));

        $dates = $event->date_start
            ? $event->date_start->translatedFormat('d/m/Y').($event->date_end ? ' - '.$event->date_end->translatedFormat('d/m/Y') : '')
            : '';
        // Le profil du compte est la source actuelle du nom sur tous ses badges ;
        // les fiches de séance et d'inscription conservent parfois l'ancien nom.
        $name = BadgeCatalog::accountName($account);
        $institution = $recipient['institution'];

        $vars = [
            // Schéma de l'éditeur de badges (crochets).
            'recipient.name' => $name,
            'recipient.role' => $badgeRole,
            'recipient.institution' => $institution,
            'recipient.photo' => $photoSrc,
            'badge.ref' => $badgeRef,
            'edition.name' => $event->title ?: (string) $event->series?->name,
            'edition.dates' => $dates,
            'edition.theme' => (string) $event->theme,
            'edition.location' => (string) $event->location,
            // Schéma attestation (accolades) pour compatibilité.
            'name' => $name,
            'role' => $badgeRole,
            'org' => (string) $event->series?->name,
            'event' => (string) $event->title,
            'theme' => (string) $event->theme,
            'dates' => $dates,
            'location' => (string) $event->location,
            'ref' => $badgeRef,
            'verify_url' => $verifyUrl,
            'initials' => $account->initials,
        ];

        return [
            'html' => LayoutRenderer::render($layout, $vars),
            'w' => (int) $layout['width'],
            'h' => (int) $layout['height'],
        ];
    }

    public function livret(Event $event, Request $request): View|Response
    {
        $account = Auth::guard('account')->user();

        $participates = Participant::where('event_id', $event->id)
            ->where('user_id', $account->id)
            ->exists();

        abort_unless($participates, 403);

        $groups = $event->submissions()
            ->where('revision_of', 0)
            ->where('status', 'accepted')
            ->orderBy('track')
            ->get()
            ->groupBy(fn ($s) => $s->track ?: 'Autres');

        if ($request->query('format') === 'docx') {
            return (new LivretDocx($event, $groups))
                ->download('livret-'.($event->slug ?: 'edition-'.$event->id).'.docx');
        }

        return view('documents.livret', [
            'event' => $event,
            'groups' => $groups,
        ]);
    }
}
