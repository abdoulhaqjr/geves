<?php

namespace App\Livewire\Account\Submissions;

use App\Actions\NotifySubmissionStatus;
use App\Actions\UpsertParticipantForAccount;
use App\Models\Edition;
use App\Models\Submission;
use App\Support\SubmissionFormConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulaire de soumission d'une communication, lié au compte connecté.
 *
 * - Identité imposée par le compte (bandeau) ; l'e-mail ne peut pas être falsifié.
 * - Gating : e-mail vérifié + profil complet (affiliation) — cf. Account::canSubmit().
 * - Garde-fou : nombre maximum de communications par personne et par édition.
 */
#[Title('Soumettre une communication')]
class Create extends Component
{
    use WithFileUploads;

    public Edition $edition;

    public string $title = '';

    public string $track = '';

    public string $type = 'oral';

    public string $keywords = '';

    public string $abstract = '';

    /** Co-auteurs : [['name' => '', 'email' => '', 'affiliation' => '', 'grade' => '']]. */
    public array $coauthors = [];

    /** Réponses aux champs personnalisés, indexées par clé de champ. */
    public array $customAnswers = [];

    public $file = null;

    public ?string $successRef = null;

    public function mount(string $slug): void
    {
        $edition = Edition::where('slug', $slug)->first();

        abort_unless($edition && $edition->isPublished(), 404);

        $this->edition = $edition;

        if (! empty($this->tracks())) {
            $this->track = $this->tracks()[0];
        }

        // Valeurs initiales des champs personnalisés.
        foreach ($this->config()->customFields() as $field) {
            $this->customAnswers[$field['key']] = $field['type'] === 'checkbox' ? false : '';
        }
    }

    /** Configuration paramétrable du formulaire de cette édition. */
    public function config(): SubmissionFormConfig
    {
        return new SubmissionFormConfig($this->edition);
    }

    /** Axes thématiques configurés sur l'édition. */
    public function tracks(): array
    {
        return $this->edition->settings['tracks'] ?? [];
    }

    /** Nombre maximum de communications par personne (défaut 3). */
    public function maxCommunications(): int
    {
        return (int) ($this->edition->settings['max_communications'] ?? 3);
    }

    /** Communications déjà déposées par ce compte sur l'édition (originales). */
    public function usedCount(): int
    {
        $account = Auth::guard('account')->user();

        return Submission::where('edition_id', $this->edition->id)
            ->where('revision_of', 0)
            ->whereHas('participant', fn ($q) => $q->where('account_id', $account->id))
            ->count();
    }

    public function remaining(): int
    {
        return max(0, $this->maxCommunications() - $this->usedCount());
    }

    public function canSubmitNow(): bool
    {
        $account = Auth::guard('account')->user();

        return $account->canSubmit()
            && $this->edition->submission_open
            && $this->remaining() > 0;
    }

    public function addCoauthor(): void
    {
        $this->coauthors[] = ['name' => '', 'email' => '', 'affiliation' => '', 'grade' => ''];
    }

    public function removeCoauthor(int $i): void
    {
        unset($this->coauthors[$i]);
        $this->coauthors = array_values($this->coauthors);
    }

    protected function rules(): array
    {
        $c = $this->config();

        $rules = [
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['required', 'string', 'min:'.$c->abstractMin(), 'max:'.$c->abstractMax()],
            'coauthors' => ['array'],
            'coauthors.*.name' => ['nullable', 'string', 'max:200'],
            'coauthors.*.email' => ['nullable', 'email', 'max:200'],
            'coauthors.*.affiliation' => ['nullable', 'string', 'max:255'],
            'coauthors.*.grade' => ['nullable', 'string', 'max:100'],
        ];

        $rules['track'] = count($this->tracks())
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

    /** Libellés de champs pour des messages de validation lisibles. */
    protected function validationAttributes(): array
    {
        $attrs = [];
        foreach ($this->config()->customFields() as $field) {
            $attrs['customAnswers.'.$field['key']] = mb_strtolower($field['label']);
        }

        return $attrs;
    }

    public function submit(UpsertParticipantForAccount $upsert, NotifySubmissionStatus $notify)
    {
        $account = Auth::guard('account')->user();

        // Garde-fous serveur (en plus de l'affichage).
        if (! $account->canSubmit()) {
            $this->addError('abstract', 'Votre e-mail doit être vérifié et votre profil complété (affiliation).');

            return null;
        }
        if (! $this->edition->submission_open) {
            $this->addError('abstract', 'Les soumissions sont fermées pour cette édition.');

            return null;
        }
        if ($this->remaining() <= 0) {
            $this->addError('abstract', 'Vous avez atteint le nombre maximum de communications pour cette édition.');

            return null;
        }

        $data = $this->validate();
        $config = $this->config();

        // Téléversement du fichier (I/O) hors transaction.
        $fileUrl = null;
        if ($config->enabled('file') && $this->file) {
            $path = $this->file->store('submissions/'.$this->edition->id, 'public');
            $fileUrl = Storage::disk('public')->url($path);
        }

        // Réponses aux champs personnalisés (label + valeur, pour affichage).
        $customMeta = [];
        foreach ($config->customFields() as $field) {
            $value = $this->customAnswers[$field['key']] ?? null;
            if ($field['type'] === 'checkbox') {
                $value = $value ? 'Oui' : 'Non';
            }
            if ($value === '' || $value === null) {
                continue;
            }
            $customMeta[] = ['key' => $field['key'], 'label' => $field['label'], 'value' => (string) $value];
        }

        $coauthors = collect($this->coauthors)
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

        // Création atomique : le participant (upsert) et la communication sont
        // écrits ensemble — pas de participant orphelin si la création échoue.
        $submission = DB::transaction(function () use ($upsert, $account, $data, $authors, $fileUrl, $customMeta) {
            $participant = $upsert->handle($this->edition, $account, 'author');

            // ref_code + access_token sont générés par SubmissionObserver.
            return Submission::create([
                'edition_id' => $this->edition->id,
                'participant_id' => $participant->id,
                'title' => $data['title'],
                'authors' => $authors,
                'abstract' => $data['abstract'],
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

        $this->successRef = $submission->ref_code;
    }

    public function render()
    {
        return view('livewire.account.submissions.create', [
            'account' => Auth::guard('account')->user(),
            'config' => $this->config(),
        ])->layout('components.layouts.participant', ['title' => 'Soumettre une communication']);
    }
}
