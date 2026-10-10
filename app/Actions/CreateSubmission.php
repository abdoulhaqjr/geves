<?php

namespace App\Actions;

use App\Http\Requests\Account\StoreSubmissionRequest;
use App\Models\Event;
use App\Models\Submission;
use App\Models\User;
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
        protected LinkSubmissionAuthors $linkAuthors,
    ) {}

    /**
     * @param  array<string, mixed>  $data  données validées
     */
    public function handle(Event $event, User $account, SubmissionFormConfig $config, array $data, ?UploadedFile $file): Submission
    {
        // Téléversement du fichier (I/O) hors transaction.
        $fileUrl = null;
        if ($config->enabled('file') && $file) {
            $path = $file->store('submissions/'.$event->id, 'public');
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
            ->filter(fn ($c) => filled($c['last_name'] ?? null) || filled($c['first_name'] ?? null))
            ->map(function ($c) {
                $first = trim((string) ($c['first_name'] ?? ''));
                $last = mb_strtoupper(trim((string) ($c['last_name'] ?? '')), 'UTF-8'); // nom de famille en MAJUSCULES

                return [
                    'name' => trim($first.' '.$last),
                    'first_name' => $first,
                    'last_name' => $last,
                    'email' => $c['email'] ?? null,
                    'phone' => ($c['phone'] ?? null) ?: null,
                    'affiliation' => $c['affiliation'] ?? null,
                    'grade' => $c['grade'] ?? null,
                ];
            })->values()->all();

        // Auteur correspondant : prérempli avec le compte, éventuellement remplacé
        // (soumission pour le compte d'un tiers). L'identité est déjà verrouillée sur
        // le compte quand « self » (cf. StoreSubmissionRequest::prepareForValidation).
        $corr = (array) ($data['corresponding'] ?? []);
        $isSelf = (string) ($corr['self'] ?? '1') !== '0';
        $corrGrade = ($corr['grade'] ?? null) ?: null;
        $corrAffiliation = ($corr['affiliation'] ?? null) ?: null;
        $corrFirst = trim((string) ($corr['first_name'] ?? $account->first_name));
        $corrLast = mb_strtoupper(trim((string) ($corr['last_name'] ?? $account->last_name)), 'UTF-8'); // nom de famille en MAJUSCULES

        $authors = array_merge([[
            'name' => trim($corrFirst.' '.$corrLast) ?: $account->full_name,
            'first_name' => $corrFirst,
            'last_name' => $corrLast,
            'email' => $corr['email'] ?? $account->email,
            'phone' => ($corr['phone'] ?? null) ?: null,
            'affiliation' => $corrAffiliation ?? $account->affiliation,
            'grade' => $corrGrade ?? $account->grade,
            'corresponding' => true,
            'is_submitter' => $isSelf,
        ]], $coauthors);

        // Grade/affiliation POUR CE COLLOQUE : quand le déposant est l'auteur
        // correspondant, ce qu'il a saisi ici est enregistré sur son participant de
        // cet événement (grades différents possibles d'un colloque à l'autre).
        $editionGrade = $isSelf ? $corrGrade : null;
        $editionAffiliation = $isSelf ? $corrAffiliation : null;

        // Création atomique : le participant (upsert) et la communication ensemble.
        $submission = DB::transaction(function () use ($account, $event, $data, $authors, $fileUrl, $customMeta, $editionGrade, $editionAffiliation): Submission {
            $participant = $this->upsert->handle($event, $account, 'author', $editionGrade, $editionAffiliation);

            // ref_code + access_token sont générés par SubmissionObserver.
            return Submission::create([
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'title' => $data['title'],
                'authors' => $authors,
                'abstract' => Html::clean($data['abstract']),
                'keywords' => \App\Support\SubmissionKeywords::join($data['keywords'] ?? null, (new \App\Support\SubmissionFormConfig($event))->keywordSeparator()),
                'track' => ($data['track'] ?? null) ?: null,
                'type' => $data['type'] ?? 'oral',
                'status' => 'submitted',
                'file_url' => $fileUrl,
                'meta' => $customMeta !== [] ? ['custom_fields' => $customMeta] : null,
            ]);
        });

        // Rapproche les auteurs des comptes existants (liaison + notif/e-mail/SMS)
        // ou invite les personnes non inscrites. Hors transaction (I/O réseau).
        $this->linkAuthors->handle($submission, $account);

        // Accusé de réception à l'auteur correspondant (hors transaction).
        $this->notify->handle($submission, 'submitted');

        // Notification in-app au back-office : une communication à traiter.
        Notifier::staff(
            kind: 'submission_new',
            title: 'Nouvelle communication',
            body: '« '.$submission->title.' » - '.$account->full_name,
            url: route('admin.submissions.show', $submission),
            icon: 'inbox',
        );

        return $submission;
    }
}
