<?php

namespace App\Listeners;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

/** Relie au nouveau compte les rôles d'évaluateur ajoutés auparavant par e-mail. */
class LinkPendingReviewers
{
    public function handle(Registered $event): void
    {
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        Participant::whereNull('user_id')
            ->where('role', 'reviewer')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim((string) $user->email))])
            ->update(['user_id' => $user->id]);
    }
}
