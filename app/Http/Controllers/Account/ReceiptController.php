<?php

namespace App\Http\Controllers\Account;

use App\Documents\BrandedPdf;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reçu de paiement accessible au participant propriétaire (guard « account ») :
 * vue imprimable (HTML) ou vrai PDF serveur (?pdf=1).
 */
class ReceiptController extends Controller
{
    public function __invoke(Payment $payment, Request $request): View|Response
    {
        $accountId = Auth::guard('account')->id();

        $payment->load(['participant', 'event.series']);

        abort_unless(
            $payment->status === 'paid'
            && $payment->participant
            && (int) $payment->participant->user_id === (int) $accountId,
            404
        );

        if ($request->boolean('pdf')) {
            return BrandedPdf::stamp(Pdf::loadView('pdf.receipt', ['payment' => $payment]), 'Reçu de paiement · '.$payment->receipt_no)
                ->download('recu-'.$payment->receipt_no.'.pdf');
        }

        return view('receipts.payment', ['payment' => $payment]);
    }
}
