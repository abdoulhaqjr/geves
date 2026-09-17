<?php

namespace App\AI\Copilot\Actions;

use App\AI\Copilot\CopilotAction;
use App\Mail\PaymentReminderMail;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\User;
use App\Support\Fees;
use App\Support\Notifier;
use Illuminate\Support\Facades\Mail;

/**
 * Relance de paiement en masse : envoie un rappel aux inscrits de l'édition dont
 * une communication est acceptée mais dont les frais ne sont pas réglés. Même
 * ciblage et même effet que le bouton « Relancer » de la page Paiements.
 */
class SendPaymentReminders implements CopilotAction
{
    public function key(): string
    {
        return 'send_payment_reminders';
    }

    public function label(): string
    {
        return 'Relancer les paiements en attente';
    }

    public function capability(): string
    {
        return 'payments';
    }

    public function description(): string
    {
        return "Envoie un e-mail de relance à tous les inscrits de l'édition ayant une communication "
            ."acceptée mais des frais d'inscription non réglés. À proposer si l'utilisateur veut relancer "
            .'les paiements en attente / retards de paiement.';
    }

    public function summary(array $params, Edition $edition): string
    {
        return 'Envoyer une relance de paiement à '.$this->targets($edition)->count()
            .' inscrit(s) en attente de règlement pour « '.($edition->title ?: $edition->year).' ».';
    }

    public function execute(array $params, Edition $edition, User $user): string
    {
        $targets = $this->targets($edition);
        $sent = 0;

        foreach ($targets as $participant) {
            if (blank($participant->email)) {
                continue;
            }

            $amount = Fees::resolveAmount($edition, $participant->title) ?? Fees::highestAmount($edition);
            $payUrl = route('account.payment', $edition);

            Mail::to($participant->email)->send(new PaymentReminderMail($participant, $edition, $payUrl, $amount));

            Notifier::account(
                $participant->user,
                kind: 'payment',
                title: 'Frais d’inscription à régler',
                body: 'Un rappel vous a été envoyé pour finaliser le paiement de votre inscription.',
                url: $payUrl,
                icon: 'card',
            );

            $meta = (array) $participant->meta;
            $meta['payment_reminded_at'] = now()->toDateTimeString();
            $participant->update(['meta' => $meta]);

            $sent++;
        }

        return $sent === 0
            ? 'Aucune relance envoyée : aucun inscrit avec une communication acceptée et des frais non réglés.'
            : $sent.' relance(s) de paiement envoyée(s).';
    }

    /**
     * Inscrits à relancer : communication acceptée, aucun paiement réglé.
     *
     * @return \Illuminate\Support\Collection<int, Participant>
     */
    private function targets(Edition $edition): \Illuminate\Support\Collection
    {
        return Participant::where('edition_id', $edition->id)
            ->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted'))
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
            ->with('user')
            ->get();
    }
}
