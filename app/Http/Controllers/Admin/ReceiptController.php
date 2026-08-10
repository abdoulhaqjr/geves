<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
    public function __invoke(Payment $payment, Request $request): View|Response
    {
        $payment->load(['participant', 'edition.colloque']);

        abort_unless($payment->status === 'paid', 404);

        if ($request->boolean('pdf')) {
            return Pdf::loadView('pdf.receipt', ['payment' => $payment])
                ->download('recu-'.$payment->receipt_no.'.pdf');
        }

        return view('receipts.payment', ['payment' => $payment]);
    }
}
