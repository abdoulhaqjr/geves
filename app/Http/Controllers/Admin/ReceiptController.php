<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Contracts\View\View;

/**
 * Reçu de paiement imprimable (Imprimer / Enregistrer en PDF depuis le
 * navigateur).
 */
class ReceiptController extends Controller
{
    public function __invoke(Payment $payment): View
    {
        $payment->load(['participant', 'edition.colloque']);

        abort_unless($payment->status === 'paid', 404);

        return view('receipts.payment', ['payment' => $payment]);
    }
}
