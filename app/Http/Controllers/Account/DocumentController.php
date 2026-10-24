<?php

namespace App\Http\Controllers\Account;

use App\Documents\BadgeLayout;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Documents\LivretCover;
use App\Documents\LivretPdf;
use App\Documents\LivretView;
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
use App\Support\TaskDocSpace;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // ⚠️⚠️ **UNE PERSONNE CUMULE LES FONCTIONS, ET SES ATTESTATIONS AVEC.**
        // La page ne rendait que `participation` : modération, rapport, comité
        // d'organisation, appui à l'organisation et instruction de résumés
        // étaient bien CHARGÉES ici - elles tombaient dans `$atts` - mais
        // aucune ligne ne les affichait. Un modérateur ne pouvait ni voir ni
        // retirer l'attestation qu'on lui avait pourtant délivrée.
        //
        // ⚠️ **On part des attestations ÉMISES, pas d'une liste de types** : la
        // règle couvre ainsi tout type délivré, y compris ceux qui ne sont plus
        // offerts d'office (« soumission », « acceptation », encore en base) et
        // ceux qu'on ajoutera. Les quatre types déjà rendus par leurs propres
        // sections sont écartés, sinon ils s'afficheraient deux fois.
        $attsFonctions = $attestations
            ->whereNotIn('type', ['participation', 'communication', 'formation', 'formation_animation'])
            ->sortBy(fn (Attestation $a) => $a->title())
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
                    // Seul le badge participant reste verrouillé avant paiement.
                    'locked' => $type === 'default' && ! $paid,
                    'supports_photo' => $rendered['supports_photo'],
                    'photo_mode' => $rendered['photo_mode'],
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
            'attsFonctions' => $attsFonctions,
            // Délivrance physique : le participant VOIT son attestation et peut la
            // vérifier, mais l'exemplaire qui fait foi lui est remis en main propre.
            'papier' => AttestationPolicy::isPaper($event),
            'hasBooklet' => $hasBooklet,
            'media' => $event->media,
            'badges' => $badges,
            'didIssuer' => $didIssuer,
        ]);
    }

    /**
     * Rend l'aperçu du badge à partir du modèle enregistré pour l'événement (kind
     * « badge »), ou du modèle par défaut, avec les vraies données du participant.
     * Retourne le HTML positionné + les dimensions natives (pour la mise à l'échelle).
     *
     * @return array{html: string, w: int, h: int, supports_photo: bool, photo_mode: string}
     */
    private function renderBadge(Event $event, User $account, Participant $participant, string $type, array $recipient): array
    {
        $badgeRef = $recipient['ref'];
        $badgeRole = BadgeCatalog::role($type);
        $template = DocumentTemplate::ensureFor((int) $event->id, 'badge', $type, BadgeCatalog::TYPES[$type]['title']);

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'badge', $type);

        // Identité d'événement : figée si l'événement est archivé (instantané du
        // participant), sinon en direct. Un profil modifié après la clôture ne
        // retouche donc plus le badge de cet événement.
        $photoSrc = $participant->displayPhotoUrl();
        $supportsPhoto = BadgeLayout::hasPhoto($layout);
        $photoModes = (array) (($participant->meta ?? [])['badge_photo_preferences'] ?? []);
        $photoMode = ($photoModes[$type] ?? 'with') === 'without' ? 'without' : 'with';
        $layout = BadgeLayout::forRecipient($layout, $type, $photoSrc, $photoMode === 'with');
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

        $dates = $event->dateRangeLabel() ?? '';
        // Nom et affiliation d'événement : figés si l'événement est archivé, sinon
        // le profil du compte (source actuelle sur tous ses badges) ; les fiches de
        // séance et d'inscription conservent parfois l'ancien nom.
        $name = $participant->displayName();
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
            'html' => LayoutRenderer::render($layout, $vars, badge: true),
            'w' => (int) $layout['width'],
            'h' => (int) $layout['height'],
            'supports_photo' => $supportsPhoto,
            'photo_mode' => $photoMode,
        ];
    }

    /** Préférence d'affichage propre à ce participant, cet événement et ce badge. */
    public function updateBadgePhoto(Request $request, Event $event, string $type): JsonResponse|RedirectResponse
    {
        abort_unless(BadgeCatalog::valid($type), 404);

        $account = Auth::guard('account')->user();
        $participant = Participant::where('event_id', $event->id)
            ->where('user_id', $account->id)
            ->firstOrFail();
        $badge = BadgeCatalog::forAccount($event, $account, $participant)->firstWhere('type', $type);
        abort_unless($badge !== null, 403);

        $template = DocumentTemplate::ensureFor((int) $event->id, 'badge', $type, BadgeCatalog::TYPES[$type]['title']);
        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'badge', $type);
        abort_unless($account->photo_path && BadgeLayout::hasPhoto($layout), 422);

        $data = $request->validate(['photo_mode' => 'required|in:with,without']);
        $meta = $participant->meta ?? [];
        $preferences = (array) ($meta['badge_photo_preferences'] ?? []);
        $preferences[$type] = $data['photo_mode'];
        $meta['badge_photo_preferences'] = $preferences;
        $participant->update(['meta' => $meta]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'badge' => $this->renderBadge($event, $account, $participant, $type, $badge['recipient']),
            ]);
        }

        return redirect()->to(route('account.documents').'#badge-'.$type)
            ->with('ukc_status', 'Présentation du badge enregistrée.');
    }

    /**
     * Le livre des résumés du participant : **le PDF**, et rien d'autre.
     *
     * ⚠️⚠️ **LE .DOCX N'EST PAS LE DOCUMENT QU'ON REMET.** C'est un gabarit, fait
     * pour être retouché dans Word puis réimporté - il appartient au back-office.
     * Proposé au participant, il donnait à croire que le livre se modifie, et il
     * s'ouvre différemment d'une machine à l'autre. Le PDF se lit partout de la
     * même façon, et c'est lui qu'on laisse.
     *
     * ⚠️ **C'est le MÊME livre que celui du back-office** ({@see LivretView}) :
     * l'espace participant rendait sa propre vue HTML, plus pauvre - sans les
     * comités ni le programme - sans que rien ne le signale.
     */
    public function livret(Event $event, Request $request, TaskDocSpace $docspace): View|Response
    {
        $account = Auth::guard('account')->user();

        $participates = Participant::where('event_id', $event->id)
            ->where('user_id', $account->id)
            ->exists();

        abort_unless($participates, 403);

        // ⚠️ **UNE SEULE règle pour « ce que le livre contient »** : le scope,
        // jamais ses conditions recopiées - c'est ainsi que le programme avait
        // fini par annoncer des communications absentes des résumés.
        $groups = $event->submissions()
            ->acceptedCanonical()
            ->with('authorLinks.user')
            ->orderBy('track')
            ->get()
            ->groupBy(fn ($s) => $s->track ?: 'Autres');

        $pdf = LivretPdf::make($event, $groups);

        if (LivretCover::fileId($event) !== '') {
            $binary = LivretCover::pdf($event, $pdf->output(), $docspace);

            return response($binary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="livret-'.($event->slug ?: 'edition-'.$event->id).'.pdf"',
            ]);
        }

        // Ouvert dans l'onglet : le lecteur du navigateur sait l'enregistrer.
        return $pdf->stream('livret-'.($event->slug ?: 'edition-'.$event->id).'.pdf');
    }
}
