<?php

namespace App\Actions;

use App\Models\Participant;
use App\Models\Review;
use App\Models\Submission;
use App\Support\Notifier;
use Illuminate\Support\Str;

/**
 * Assigne un relecteur à une soumission : crée/retrouve le participant relecteur
 * (par e-mail sur l'édition, avec jeton d'accès au portail), crée la relecture
 * et passe la soumission « en relecture ».
 */
class AssignReviewer
{
    public function handle(Submission $submission, string $name, string $email): Review
    {
        $reviewer = Participant::firstOrNew([
            'edition_id' => $submission->edition_id,
            'email' => mb_strtolower(trim($email)),
        ]);

        if (! $reviewer->exists) {
            [$first, $last] = $this->splitName($name);
            $reviewer->first_name = $first;
            $reviewer->last_name = $last;
            $reviewer->access_token = Str::random(64);
            $reviewer->role = 'reviewer';
        } else {
            // Garantit un jeton et n'écrase pas un rôle fort par « reviewer ».
            if (blank($reviewer->access_token)) {
                $reviewer->access_token = Str::random(64);
            }
            if (! in_array($reviewer->role, UpsertParticipantForAccount::STRONG_ROLES, true)) {
                $reviewer->role = 'reviewer';
            }
        }

        // Relie l'inscription au compte correspondant (identité unifiée) : la
        // relecture se fait désormais depuis l'espace « Mes relectures » connecté.
        if (blank($reviewer->user_id)) {
            $account = \App\Models\User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->first();
            if ($account !== null) {
                $reviewer->user_id = $account->id;
            }
        }

        $reviewer->save();

        $review = Review::firstOrCreate(
            ['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id],
            ['status' => 'assigned']
        );

        if ($submission->status === 'submitted') {
            $submission->update(['status' => 'under_review']);
        }

        // Notification in-app au relecteur, s'il dispose d'un compte lié.
        if ($review->wasRecentlyCreated) {
            Notifier::account(
                $reviewer->user,
                kind: 'review_assigned',
                title: 'Nouvelle relecture assignée',
                body: 'Une communication vous a été confiée pour évaluation.',
                url: route('account.reviews'),
                icon: 'inbox',
            );
        }

        return $review;
    }

    /** Sépare « Prénom Nom » en [prénom, nom] (dernier mot = nom). */
    protected function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (count($parts) <= 1) {
            return [$name, ''];
        }
        $last = array_pop($parts);

        return [implode(' ', $parts), $last];
    }
}
