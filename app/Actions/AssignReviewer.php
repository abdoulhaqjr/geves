<?php

namespace App\Actions;

use App\Models\Participant;
use App\Models\Review;
use App\Models\Submission;
use App\Models\User;
use App\Support\Notifier;
use App\Support\ReviewGrid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Assigne un relecteur à une soumission : crée/retrouve le participant relecteur
 * (par e-mail sur l'événement), le relie à un compte (provisionné au besoin pour
 * accéder à « Mes relectures »), crée la relecture et passe la soumission
 * « en relecture ».
 */
class AssignReviewer
{
    /**
     * @param  string|null  $reason  justification du moteur d'attribution automatique
     */
    public function handle(Submission $submission, string $name, string $email, ?string $reason = null): Review
    {
        $reviewer = Participant::firstOrNew([
            'event_id' => $submission->event_id,
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

        // Relie l'inscription à un compte (identité unifiée) : la relecture se
        // fait depuis « Mes relectures » connecté. Provisionne un compte si le
        // relecteur n'en a pas encore (il définira son mot de passe via l'e-mail).
        if (blank($reviewer->user_id)) {
            $normalized = mb_strtolower(trim($email));
            $account = User::whereRaw('LOWER(email) = ?', [$normalized])->first()
                ?? $this->provisionAccount($normalized, $name);
            $reviewer->user_id = $account->id;
        }

        $reviewer->save();

        // Échéance : durée d'évaluation de l'événement, sinon la valeur par défaut.
        $dueDays = ReviewGrid::dueDays($submission->event);

        $review = Review::firstOrCreate(
            ['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id],
            [
                'status' => 'invited',
                'assigned_at' => now(),
                'assigned_by_id' => Auth::guard('web')->id(),
                'assign_reason' => $reason,
                'due_at' => now()->addDays($dueDays),
            ],
        );

        // Ré-attribution après un refus ou une annulation : on rouvre la même
        // ligne plutôt que d'en créer une seconde (l'unicité soumission/relecteur
        // reste vraie) et l'échéance repart de zéro.
        $reopened = ! $review->wasRecentlyCreated && in_array($review->status, ['declined', 'cancelled'], true);
        if ($reopened) {
            $review->update([
                'status' => 'invited',
                'assigned_at' => now(),
                'assigned_by_id' => Auth::guard('web')->id(),
                'assign_reason' => $reason,
                'due_at' => now()->addDays($dueDays),
                'responded_at' => null,
                'decline_reason' => null,
            ]);
        }

        if ($submission->status === 'submitted') {
            $submission->update(['status' => 'under_review']);
        }

        // Notification in-app au relecteur, s'il dispose d'un compte lié.
        // Notifie aussi une RÉ-attribution après refus/annulation : sans cela, le
        // relecteur ne saurait pas que la communication lui revient.
        if ($review->wasRecentlyCreated || $reopened) {
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

    /**
     * Provisionne un compte participant pour un relecteur invité sans compte.
     * Compte vérifié (invitation par le comité) mais avec mot de passe à définir
     * (`must_reset_password`) : le lien d'invitation l'amène à le créer.
     */
    protected function provisionAccount(string $email, string $name): User
    {
        [$first, $last] = $this->splitName($name);

        $user = new User;
        $user->forceFill([
            'first_name' => $first ?: 'Relecteur',
            'last_name' => $last,
            'email' => $email,
            'password' => Str::random(40),
            'email_verified_at' => now(),
            'must_reset_password' => true,
            'access_level' => User::ACCESS_USER,
        ])->save();

        return $user;
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
