<?php

namespace App\Listeners;

use App\Actions\UpsertParticipantForAccount;
use App\Models\SubmissionAuthor;
use App\Models\User;
use App\Support\AuthorMatcher;
use App\Support\Notifier;
use Illuminate\Auth\Events\Registered;

/**
 * À la création d'un compte, rattache automatiquement les communications où la
 * personne était citée (auteur/co-auteur) mais pas encore inscrite : on retrouve
 * les liaisons « en attente » par e-mail / téléphone (fort) ou nom + affiliation
 * (déclinable), sur les éditions non clôturées, puis on lie + notifie.
 * Auto-découvert par Laravel (pas d'enregistrement manuel).
 */
class LinkPendingAuthorships
{
    public function __construct(protected UpsertParticipantForAccount $upsert) {}

    public function handle(Registered $event): void
    {
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        $email = AuthorMatcher::normEmail($user->email);
        $phone = AuthorMatcher::normPhone($user->phone);
        $wanted = AuthorMatcher::nameTokens($user->full_name ?: (string) $user->name);
        $lastToken = $wanted !== [] ? end($wanted) : '';

        $rows = SubmissionAuthor::query()
            ->whereNull('user_id')
            ->whereIn('status', ['invited', 'unmatched'])
            ->where(function ($q) use ($email, $phone, $lastToken): void {
                if ($email !== '') {
                    $q->orWhereRaw('LOWER(email) = ?', [$email]);
                }
                if ($phone !== '') {
                    $q->orWhere('phone', $phone);
                }
                if ($lastToken !== '') {
                    $q->orWhere('name', 'like', '%'.$lastToken.'%');
                }
            })
            ->with('submission.edition')
            ->limit(200)
            ->get();

        foreach ($rows as $row) {
            $edition = $row->submission?->edition;
            if (! $edition || $edition->status === 'closed') {
                continue; // uniquement les événements en cours ou à venir
            }

            $type = $this->matchType($row, $email, $phone, $wanted);
            if ($type === null) {
                continue;
            }

            $this->upsert->handle($edition, $user, $row->role === 'corresponding' ? 'author' : 'coauthor');

            $row->update([
                'user_id' => $user->id,
                'match_type' => $type,
                'status' => 'linked',
                'notified_at' => now(),
            ]);

            Notifier::account(
                $user,
                'authorship',
                __('Une communication vous a été rattachée'),
                '« '.$row->submission->title.' » - '.$edition->title,
                route('account.submissions.show', $row->submission),
                'inbox',
            );
        }
    }

    /**
     * Type de correspondance entre une liaison en attente et le nouveau compte.
     *
     * @param  list<string>  $wanted  tokens du nom du compte
     */
    protected function matchType(SubmissionAuthor $row, string $email, string $phone, array $wanted): ?string
    {
        if ($email !== '' && AuthorMatcher::normEmail($row->email) === $email) {
            return 'email';
        }
        if ($phone !== '' && AuthorMatcher::normPhone($row->phone) === $phone) {
            return 'phone';
        }
        if ($wanted !== [] && AuthorMatcher::nameTokens($row->name) === $wanted) {
            // Nom identique : accepté (déclinable). Renforcé par l'affiliation si connue.
            return 'name';
        }

        return null;
    }
}
