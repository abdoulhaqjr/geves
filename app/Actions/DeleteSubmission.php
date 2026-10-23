<?php

namespace App\Actions;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/** Supprime une communication et ses versions sans effacer les paiements du participant. */
class DeleteSubmission
{
    public function handle(Submission $submission, ?User $actor = null): void
    {
        if ((int) $submission->revision_of !== 0) {
            throw new InvalidArgumentException('Seule la communication principale peut être supprimée.');
        }

        $fileUrls = DB::transaction(function () use ($submission): array {
            $submission = Submission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ((int) $submission->revision_of !== 0) {
                throw new InvalidArgumentException('Seule la communication principale peut être supprimée.');
            }

            $records = Submission::where('event_id', $submission->event_id)
                ->where(function ($query) use ($submission) {
                    $query->whereKey($submission->id)->orWhere('revision_of', $submission->id);
                })->get(['id', 'file_url']);
            $ids = $records->pluck('id');

            // Ces tables n'ont pas de clé étrangère vers submissions. Un créneau
            // redevient libre ; un paiement reste attaché au participant.
            DB::table('slots')->where('event_id', $submission->event_id)->whereIn('submission_id', $ids)
                ->update(['submission_id' => null, 'participant_id' => 0, 'chosen_by_participant' => false, 'updated_at' => now()]);
            DB::table('payments')->where('event_id', $submission->event_id)->whereIn('submission_id', $ids)
                ->update(['submission_id' => 0, 'updated_at' => now()]);

            // Une attestation déjà émise reste vérifiable dans l'historique,
            // mais ne doit plus être valide pour une communication supprimée.
            DB::table('attestations')->where('event_id', $submission->event_id)->whereIn('submission_id', $ids)
                ->update(['revoked' => true, 'updated_at' => now()]);

            // Évaluations, enchères de relecture et auteurs liés sont supprimés
            // par leurs contraintes de clé étrangère en cascade.
            Submission::whereIn('id', $ids)->delete();

            return $records->pluck('file_url')->filter()->unique()->values()->all();
        });

        Log::notice('Soumission supprimée', [
            'submission_id' => $submission->id,
            'event_id' => $submission->event_id,
            'ref_code' => $submission->ref_code,
            'actor_user_id' => $actor?->id,
        ]);

        // Une correction peut réutiliser le fichier d'une ancienne version.
        // Ne retirer que les fichiers locaux qui ne servent plus à aucun résumé.
        $prefix = rtrim(Storage::disk('public')->url('submissions'), '/').'/';
        foreach ($fileUrls as $url) {
            if (! is_string($url) || ! str_starts_with($url, $prefix)
                || Submission::where('file_url', $url)->exists()) {
                continue;
            }

            $path = 'submissions/'.substr($url, strlen($prefix));
            if (str_contains($path, '..') || ! preg_match('~^submissions/[a-zA-Z0-9/_\.\-]+$~', $path)) {
                continue;
            }

            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $e) {
                // L'enregistrement est déjà supprimé : un problème de stockage
                // ne doit pas faire croire que la suppression a été annulée.
                Log::warning('Fichier de soumission non supprimé', ['path' => $path, 'error' => $e->getMessage()]);
            }
        }
    }
}
