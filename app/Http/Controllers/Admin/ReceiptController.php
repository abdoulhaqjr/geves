<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reçu de paiement : vue imprimable (HTML) ou vrai PDF serveur (?pdf=1).
 */
class ReceiptController extends Controller
{
    /**
     * Aperçu du reçu avec des valeurs d'exemple (sans paiement réel) : montre le
     * rendu final du reçu pour l'événement courante. Utile avant tout encaissement.
     */
    public function sample(Request $request): View
    {
        $event = Event::with('series')->find($request->query('event_id'))
            ?? Event::with('series')->latest('id')->first();

        $participant = new Participant;
        $participant->forceFill(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa.kodjo@example.tg']);

        $payment = new Payment;
        $payment->forceFill([
            'amount' => 25000, 'currency' => 'FCFA', 'method' => 'cash',
            'status' => 'paid', 'receipt_no' => 'EXEMPLE-0001', 'paid_at' => now(),
        ]);
        $payment->setRelation('participant', $participant);
        $payment->setRelation('event', $event);

        // Aperçu verrouillé (même présentation que l'attestation/le badge) : le reçu
        // réel rendu avec des valeurs d'exemple, dans un cadre sombre, « Fermer » ramène
        // aux paiements.
        $backUrl = route('admin.payments.index', $event ? ['event_id' => $event->id] : []);

        return view('receipts.payment', [
            'payment' => $payment,
            'sample' => true,
            'backUrl' => $backUrl,
        ]);
    }

    public function __invoke(Payment $payment, Request $request): View|Response
    {
        $payment->load(['participant', 'event.series']);

        abort_unless($payment->status === 'paid', 404);

        if ($request->boolean('pdf')) {
            return Pdf::loadView('pdf.receipt', ['payment' => $payment])
                ->download('recu-'.$payment->receipt_no.'.pdf');
        }

        return view('receipts.payment', ['payment' => $payment]);
    }
}
