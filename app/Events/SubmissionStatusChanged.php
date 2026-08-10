<?php

namespace App\Events;

use App\Models\Submission;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Émis quand le statut d'une communication change (depuis le back-office).
 * Découple les effets de bord (notifier l'auteur, inviter les co-auteurs à
 * payer à l'acceptation…) du composant qui applique le statut.
 */
class SubmissionStatusChanged
{
    use Dispatchable;

    public function __construct(
        public Submission $submission,
        public string $status,
        public ?string $note = null,
    ) {}
}
