<?php

namespace App\Actions;

use App\Http\Requests\Account\StoreSubmissionRequest;
use App\Models\Account;
use App\Models\Edition;
use App\Models\Submission;
use App\Support\Html;
use App\Support\Notifier;
use App\Support\SubmissionFormConfig;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Dépose une communication pour un compte : téléverse le fichier, compose la
 * liste des auteurs (correspondant + co-auteurs) et les réponses aux champs
 * personnalisés, crée le participant (upsert) et la soumission de façon atomique,
 * puis notifie l'auteur et le back-office. La validation est faite en amont par
 * {@see StoreSubmissionRequest}.
 */
class CreateSubmission
{
    public function __construct(
        protected UpsertParticipantForAccount $upsert,
        protected NotifySubmissionStatus $notify,
    ) {}

    /**
     * @param  array<string, mixed>  $data  données validées
     */
    public function handle(Edition $edition, Account $account, SubmissionFormConfig $config, array $data, ?UploadedFile $file): Submission
    {
        // Téléversement du fichier (I/O) hors transaction.
        $fileUrl = null;
        if ($config->enabled('file') && $file) {
            $path = $file->store('submissions/'.$edition->id, 'public');
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
        $submission = DB::transaction(function () use ($account, $edition, $data, $authors, $fileUrl, $customMeta): Submission {
            $participant = $this->upsert->handle($edition, $account, 'author');

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
        $this->notify->handle($submission, 'submitted');

        // Notification in-app au back-office : une communication à traiter.
        Notifier::staff(
            kind: 'submission_new',
            title: 'Nouvelle communication',
            body: '« '.$submission->title.' » — '.$account->full_name,
            url: route('admin.submissions.show', $submission),
            icon: 'inbox',
        );

        return $submission;
    }
}
