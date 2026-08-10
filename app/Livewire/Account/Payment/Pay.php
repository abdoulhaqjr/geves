<?php

namespace App\Livewire\Account\Payment;

use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Payments\GatewayRegistry;
use App\Support\Fees;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Paiement des frais par le participant. Disponible dès qu'au moins une
 * communication est acceptée ; montant déduit du grade. Garde anti-double-paiement.
 */
#[Title('Payer mes frais')]
class Pay extends Component
{
    public Edition $edition;
    public ?Participant $participant = null;
    public ?float $amount = null;

    public ?string $instructions = null;

    public function mount(Edition $edition): void
    {
        $account = Auth::guard('account')->user();

        $this->edition = $edition;
        $this->participant = Participant::where('edition_id', $edition->id)
            ->where('account_id', $account->id)
            ->first();

        if ($this->participant) {
            $this->amount = Fees::resolveAmount($edition, $this->participant->title)
                ?? Fees::highestAmount($edition);
        }
    }

    public function isEligible(): bool
    {
        return $this->participant
            && $this->participant->hasAcceptedSubmission();
    }

    public function hasPaid(): bool
    {
        return $this->participant && $this->participant->hasPaid();
    }

    public function paidReceipt(): ?Payment
    {
        return $this->participant?->payments()->where('status', 'paid')->latest('id')->first();
    }

    public function pay(string $gatewayKey, GatewayRegistry $registry)
    {
        if (! $this->isEligible() || $this->hasPaid() || ! $this->amount) {
            $this->addError('pay', 'Paiement indisponible.');

            return null;
        }

        $gateway = $registry->get($gatewayKey);
        if (! $gateway || ! $gateway->isAvailable()) {
            $this->addError('pay', 'Ce moyen de paiement n’est pas disponible.');

            return null;
        }

        // Réutilise un paiement en attente existant plutôt que d'en créer un doublon.
        $payment = Payment::firstOrCreate(
            [
                'edition_id' => $this->edition->id,
                'participant_id' => $this->participant->id,
                'status' => 'pending',
            ],
            [
                'amount' => $this->amount,
                'currency' => config('payments.currency', 'XOF'),
                'method' => $gateway->key(),
                'gateway' => $gateway->key(),
            ]
        );

        $result = $gateway->initiate($payment);

        if (($result['type'] ?? null) === 'redirect' && ! empty($result['url'])) {
            return $this->redirect($result['url']);
        }

        $this->instructions = $result['message'] ?? 'Votre demande de paiement est enregistrée.';

        return null;
    }

    public function render()
    {
        return view('livewire.account.payment.pay', [
            'gateways' => app(GatewayRegistry::class)->available(),
        ])->layout('components.layouts.participant', ['title' => 'Payer mes frais']);
    }
}
