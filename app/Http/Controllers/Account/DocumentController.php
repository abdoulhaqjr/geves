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
use App\Support\AttestationPolicy;
use App\Support\CurrentEvent;
use App\Support\EventSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        // Référence de badge + rôle (débloqué après paiement).
        $prefix = (string) EventSettings::get($event, 'badges.prefix', 'UKC-'.$event->year.'-');
        $badgeRef = $prefix.Str::upper($account->initials).'-'.str_pad((string) $participant->id, 4, '0', STR_PAD_LEFT);
        $grade = $participant->title ?: (string) $account->grade;
        $role = trim(($grade !== '' ? $grade.' - ' : '').$participant->roleLabel());
        $badgeRole = $role ?: 'Participant';
        $didIssuer = (string) EventSettings::get($event, 'attestations.did_issuer', 'did:web:univkara.tg:colloques');

        // Aperçu du badge rendu à partir du MODÈLE conçu dans l'admin (même mise en
        // page que l'éditeur), peuplé des données réelles du participant.
        $badge = $this->renderBadge($event, $account, $participant, $badgeRef, $badgeRole);

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
            'badgeRef' => $badgeRef,
            'badgeRole' => $badgeRole,
            'didIssuer' => $didIssuer,
            'badgeCanvas' => $badge['html'],
            'badgeW' => $badge['w'],
            'badgeH' => $badge['h'],
        ]);
    }

    /**
     * Rend l'aperçu du badge à partir du modèle enregistré pour l'événement (kind
     * « badge »), ou du modèle par défaut, avec les vraies données du participant.
     * Retourne le HTML positionné + les dimensions natives (pour la mise à l'échelle).
     *
     * @return array{html: string, w: int, h: int}
     */
    private function renderBadge(Event $event, $account, Participant $participant, string $badgeRef, string $badgeRole): array
    {
        // Modèle enregistré (créé au besoin) : le badge du participant et la planche
        // admin rendent ainsi strictement la même mise en page.
        $template = DocumentTemplate::ensureFor((int) $event->id, 'badge');

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'badge', 'default');

        $photoSrc = $account->photo_path ? Storage::url($account->photo_path) : '';
        $verifyUrl = url('/verifier?ref='.urlencode($badgeRef).'&pid='.$participant->id.'&ed='.$event->id);

        // Le modèle range la photo comme un texte « Photo »/[recipient.photo] et le
        // QR avec une URL générique : on les remplace par les données réelles.
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
                if ($v === '' || str_contains($v, 'univkara.tg/colloque/badge')) {
                    $el['value'] = $verifyUrl;
                }
            }

            return $el;
        }, (array) ($layout['elements'] ?? []));

        $dates = $event->date_start
            ? $event->date_start->translatedFormat('d/m/Y').($event->date_end ? ' - '.$event->date_end->translatedFormat('d/m/Y') : '')
            : '';
        $institution = $account->affiliation ?: (string) $participant->institution;

        $vars = [
            // Schéma de l'éditeur de badges (crochets).
            'recipient.name' => $account->full_name,
            'recipient.role' => $badgeRole,
            'recipient.institution' => $institution,
            'recipient.photo' => $photoSrc,
            'badge.ref' => $badgeRef,
            'edition.name' => $event->title ?: (string) $event->series?->name,
            'edition.dates' => $dates,
            'edition.theme' => (string) $event->theme,
            'edition.location' => (string) $event->location,
            // Schéma attestation (accolades) pour compatibilité.
            'name' => $account->full_name,
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
