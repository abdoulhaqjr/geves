<?php

namespace App\Http\Controllers\Account;

use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Documents\LivretDocx;
use App\Http\Controllers\Controller;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use App\Support\CurrentColloque;
use App\Support\EditionSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Documents de l'édition accessibles au participant connecté : livret des
 * résumés d'une édition à laquelle il a participé (contrôle de propriété).
 */
class DocumentController extends Controller
{
    /**
     * « Mes documents » (Blade, fidèle au mockup) : espace consolidé — attestations
     * vérifiables, reçus de paiement, et documents des éditions du participant
     * (ressources + livret des résumés). Lecture seule → Blade.
     */
    public function index(): View
    {
        $account = Auth::guard('account')->user();

        $participants = Participant::where('user_id', $account->id)->with('edition')->get();
        $participantIds = $participants->pluck('id');

        // Édition de contexte = colloque courant sélectionné (repli : participation
        // la plus récente).
        $current = CurrentColloque::current($account);
        $participant = ($current ? $participants->firstWhere('edition_id', $current->id) : null)
            ?? $participants->sortByDesc('edition_id')->first();
        $edition = $participant?->edition;

        if (! $edition) {
            return view('account.documents', ['edition' => null]);
        }

        $edParticipantIds = $participants->where('edition_id', $edition->id)->pluck('id');

        $paid = Payment::whereIn('participant_id', $edParticipantIds)
            ->where('status', 'paid')->exists();
        $payment = Payment::whereIn('participant_id', $edParticipantIds)
            ->where('status', 'paid')->latest('id')->first();

        $submissions = Submission::where('edition_id', $edition->id)
            ->whereIn('participant_id', $edParticipantIds)
            ->where('revision_of', 0)->latest('id')->get();

        // Attestations émises (une par type), pour lookup rapide.
        $atts = Attestation::whereIn('participant_id', $edParticipantIds)
            ->where('edition_id', $edition->id)->where('revoked', false)
            ->get()->keyBy('type');

        $hasBooklet = $edition->submissions()
            ->where('revision_of', 0)->where('status', 'accepted')->exists();

        // Média « appel à communications » de l'édition (le plus pertinent) + tous les médias.
        $edition->loadMissing(['media' => fn ($q) => $q->orderBy('ordre')->orderBy('id')]);

        // Référence de badge + rôle (débloqué après paiement).
        $prefix = (string) EditionSettings::get($edition, 'badges.prefix', 'UKC-'.$edition->year.'-');
        $badgeRef = $prefix.Str::upper($account->initials).'-'.str_pad((string) $participant->id, 4, '0', STR_PAD_LEFT);
        $grade = $participant->title ?: (string) $account->grade;
        $role = trim(($grade !== '' ? $grade.' · ' : '').$participant->roleLabel());
        $badgeRole = $role ?: 'Participant';
        $didIssuer = (string) EditionSettings::get($edition, 'attestations.did_issuer', 'did:web:univkara.tg:colloques');

        // Aperçu du badge rendu à partir du MODÈLE conçu dans l'admin (même mise en
        // page que l'éditeur), peuplé des données réelles du participant.
        $badge = $this->renderBadge($edition, $account, $participant, $badgeRef, $badgeRole);

        return view('account.documents', [
            'account' => $account,
            'edition' => $edition,
            'participant' => $participant,
            'paid' => $paid,
            'payment' => $payment,
            'submissions' => $submissions,
            'atts' => $atts,
            'hasBooklet' => $hasBooklet,
            'media' => $edition->media,
            'badgeRef' => $badgeRef,
            'badgeRole' => $badgeRole,
            'didIssuer' => $didIssuer,
            'badgeCanvas' => $badge['html'],
            'badgeW' => $badge['w'],
            'badgeH' => $badge['h'],
        ]);
    }

    /**
     * Rend l'aperçu du badge à partir du modèle enregistré pour l'édition (kind
     * « badge »), ou du modèle par défaut, avec les vraies données du participant.
     * Retourne le HTML positionné + les dimensions natives (pour la mise à l'échelle).
     *
     * @return array{html: string, w: int, h: int}
     */
    private function renderBadge(Edition $edition, $account, Participant $participant, string $badgeRef, string $badgeRole): array
    {
        $template = DocumentTemplate::where('edition_id', $edition->id)
            ->where('kind', 'badge')->where('type', 'default')->first();

        $layout = LayoutSanitizer::sanitize($template?->layout ?? [], 'badge', 'default');

        $photoSrc = $account->photo_path ? Storage::url($account->photo_path) : '';
        $verifyUrl = url('/verifier?ref='.urlencode($badgeRef).'&pid='.$participant->id.'&ed='.$edition->id);

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

        $dates = $edition->date_start
            ? $edition->date_start->translatedFormat('d/m/Y').($edition->date_end ? ' – '.$edition->date_end->translatedFormat('d/m/Y') : '')
            : '';
        $institution = $account->affiliation ?: (string) $participant->institution;

        $vars = [
            // Schéma de l'éditeur de badges (crochets).
            'recipient.name' => $account->full_name,
            'recipient.role' => $badgeRole,
            'recipient.institution' => $institution,
            'recipient.photo' => $photoSrc,
            'badge.ref' => $badgeRef,
            'edition.name' => $edition->title ?: (string) $edition->colloque?->name,
            'edition.dates' => $dates,
            'edition.theme' => (string) $edition->theme,
            // Schéma attestation (accolades) pour compatibilité.
            'name' => $account->full_name,
            'role' => $badgeRole,
            'org' => (string) $edition->colloque?->name,
            'event' => (string) $edition->title,
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

    public function livret(Edition $edition, Request $request): View|Response
    {
        $account = Auth::guard('account')->user();

        $participates = Participant::where('edition_id', $edition->id)
            ->where('user_id', $account->id)
            ->exists();

        abort_unless($participates, 403);

        $groups = $edition->submissions()
            ->where('revision_of', 0)
            ->where('status', 'accepted')
            ->orderBy('track')
            ->get()
            ->groupBy(fn ($s) => $s->track ?: 'Autres');

        if ($request->query('format') === 'docx') {
            return (new LivretDocx($edition, $groups))
                ->download('livret-'.($edition->slug ?: 'edition-'.$edition->id).'.docx');
        }

        return view('documents.livret', [
            'edition' => $edition,
            'groups' => $groups,
        ]);
    }
}
