<?php

namespace App\AI\Copilot\Actions;

use App\Actions\DeleteSubmission as DeleteSubmissionRecord;
use App\AI\Copilot\CopilotAction;
use App\Models\Event;
use App\Models\Submission;
use App\Models\User;
use InvalidArgumentException;
use Illuminate\Support\Str;

/** Suppression d'un résumé identifié sans ambiguïté dans le colloque sélectionné. */
class DeleteSubmission implements CopilotAction
{
    public function key(): string
    {
        return 'delete_submission';
    }

    public function label(): string
    {
        return 'Supprimer le résumé';
    }

    public function capability(): string
    {
        return 'editions_write';
    }

    public function description(): string
    {
        return 'Supprime définitivement un résumé du colloque sélectionné, avec ses versions et évaluations. '
            .'À proposer uniquement si l’utilisateur demande sa suppression et donne sa référence exacte. '
            .'Mettre cette référence dans params.ref_code (ex. "26-050"). Si elle manque, demander la référence '
            .'sans proposer d’action. La suppression exige ensuite une confirmation humaine.';
    }

    public function context(Event $event): ?string
    {
        return null;
    }

    public function summary(array $params, Event $event): string
    {
        $submission = $this->target($params, $event);

        return 'Supprimer définitivement le résumé '.$submission->ref_code.' « '.Str::limit($submission->title, 80).' » '
            .'du colloque « '.$event->title.' », avec ses versions et évaluations ; libérer ses créneaux '
            .'et révoquer ses attestations liées. Les paiements du participant seront conservés.';
    }

    public function execute(array $params, Event $event, User $user): string
    {
        $submission = $this->target($params, $event);
        $reference = $submission->ref_code;

        app(DeleteSubmissionRecord::class)->handle($submission, $user);

        return 'Résumé '.$reference.' supprimé du colloque « '.$event->title.' ».';
    }

    private function target(array $params, Event $event): Submission
    {
        $reference = $params['ref_code'] ?? null;
        if (! is_string($reference) || trim($reference) === '' || mb_strlen(trim($reference)) > 40) {
            throw new InvalidArgumentException('Indiquez la référence exacte du résumé à supprimer.');
        }

        $matches = Submission::where('event_id', $event->id)
            ->where('revision_of', 0)
            ->where('ref_code', trim($reference))
            ->limit(2)->get();

        if ($matches->isEmpty()) {
            throw new InvalidArgumentException('Aucun résumé portant la référence « '.trim($reference).' » dans le colloque sélectionné.');
        }
        if ($matches->count() !== 1) {
            throw new InvalidArgumentException('Cette référence correspond à plusieurs résumés. Supprimez-les depuis la liste après vérification.');
        }

        return $matches->first();
    }
}
