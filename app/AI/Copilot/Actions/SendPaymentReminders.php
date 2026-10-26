<?php

namespace App\AI\Copilot\Actions;

use App\AI\Copilot\CopilotAction;
use App\Mail\PaymentReminderMail;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Support\Fees;
use App\Support\Notifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Relance de paiement en masse : envoie un rappel aux inscrits de l'événement dont
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
        return "Envoie un e-mail de relance à tous les inscrits de l'événement ayant une communication "
            ."acceptée mais des frais d'inscription non réglés. À proposer si l'utilisateur veut relancer "
            .'les paiements en attente / retards de paiement.';
    }

    public function summary(array $params, Event $event): string
    {
        return 'Envoyer une relance de paiement à '.$this->targets($event)->count()
            .' inscrit(s) en attente de règlement pour « '.($event->title ?: $event->year).' ».';
    }

    public function context(Event $event): ?string
    {
        return $this->targets($event)->count().' inscrit(s) actuellement concerné(s) par une relance '
            .'(communication acceptée ET frais non réglés). Attention : ce nombre peut différer des '
            .'« paiements en attente » du tableau de bord, qui ne comptent que les paiements déjà initiés '
            .'(un inscrit accepté qui n’a jamais lancé de paiement est concerné mais n’apparaît pas en « attente »).';
    }

    public function execute(array $params, Event $event, User $user): string
    {
        $targets = $this->targets($event);
        $sent = 0;

        foreach ($targets as $participant) {
            if (blank($participant->email)) {
                continue;
            }

            $amount = Fees::forParticipant($participant) ?? Fees::highestAmount($event);
            $payUrl = route('account.payment', $event);

            Mail::to($participant->email)->send(new PaymentReminderMail($participant, $event, $payUrl, $amount));

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
     * @return Collection<int, Participant>
     */
    private function targets(Event $event): Collection
    {
        return Participant::where('event_id', $event->id)
            ->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted'))
            ->whereDoesntHave('participationPayments', fn ($q) => $q->where('status', 'paid'))
            ->with('user')
            ->get();
    }
}
