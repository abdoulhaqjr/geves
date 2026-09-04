<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Payments\GatewayRegistry;
use App\Support\Fees;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Paiement des frais par le participant (contrôleur + Blade, sans Livewire).
 * Disponible dès qu'au moins une communication est acceptée ; montant déduit du
 * grade. Garde anti-double-paiement (réutilise un paiement en attente existant).
 */
class PaymentController extends Controller
{
    public function show(Edition $edition): View
    {
        $account = Auth::guard('account')->user();
        $participant = Participant::where('edition_id', $edition->id)
            ->where('account_id', $account->id)
            ->first();

        $applicable = $participant ? Fees::resolveAmount($edition, $participant->title) : null;
        $amount = $applicable ?? ($participant ? Fees::highestAmount($edition) : null);

        // Libellé du tarif applicable (rapproché du grade du participant).
        $applicableLabel = null;
        foreach (Fees::grid($edition) as $tier) {
            if ((float) ($tier['amount'] ?? 0) === (float) $amount) {
                $applicableLabel = $tier['label'] ?? null;
                break;
            }
        }

        return view('account.payment.pay', [
            'edition' => $edition,
            'participant' => $participant,
            'amount' => $amount,
            'tiers' => Fees::grid($edition),
            'applicableLabel' => $applicableLabel,
            'eligible' => $participant && $participant->hasAcceptedSubmission(),
            'paid' => $participant && $participant->hasPaid(),
            'paidReceipt' => $participant?->payments()->where('status', 'paid')->latest('id')->first(),
            'gateways' => app(GatewayRegistry::class)->available(),
        ]);
    }

    public function pay(Request $request, Edition $edition, GatewayRegistry $registry): RedirectResponse
    {
        $account = Auth::guard('account')->user();
        $participant = Participant::where('edition_id', $edition->id)
            ->where('account_id', $account->id)
            ->first();

        $amount = $participant
            ? (Fees::resolveAmount($edition, $participant->title) ?? Fees::highestAmount($edition))
            : null;

        if (! $participant || ! $participant->hasAcceptedSubmission() || $participant->hasPaid() || ! $amount) {
            return back()->withErrors(['pay' => 'Paiement indisponible.']);
        }

        $gateway = $registry->get((string) $request->input('gateway'));
        if (! $gateway || ! $gateway->isAvailable()) {
            return back()->withErrors(['pay' => 'Ce moyen de paiement n’est pas disponible.']);
        }

        // Réutilise un paiement en attente existant plutôt que d'en créer un doublon.
        $payment = Payment::firstOrCreate(
            [
                'edition_id' => $edition->id,
                'participant_id' => $participant->id,
                'status' => 'pending',
            ],
            [
                'amount' => $amount,
                'currency' => config('payments.currency', 'XOF'),
                'method' => $gateway->key(),
                'gateway' => $gateway->key(),
            ]
        );

        try {
            $result = $gateway->initiate($payment);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['pay' => 'Le service de paiement est momentanément indisponible. Réessayez plus tard.']);
        }

        if (($result['type'] ?? null) === 'redirect' && ! empty($result['url'])) {
            return redirect()->away($result['url']);
        }

        return back()->with('pay_instructions', $result['message'] ?? 'Votre demande de paiement est enregistrée.');
    }
}
