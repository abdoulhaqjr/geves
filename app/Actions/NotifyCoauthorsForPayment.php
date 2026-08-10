<?php

namespace App\Actions;

use App\Mail\CoauthorPaymentInvitationMail;
use App\Models\Email;
use App\Models\Submission;
use Illuminate\Support\Facades\Mail;

/**
 * Notifie par e-mail les co-auteurs d'une communication acceptée pour les
 * inviter à régler leurs frais (lien pré-rempli vers le parcours co-auteur).
 * Journalise chaque envoi dans la table `emails`.
 */
class NotifyCoauthorsForPayment
{
    /** @return int Nombre de co-auteurs notifiés. */
    public function handle(Submission $submission): int
    {
        if ($submission->status !== 'accepted') {
            return 0;
        }

        $edition = $submission->edition;
        $slug = $edition?->slug;
        if (! $slug) {
            return 0;
        }

        $count = 0;
        $seen = [];

        foreach ($submission->authors ?? [] as $author) {
            if ($author['corresponding'] ?? false) {
                continue;
            }

            $email = trim($author['email'] ?? '');
            $key = mb_strtolower($email);

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $url = route('public.coauthor', ['slug' => $slug, 'email' => $email]);
            $mail = new CoauthorPaymentInvitationMail($submission, (string) ($author['name'] ?? ''), $url);

            Mail::to($email)->send($mail);

            Email::create([
                'edition_id' => $submission->edition_id,
                'participant_id' => null,
                'template' => 'coauthor_payment',
                'recipient' => $email,
                'subject' => 'Communication acceptée — réglez vos frais de co-auteur',
                'body' => 'Communication '.$submission->ref_code.' acceptée.',
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            $count++;
        }

        return $count;
    }
}
