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
 * Assigne un évaluateur à une soumission : crée/retrouve le participant évaluateur
 * (par e-mail sur l'événement), le relie à un compte (provisionné au besoin pour
 * accéder à « Mes évaluations »), crée l’évaluation et passe la soumission
 * « en évaluation ».
 */
class AssignReviewer
{
    /**
     * @param  string|null  $reason  justification du moteur d'attribution automatique
     */
    public function handle(Submission $submission, string $name, string $email, ?string $reason = null): Review
    {
        $normalized = mb_strtolower(trim($email));
        $account = User::whereRaw('LOWER(email) = ?', [$normalized])->first();
        $matches = Participant::where('event_id', $submission->event_id)
            ->where(function ($query) use ($normalized, $account) {
                $query->whereRaw('LOWER(email) = ?', [$normalized]);
                if ($account) {
                    $query->orWhere('user_id', $account->id);
                }
            })->get();
        $reviewer = $matches->firstWhere('role', 'reviewer')
            ?? $matches->first(fn (Participant $match) => ! in_array($match->role, UpsertParticipantForAccount::STRONG_ROLES, true))
            ?? new Participant(['event_id' => $submission->event_id, 'email' => $normalized]);

        if (! $reviewer->exists) {
            [$first, $last] = $this->splitName($name);
            $reviewer->first_name = $first;
            $reviewer->last_name = $last;
            $reviewer->access_token = Str::random(64);
        } else {
            // Garantit un jeton sur une fiche évaluateur existante ou réutilisable.
            if (blank($reviewer->access_token)) {
                $reviewer->access_token = Str::random(64);
            }
        }
        $reviewer->role = 'reviewer';
        $reviewer->email = $normalized;

        // Relie l'inscription à un compte (identité unifiée) : l’évaluation se
        // fait depuis « Mes évaluations » connecté. Provisionne un compte si le
        // évaluateur n'en a pas encore (il définira son mot de passe via l'e-mail).
        if (blank($reviewer->user_id)) {
            $account ??= $this->provisionAccount($normalized, $name);
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
        // ligne plutôt que d'en créer une seconde (l'unicité soumission/évaluateur
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

        // Notification in-app à l’évaluateur, s'il dispose d'un compte lié.
        // Notifie aussi une RÉ-attribution après refus/annulation : sans cela, le
        // évaluateur ne saurait pas que la communication lui revient.
        if ($review->wasRecentlyCreated || $reopened) {
            Notifier::account(
                $reviewer->user,
                kind: 'review_assigned',
                title: 'Nouvelle évaluation assignée',
                body: 'Une communication vous a été confiée pour évaluation.',
                url: route('account.reviews'),
                icon: 'inbox',
            );
        }

        return $review;
    }

    /**
     * Provisionne un compte participant pour un évaluateur invité sans compte.
     * Compte vérifié (invitation par le comité) mais avec mot de passe à définir
     * (`must_reset_password`) : le lien d'invitation l'amène à le créer.
     */
    protected function provisionAccount(string $email, string $name): User
    {
        [$first, $last] = $this->splitName($name);

        $user = new User;
        $user->forceFill([
            'first_name' => $first ?: 'Évaluateur',
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
