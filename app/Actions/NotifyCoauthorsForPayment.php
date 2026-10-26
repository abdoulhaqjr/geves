<?php

namespace App\Actions;

use App\Mail\CoauthorPaymentInvitationMail;
use App\Models\Email;
use App\Models\Submission;
use App\Support\EventNameFormat;
use Illuminate\Support\Facades\Mail;

/**
 * Notifie par e-mail les co-auteurs d'une communication acceptée pour les
 * inviter à régler leurs frais. Journalise chaque envoi dans `emails`.
 *
 * ⚠️ Le lien mène à la **porte d'entrée** co-auteur, qui oriente vers l'espace
 * participant - elle n'encaisse plus. C'est le seul chemin de règlement :
 * deux parcours concurrents laissaient **deux participants** pour la même
 * personne, et l'attestation partait sur celui qui n'avait pas de compte.
 */
class NotifyCoauthorsForPayment
{
    /** @return int Nombre de co-auteurs notifiés. */
    public function handle(Submission $submission): int
    {
        if ($submission->status !== 'accepted') {
            return 0;
        }

        $event = $submission->event;
        $slug = $event?->slug;
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

            // ⚠️ La ligne du journal naît AVANT l'envoi, et à « queued » : cet
            // e-mail est une tâche de fond, donc `send()` ne fait que l'empiler
            // (cf. CoauthorPaymentInvitationMail). Écrite « sent » ici, elle
            // affirmait un envoi que personne n'avait encore effectué - et elle
            // le maintenait quand le travailleur échouait.
            $log = Email::create([
                'event_id' => $submission->event_id,
                'participant_id' => null,
                'template' => 'coauthor_payment',
                'recipient' => $email,
                'subject' => 'Communication acceptée - réglez vos frais de co-auteur',
                'body' => 'Communication '.$submission->ref_code.' acceptée.',
                'status' => 'queued',
            ]);

            Mail::to($email)->send(new CoauthorPaymentInvitationMail(
                $submission, EventNameFormat::author($event, $author), $url, $log->id
            ));

            $count++;
        }

        return $count;
    }
}
