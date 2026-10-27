<?php

namespace App\Http\Controllers\Account;

use App\Actions\CreateSubmission;
use App\Actions\NotifySubmissionResubmitted;
use App\Actions\NotifySubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ResubmitSubmissionRequest;
use App\Http\Requests\Account\StoreSubmissionRequest;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Slot;
use App\Models\Submission;
use App\Models\User;
use App\Support\CalendarExport;
use App\Support\CurrentEvent;
use App\Support\EventLock;
use App\Support\EventStorage;
use App\Support\Html;
use App\Support\ParticipantTypes;
use App\Support\RorClient;
use App\Support\SubmissionFormConfig;
use App\Support\SubmissionKeywords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Communications du compte connecté (contrôleur + Blade, sans Livewire) :
 * liste, dépôt (wizard multi-étapes piloté par Alpine + POST multipart), fiche
 * de suivi (statut, retours évaluateurs, versions), choix de créneau et
 * resoumission d'une version corrigée.
 */
class SubmissionController extends Controller
{
    public function index(): View
    {
        $account = Auth::guard('account')->user();

        // Événement courant (sélecteur) : la liste se scope dessus.
        $current = CurrentEvent::current($account);

        // « Mes communications » = celles que j'ai déposées OU celles où je suis
        // rattaché comme auteur/co-auteur (liaison confirmée, non déclinée).
        $submissions = Submission::query()
            ->canonical()
            ->with('event')
            ->where(function ($q) use ($account): void {
                $q->whereHas('participant', fn ($p) => $p->where('user_id', $account->id))
                    ->orWhereHas('authorLinks', fn ($l) => $l->where('user_id', $account->id)->where('status', 'linked'));
            })
            ->when($current, fn ($q) => $q->where('event_id', $current->id))
            ->latest('id')
            ->get();

        // Événement ouverte aux soumissions (pour « Nouvelle soumission ») + échéance.
        $openEdition = Event::acceptingSubmissions()
            ->latest('id')->first();

        // On soumet dans le colloque sélectionné (repli sur l'appel ouvert pour un
        // compte sans participation). Contrôle en amont : place restante.
        $submitEdition = $current ?? $openEdition;
        $submitRemaining = $submitEdition ? Submission::remainingFor($submitEdition, $account) : 0;

        $deadlineEdition = $openEdition
            ?? $submissions->first()?->event
            ?? Event::latest('id')->first();

        return view('account.submissions.index', [
            'submissions' => $submissions,
            'openEdition' => $openEdition,
            'submitEdition' => $submitEdition,
            'submitRemaining' => $submitRemaining,
            'deadline' => $deadlineEdition?->submission_deadline,
            'callOpen' => (bool) $openEdition,
        ]);
    }

    /** Formulaire de dépôt d'une communication (wizard). */
    public function create(string $slug): View
    {
        $event = Event::where('slug', $slug)->first();
        abort_unless($event && $event->isPublished(), 404);

        $account = Auth::guard('account')->user();
        $config = new SubmissionFormConfig($event);

        return view('account.submissions.create', [
            'event' => $event,
            'config' => $config,
            'account' => $account,
            'tracks' => $event->settings['tracks'] ?? [],
            'gradeOptions' => ParticipantTypes::all(),
            'maxCommunications' => (int) ($event->settings['max_communications'] ?? 3),
            'remaining' => Submission::remainingFor($event, $account),
            'successRef' => session('success_ref'),
        ]);
    }

    /**
     * Autocomplétion des affiliations : suggestions **ROR** (référentiel mondial
     * des organisations de recherche, API libre) ; repli sur les affiliations
     * déjà connues localement si ROR ne répond pas / rien trouvé.
     */
    public function affiliations(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $items = RorClient::suggest($q);

        if ($items === [] && mb_strlen($q) >= 2) {
            $items = $this->knownAffiliations($q);
        }

        return response()->json(['items' => $items]);
    }

    /**
     * Affiliations/institutions déjà connues localement (comptes + participants),
     * filtrées par `$q` - repli hors-ligne de l'autocomplétion ROR.
     *
     * @return list<string>
     */
    private function knownAffiliations(string $q): array
    {
        $like = '%'.$q.'%';
        $fromUsers = User::query()->where('affiliation', 'like', $like)->distinct()->pluck('affiliation');
        $fromParticipants = Participant::query()->where('institution', 'like', $like)->distinct()->pluck('institution');

        return $fromUsers->merge($fromParticipants)
            ->map(fn ($a) => trim((string) $a))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->take(8)
            ->all();
    }

    /** Enregistre la communication déposée (validation : StoreSubmissionRequest). */
    public function store(StoreSubmissionRequest $request, CreateSubmission $action): RedirectResponse
    {
        $event = $request->event();
        EventLock::guard($event);
        $account = Auth::guard('account')->user();

        $submission = $action->handle($event, $account, $request->config(), $request->validated(), $request->file('file'));

        return redirect()->route('account.submissions.create', $event->slug)
            ->with('success_ref', $submission->ref_code);
    }

    public function show(Submission $submission): View
    {
        // Le déposant, l'auteur correspondant OU un co-auteur rattaché peut
        // consulter la fiche ; les DEUX premiers peuvent la modifier (gardé par
        // $isOwner dans la vue). {@see Submission::isEditableBy()}
        $isOwner = $this->authorizeView($submission);
        $account = Auth::guard('account')->user();

        $slot = $this->currentSlot($submission);

        return view('account.submissions.show', [
            'submission' => $submission,
            'feedback' => $this->authorFeedback($submission),
            'versions' => $submission->versions()->get(),
            'slot' => $slot,
            'availableSlots' => $this->availableSlots($submission),
            'isOwner' => $isOwner,
            // ⚠️ « Peut modifier » ne dit pas À QUEL TITRE : l'auteur
            // correspondant dont un tiers a déposé la communication doit lire
            // autre chose que le déposant.
            'correspondantNonDeposant' => $submission->isCorrespondingAuthor($account)
                && ! $submission->wasDepositedBy($account),
            'abstractMin' => (new SubmissionFormConfig($submission->event))->abstractMin(),
            'abstractMax' => (new SubmissionFormConfig($submission->event))->abstractMax(),
            'googleCal' => $slot && $slot->starts_at
                ? app(CalendarExport::class)->googleUrl(AgendaController::slotEvent($slot))
                : null,
        ]);
    }

    /**
     * **La correction a sa propre page.**
     *
     * ⚠️⚠️ Elle vivait **au bas de la fiche**, après l'historique, les documents
     * liés et le créneau de passage : le bouton « Corriger & renvoyer » n'était
     * qu'une ancre `#resubmit`, et l'auteur se retrouvait devant un formulaire
     * surgi au milieu d'une page de consultation, sans comprendre qu'il venait
     * de changer de tâche. Une erreur de validation le renvoyait au même endroit,
     * à lui de retrouver le bloc.
     *
     * ⚠️ **Une page, pas une fenêtre.** Le formulaire porte l'éditeur de résumé
     * (CKEditor + compteur de mots), le sélecteur de mots-clés et un dépôt de
     * fichier - trois composants qu'une fenêtre enfermerait dans une boîte, sur
     * une fiche qui tient déjà son propre état Alpine et sa modale de créneau.
     * Et le DÉPÔT est une page : corriger doit se présenter comme déposer.
     *
     * ⚠️ **Les retours du comité sont sur CETTE page**, au-dessus du formulaire :
     * on corrige avec les remarques sous les yeux, sans remonter la fiche.
     */
    public function revise(Submission $submission): View
    {
        $this->authorizeOwnership($submission);
        abort_unless($submission->canBeRevised(), 403);

        $config = new SubmissionFormConfig($submission->event);

        return view('account.submissions.revise', [
            'submission' => $submission,
            'config' => $config,
            'feedback' => $this->authorFeedback($submission),
            'keywordSeparator' => $config->keywordSeparator(),
        ]);
    }

    /** Réponse de l'auteur aux évaluateurs (rebuttal), tant que la phase est ouverte. */
    public function rebuttal(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorizeOwnership($submission);
        abort_unless($submission->rebuttalOpen(), 403);

        $data = $request->validate([
            'rebuttal' => ['required', 'string', 'max:8000'],
        ], [
            'rebuttal.required' => 'Saisissez votre réponse aux évaluateurs.',
        ]);

        $meta = $submission->meta ?? [];
        $rebuttal = is_array($meta['rebuttal'] ?? null) ? $meta['rebuttal'] : [];
        $rebuttal['text'] = trim($data['rebuttal']);
        $rebuttal['submitted_at'] = now()->toIso8601String();
        $meta['rebuttal'] = $rebuttal;
        $submission->update(['meta' => $meta]);

        return redirect()->route('account.submissions.show', $submission)
            ->with('ukc_status', 'Votre réponse aux évaluateurs a été transmise au comité.');
    }

    /** Le participant choisit lui-même un créneau libre. */
    public function chooseSlot(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorizeOwnership($submission);
        abort_unless($submission->status === 'accepted', 403);

        $slot = Slot::where('id', (int) $request->input('slot_id'))
            ->where('event_id', $submission->event_id)
            ->where('submission_id', 0)
            ->first();

        if (! $slot) {
            return back()->with('ukc_error', 'Ce créneau n’est plus disponible.');
        }

        // Libère l'ancien créneau puis prend le nouveau.
        Slot::where('submission_id', $submission->id)
            ->update(['submission_id' => 0, 'participant_id' => 0, 'chosen_by_participant' => false]);

        $slot->update([
            'submission_id' => $submission->id,
            'participant_id' => $submission->participant_id ?? 0,
            'chosen_by_participant' => true,
        ]);

        return back()->with('ukc_status', 'Votre créneau de passage est confirmé.');
    }

    /**
     * Correction en place du contenu (titre / résumé / mots-clés) tant que la
     * communication n'a pas été touchée par le staff. Ne crée PAS de version :
     * la communication est encore « déposée » et personne ne l'a évaluée.
     */
    public function updateContent(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorizeOwnership($submission);
        abort_unless($submission->isAuthorEditable(), 403);

        $config = new SubmissionFormConfig($submission->event);

        $titleRule = ['required', 'string', 'max:'.$config->titleMax()];
        if ($config->titleMin() > 0) {
            $titleRule[] = 'min:'.$config->titleMin();
        }

        $rules = [
            'title' => $titleRule,
            'abstract' => ['required', 'string', 'max:20000', function (string $attr, string $value, \Closure $fail) use ($config): void {
                $words = SubmissionFormConfig::wordCount($value);
                if ($words < $config->abstractMin()) {
                    $fail('Le résumé doit contenir au moins '.$config->abstractMin().' mots.');
                } elseif ($words > $config->abstractMax()) {
                    $fail('Le résumé ne doit pas dépasser '.$config->abstractMax().' mots.');
                }
            }],
        ];
        if ($config->enabled('keywords')) {
            $rules['keywords'] = ['nullable', 'string', 'max:500'];
        }

        $data = $request->validate($rules);

        $submission->update([
            'title' => $data['title'],
            'abstract' => Html::clean($data['abstract']),
            'keywords' => SubmissionKeywords::join($data['keywords'] ?? null, $config->keywordSeparator()),
        ]);

        return redirect()->route('account.submissions.show', $submission)
            ->with('ukc_status', 'Votre résumé a été mis à jour.');
    }

    /** Change la présentation de cette seule communication. */
    public function updatePresentationMode(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorizeOwnership($submission);
        $modes = (new SubmissionFormConfig($submission->event))->participationModes();
        $data = $request->validate([
            'presentation_mode' => ['required', Rule::in(array_keys($modes))],
        ]);

        $submission->update(['presentation_mode' => $data['presentation_mode']]);

        return redirect()->route('account.submissions.show', $submission)
            ->with('ukc_status', 'Mode de présentation enregistré pour cette communication.');
    }

    public function resubmit(ResubmitSubmissionRequest $request, Submission $submission, NotifySubmissionStatus $notify, NotifySubmissionResubmitted $notifyStaff): RedirectResponse
    {
        $this->authorizeOwnership($submission);
        abort_unless($submission->canBeRevised(), 403);

        $data = $request->validated();

        // Archive la version courante (revision_of = canonique).
        Submission::create([
            'event_id' => $submission->event_id,
            'participant_id' => $submission->participant_id,
            'ref_code' => $submission->ref_code,
            'access_token' => Str::random(64),
            'title' => $submission->title,
            'authors' => $submission->authors,
            'abstract' => $submission->abstract,
            'keywords' => $submission->keywords,
            'track' => $submission->track,
            'type' => $submission->type,
            'presentation_mode' => $submission->presentation_mode,
            'status' => 'superseded',
            'file_url' => $submission->file_url,
            'revision_of' => $submission->id,
            'version' => $submission->version,
        ]);

        // Nouveau fichier éventuel.
        //
        // ⚠️ **Il porte la référence ET LA VERSION** (`EAU26-029-v2.pdf`) : la
        // version archivée juste au-dessus garde son `file_url`, donc réécrire
        // le même nom écraserait sur le disque l'exemplaire qu'elle désigne
        // encore. {@see EventStorage::renameSubmissionFile()}
        $nouvelleVersion = $submission->version + 1;
        $fileUrl = $submission->file_url;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store(EventStorage::submissions($submission->event), 'public');
            $path = EventStorage::renameSubmissionFile($path, (string) $submission->ref_code, $nouvelleVersion) ?? $path;
            $fileUrl = Storage::disk('public')->url($path);
        }

        // Met à jour la version canonique.
        $submission->update([
            'title' => $data['title'],
            'abstract' => Html::clean($data['abstract']),
            'keywords' => SubmissionKeywords::join($data['keywords'] ?? null, (new SubmissionFormConfig($submission->event))->keywordSeparator()),
            'file_url' => $fileUrl,
            'version' => $nouvelleVersion,
            'status' => 'submitted',
        ]);

        // Le gestionnaire est alerté dès que la correction est enregistrée.
        // L'envoi à l'auteur peut échouer indépendamment (transport SMTP).
        $submission->refresh();
        $notifyStaff->handle($submission);
        $notify->handle($submission, 'submitted');

        return redirect()->route('account.submissions.show', $submission)
            ->with('ukc_status', 'Version corrigée envoyée. Votre communication est de nouveau en cours d’examen.');
    }

    // --- Helpers (suivi de communication) -------------------------------------

    /**
     * Droit d'ÉCRITURE sur une communication + événement non archivé.
     *
     * ⚠️ Cette garde est le point de passage des SIX actions d'écriture : y
     * placer le gel évite d'avoir à le répéter (et à l'oublier) dans chacune.
     *
     * ⚠️⚠️ **LE DÉPOSANT N'EST PAS TOUJOURS L'AUTEUR.** Elle n'acceptait que
     * lui : un secrétariat qui dépose pour une équipe gardait seul la main, et
     * l'auteur correspondant - celui à qui le comité demande la correction - ne
     * pouvait rien modifier une fois son compte créé. Les DEUX passent
     * désormais ({@see Submission::isEditableBy()}), et c'est voulu : l'auteur
     * correspondant peut très bien ne jamais se connecter.
     */
    private function authorizeOwnership(Submission $submission): void
    {
        $account = Auth::guard('account')->user();
        EventLock::guard($submission->event);

        abort_unless($submission->isEditableBy($account), 404);
    }

    /**
     * Qui peut VOIR la fiche : le déposant, l'auteur correspondant, ou un
     * co-auteur rattaché. Rend « peut modifier » ({@see Submission::isEditableBy()}).
     */
    private function authorizeView(Submission $submission): bool
    {
        $account = Auth::guard('account')->user();

        if ($submission->isEditableBy($account)) {
            return true;
        }

        $linked = (int) $submission->revision_of === 0
            && $submission->authorLinks()
                ->where('user_id', $account->id)->where('status', 'linked')->exists();

        abort_unless($linked, 404);

        return false;
    }

    /** Commentaires des évaluateurs destinés à l'auteur (révélés après décision ou en phase de réponse). */
    private function authorFeedback(Submission $submission): array
    {
        if (! in_array($submission->status, ['revision', 'accepted', 'rejected'], true) && ! $submission->rebuttalOpen()) {
            return [];
        }

        return $submission->reviews()->where('status', 'completed')->get()
            ->filter(fn ($r) => filled($r->comments))
            ->values()
            ->map(fn ($r, $i) => ['label' => 'Évaluateur '.($i + 1), 'comments' => $r->comments])
            ->all();
    }

    /** Créneau actuellement assigné à cette communication. */
    private function currentSlot(Submission $submission): ?Slot
    {
        return Slot::where('submission_id', $submission->id)->first();
    }

    /** Créneaux libres proposés au participant (communication acceptée). */
    private function availableSlots(Submission $submission)
    {
        if ($submission->status !== 'accepted') {
            return collect();
        }

        return Slot::where('event_id', $submission->event_id)
            ->where('submission_id', 0)
            ->orderBy('starts_at')
            ->get();
    }
}
