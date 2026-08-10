<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Reçu de paiement accessible au participant propriétaire (guard « account »).
 */
class ReceiptController extends Controller
{
    public function __invoke(Payment $payment): View
    {
        $accountId = Auth::guard('account')->id();

        $payment->load(['participant', 'edition.colloque']);

        abort_unless(
            $payment->status === 'paid'
            && $payment->participant
            && (int) $payment->participant->account_id === (int) $accountId,
            404
        );

        return view('receipts.payment', ['payment' => $payment]);
    }
}
