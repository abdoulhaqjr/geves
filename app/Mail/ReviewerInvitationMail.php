<?php

namespace App\Mail;

use App\Models\Participant;
use App\Models\Review;
use App\Support\Locales;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Password;

/**
 * Invitation d'un relecteur : lien vers son espace « Mes relectures », échéance
 * de remise et rappel qu'il peut accepter ou refuser. Pour un compte fraîchement
 * provisionné (mot de passe à définir), le lien mène à la création du mot de
 * passe (jeton). Mise en file (ShouldQueue).
 */
class ReviewerInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Participant $reviewer,
        public int $assignedCount = 1,
        public ?Review $review = null,
    ) {
        $this->locale(Locales::resolve($reviewer->user?->locale));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Invitation à relire des communications'));
    }

    public function content(): Content
    {
        $user = $this->reviewer->user;
        $needsPassword = $user !== null && $user->must_reset_password;

        if ($needsPassword) {
            $token = Password::broker('users')->createToken($user);
            $url = route('account.password.reset', $token).'?email='.urlencode((string) $user->email);
        } else {
            $url = route('account.reviews');
        }

        return new Content(markdown: 'emails.reviewer-invitation', with: [
            'portalUrl' => $url,
            'needsPassword' => $needsPassword,
            // Échéance de remise : le relecteur doit la connaître AVANT d'accepter.
            'due' => $this->review?->due_at?->translatedFormat('j F Y'),
        ]);
    }
}
