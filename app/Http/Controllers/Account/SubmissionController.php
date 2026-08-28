<?php

namespace App\Http\Controllers\Account;

use App\Actions\NotifySubmissionStatus;
use App\Actions\UpsertParticipantForAccount;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Slot;
use App\Models\Submission;
use App\Support\Html;
use App\Support\Notifier;
use App\Support\SubmissionFormConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Communications du compte connecté (contrôleur + Blade, sans Livewire) :
 * liste, dépôt (wizard multi-étapes piloté par Alpine + POST multipart), fiche
 * de suivi (statut, retours relecteurs, versions), choix de créneau et
 * resoumission d'une version corrigée.
 */
class SubmissionController extends Controller
{
    public function index(): View
    {
        $account = Auth::guard('account')->user();

        $submissions = Submission::query()
            ->canonical()
            ->with('edition')
            ->whereHas('participant', fn ($q) => $q->where('account_id', $account->id))
            ->latest('id')
            ->get();

        return view('account.submissions.index', [
            'submissions' => $submissions,
        ]);
    }

    /** Formulaire de dépôt d'une communication (wizard). */
    public function create(string $slug): View
    {
        $edition = Edition::where('slug', $slug)->first();
        abort_unless($edition && $edition->isPublished(), 404);

        $account = Auth::guard('account')->user();
        $config = new SubmissionFormConfig($edition);

        return view('account.submissions.create', [
            'edition' => $edition,
            'config' => $config,
            'account' => $account,
            'tracks' => $edition->settings['tracks'] ?? [],
            'maxCommunications' => (int) ($edition->settings['max_communications'] ?? 3),
            'remaining' => $this->remainingFor($edition, $account),
            'successRef' => session('success_ref'),
        ]);
    }

    /** Enregistre la communication déposée. */
    public function store(string $slug, Request $request, UpsertParticipantForAccount $upsert, NotifySubmissionStatus $notify): RedirectResponse
    {
        $edition = Edition::where('slug', $slug)->first();
        abort_unless($edition && $edition->isPublished(), 404);

        $account = Auth::guard('account')->user();
        $config = new SubmissionFormConfig($edition);
        $tracks = $edition->settings['tracks'] ?? [];

        // Garde-fous serveur (en plus de l'affichage).
        if (! $account->canSubmit()) {
            return back()->withInput()->withErrors(['abstract' => 'Votre e-mail doit être vérifié et votre profil complété (affiliation).']);
        }
        if (! $edition->submission_open) {
            return back()->withInput()->withErrors(['abstract' => 'Les soumissions sont fermées pour cette édition.']);
        }
        if ($this->remainingFor($edition, $account) <= 0) {
            return back()->withInput()->withErrors(['abstract' => 'Vous avez atteint le nombre maximum de communications pour cette édition.']);
        }

        // Valeurs par défaut (que le wizard envoie toujours) : type « oral » et
        // premier axe thématique — cohérent avec l'ancien composant.
        if ($config->enabled('type') && ! $request->filled('type')) {
            $request->merge(['type' => 'oral']);
        }
        if (count($tracks) && ! $request->filled('track')) {
            $request->merge(['track' => $tracks[0]]);
        }

        $validator = Validator::make($request->all(), $this->rulesFor($config, $tracks), [], $this->attributesFor($config));

        if ($validator->fails()) {
            // Renvoie le wizard vers l'étape contenant la première erreur (masquée sinon).
            $step = $this->stepForField((string) array_key_first($validator->errors()->toArray()));

            return back()->withInput()->withErrors($validator)->with('submission_error_step', $step);
        }

        $data = $validator->validated();

        // Téléversement du fichier (I/O) hors transaction.
        $fileUrl = null;
        if ($config->enabled('file') && $request->hasFile('file')) {
            $path = $request->file('file')->store('submissions/'.$edition->id, 'public');
            $fileUrl = Storage::disk('public')->url($path);
        }

        // Réponses aux champs personnalisés (label + valeur, pour affichage).
        $customMeta = [];
        foreach ($config->customFields() as $field) {
            $value = $data['customAnswers'][$field['key']] ?? null;
            if ($field['type'] === 'checkbox') {
                $value = $value ? 'Oui' : 'Non';
            }
            if ($value === '' || $value === null) {
                continue;
            }
            $customMeta[] = ['key' => $field['key'], 'label' => $field['label'], 'value' => (string) $value];
        }

        $coauthors = collect($data['coauthors'] ?? [])
            ->filter(fn ($c) => filled($c['name'] ?? null))
            ->map(fn ($c) => [
                'name' => $c['name'],
                'email' => $c['email'] ?? null,
                'affiliation' => $c['affiliation'] ?? null,
                'grade' => $c['grade'] ?? null,
            ])->values()->all();

        $authors = array_merge([[
            'name' => $account->full_name,
            'email' => $account->email,
            'affiliation' => $account->affiliation,
            'grade' => $account->grade,
            'corresponding' => true,
        ]], $coauthors);

        // Création atomique : le participant (upsert) et la communication ensemble.
        $submission = DB::transaction(function () use ($upsert, $account, $edition, $data, $authors, $fileUrl, $customMeta) {
            $participant = $upsert->handle($edition, $account, 'author');

            // ref_code + access_token sont générés par SubmissionObserver.
            return Submission::create([
                'edition_id' => $edition->id,
                'participant_id' => $participant->id,
                'title' => $data['title'],
                'authors' => $authors,
                'abstract' => Html::clean($data['abstract']),
                'keywords' => ($data['keywords'] ?? null) ?: null,
                'track' => ($data['track'] ?? null) ?: null,
                'type' => $data['type'] ?? 'oral',
                'status' => 'submitted',
                'file_url' => $fileUrl,
                'meta' => $customMeta !== [] ? ['custom_fields' => $customMeta] : null,
            ]);
        });

        // Accusé de réception à l'auteur correspondant (hors transaction).
        $notify->handle($submission, 'submitted');

        // Notification in-app au back-office : une communication à traiter.
        Notifier::staff(
            kind: 'submission_new',
            title: 'Nouvelle communication',
            body: '« '.$submission->title.' » — '.$account->full_name,
            url: route('admin.submissions.show', $submission),
            icon: 'inbox',
        );

        return redirect()->route('account.submissions.create', $edition->slug)
            ->with('success_ref', $submission->ref_code);
    }

    public function show(Submission $submission): View
    {
        $this->authorizeOwnership($submission);

        return view('account.submissions.show', [
            'submission' => $submission,
            'feedback' => $this->authorFeedback($submission),
            'versions' => $submission->versions()->get(),
            'slot' => $this->currentSlot($submission),
            'availableSlots' => $this->availableSlots($submission),
        ]);
    }

    /** Le participant choisit lui-même un créneau libre. */
    public function chooseSlot(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorizeOwnership($submission);
        abort_unless($submission->status === 'accepted', 403);

        $slot = Slot::where('id', (int) $request->input('slot_id'))
            ->where('edition_id', $submission->edition_id)
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

    public function resubmit(Request $request, Submission $submission, NotifySubmissionStatus $notify): RedirectResponse
    {
        $this->authorizeOwnership($submission);
        abort_unless($submission->canBeRevised(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['required', 'string', 'max:20000', function (string $attr, string $value, \Closure $fail): void {
                $len = Html::textLength($value);
                if ($len < 50) {
                    $fail('Le résumé doit contenir au moins 50 caractères.');
                } elseif ($len > 8000) {
                    $fail('Le résumé ne doit pas dépasser 8000 caractères.');
                }
            }],
            'keywords' => ['nullable', 'string', 'max:500'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:8192'],
        ]);

        // Archive la version courante (revision_of = canonique).
        Submission::create([
            'edition_id' => $submission->edition_id,
            'participant_id' => $submission->participant_id,
            'ref_code' => $submission->ref_code,
            'access_token' => Str::random(64),
            'title' => $submission->title,
            'authors' => $submission->authors,
            'abstract' => $submission->abstract,
            'keywords' => $submission->keywords,
            'track' => $submission->track,
            'type' => $submission->type,
            'status' => 'superseded',
            'file_url' => $submission->file_url,
            'revision_of' => $submission->id,
            'version' => $submission->version,
        ]);

        // Nouveau fichier éventuel.
        $fileUrl = $submission->file_url;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('submissions/'.$submission->edition_id, 'public');
            $fileUrl = Storage::disk('public')->url($path);
        }

        // Met à jour la version canonique.
        $submission->update([
            'title' => $data['title'],
            'abstract' => Html::clean($data['abstract']),
            'keywords' => ($data['keywords'] ?? null) ?: null,
            'file_url' => $fileUrl,
            'version' => $submission->version + 1,
            'status' => 'submitted',
        ]);

        $notify->handle($submission->fresh(), 'submitted');

        return redirect()->route('account.submissions.show', $submission)
            ->with('ukc_status', 'Version corrigée envoyée. Votre communication est de nouveau en cours d’examen.');
    }

    // --- Règles & helpers du wizard -------------------------------------------

    /**
     * @param  array<int, string>  $tracks
     * @return array<string, mixed>
     */
    private function rulesFor(SubmissionFormConfig $c, array $tracks): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['required', 'string', 'max:20000', function (string $attr, string $value, \Closure $fail) use ($c): void {
                $len = Html::textLength($value);
                if ($len < $c->abstractMin()) {
                    $fail('Le résumé doit contenir au moins '.$c->abstractMin().' caractères.');
                } elseif ($len > $c->abstractMax()) {
                    $fail('Le résumé ne doit pas dépasser '.$c->abstractMax().' caractères.');
                }
            }],
            'coauthors' => ['array'],
            'coauthors.*.name' => ['nullable', 'string', 'max:200'],
            'coauthors.*.email' => ['nullable', 'email', 'max:200'],
            'coauthors.*.affiliation' => ['nullable', 'string', 'max:255'],
            'coauthors.*.grade' => ['nullable', 'string', 'max:100'],
        ];

        $rules['track'] = count($tracks)
            ? [$c->required('track') ? 'required' : 'nullable', 'string', 'max:150']
            : ['nullable', 'string', 'max:150'];

        if ($c->enabled('type')) {
            $rules['type'] = ['required', 'in:oral,poster'];
        }

        if ($c->enabled('keywords')) {
            $rules['keywords'] = [$c->required('keywords') ? 'required' : 'nullable', 'string', 'max:500'];
        }

        if ($c->enabled('file')) {
            $rules['file'] = [$c->required('file') ? 'required' : 'nullable', 'file', 'mimes:pdf,doc,docx', 'max:8192'];
        }

        foreach ($c->customFields() as $field) {
            $key = 'customAnswers.'.$field['key'];

            if ($field['type'] === 'checkbox') {
                $rules[$key] = $field['required'] ? ['accepted'] : ['nullable', 'boolean'];

                continue;
            }

            $rule = [$field['required'] ? 'required' : 'nullable'];
            $rule[] = match ($field['type']) {
                'email' => 'email',
                'number' => 'numeric',
                'date' => 'date',
                default => 'string',
            };
            $rule[] = 'max:2000';
            if ($field['type'] === 'select' && $field['options'] !== []) {
                $rule[] = Rule::in($field['options']);
            }
            $rules[$key] = $rule;
        }

        return $rules;
    }

    /** @return array<string, string> */
    private function attributesFor(SubmissionFormConfig $c): array
    {
        $attrs = [];
        foreach ($c->customFields() as $field) {
            $attrs['customAnswers.'.$field['key']] = mb_strtolower($field['label']);
        }

        return $attrs;
    }

    /** Étape du wizard (1..5) contenant un champ donné (pour le saut d'erreur). */
    private function stepForField(string $field): int
    {
        return match (true) {
            str_starts_with($field, 'coauthors') => 2,
            $field === 'abstract', str_starts_with($field, 'customAnswers') => 3,
            $field === 'file' => 4,
            default => 1,
        };
    }

    /** Communications originales déjà déposées par ce compte sur l'édition. */
    private function usedCountFor(Edition $edition, object $account): int
    {
        return Submission::where('edition_id', $edition->id)
            ->where('revision_of', 0)
            ->whereHas('participant', fn ($q) => $q->where('account_id', $account->id))
            ->count();
    }

    private function remainingFor(Edition $edition, object $account): int
    {
        $max = (int) ($edition->settings['max_communications'] ?? 3);

        return max(0, $max - $this->usedCountFor($edition, $account));
    }

    /** La communication doit appartenir au compte connecté (version canonique). */
    private function authorizeOwnership(Submission $submission): void
    {
        $account = Auth::guard('account')->user();

        abort_unless(
            (int) $submission->revision_of === 0
            && $submission->participant
            && (int) $submission->participant->account_id === (int) $account->id,
            404
        );
    }

    /** Commentaires des relecteurs destinés à l'auteur (révélés après décision). */
    private function authorFeedback(Submission $submission): array
    {
        if (! in_array($submission->status, ['revision', 'accepted', 'rejected'], true)) {
            return [];
        }

        return $submission->reviews()->where('status', 'completed')->get()
            ->filter(fn ($r) => filled($r->comments))
            ->values()
            ->map(fn ($r, $i) => ['label' => 'Relecteur '.($i + 1), 'comments' => $r->comments])
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

        return Slot::where('edition_id', $submission->edition_id)
            ->where('submission_id', 0)
            ->orderBy('starts_at')
            ->get();
    }
}
