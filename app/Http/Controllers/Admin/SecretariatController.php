<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentReminderMail;
use App\Models\EventSession;
use App\Models\Participant;
use App\Support\Fees;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Poste de travail du secrétariat (back-office, Blade lecture seule) : file des
 * rapports de séance à valider, séances en attente de rédaction, rapports
 * validés, relances de paiement, et accès rapides aux tâches administratives.
 * Scopé aux éditions accessibles à l'utilisateur (capacité `editions_read`).
 */
class SecretariatController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $editions = $user->accessibleEditions('editions_read');
        $editionIds = $editions->pluck('id');

        /** @var Collection<int, EventSession> $sessions */
        $sessions = $editionIds->isEmpty()
            ? collect()
            : EventSession::whereIn('edition_id', $editionIds)
                ->whereNotNull('rapporteur_email')->where('rapporteur_email', '!=', '')
                ->with(['edition.colloque', 'atelier', 'report'])
                ->orderBy('starts_at')->orderBy('id')->get();

        $toValidate = $sessions->filter(fn (EventSession $s) => $s->report && $s->report->isSubmitted());
        $awaiting = $sessions->filter(fn (EventSession $s) => $s->hasStarted()
            && (! $s->report || $s->report->status === 'draft'));
        $validated = $sessions->filter(fn (EventSession $s) => $s->report && $s->report->isValidated());

        // Relances de paiement : inscriptions avec communication acceptée et frais
        // non encore réglés (à relancer).
        $toRemind = $editionIds->isEmpty() ? collect()
            : Participant::whereIn('edition_id', $editionIds)
                ->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted'))
                ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
                ->with('edition')
                ->orderBy('edition_id')->orderBy('last_name')
                ->get();

        return view('admin.secretariat', [
            'editions' => $editions,
            'toValidate' => $toValidate->values(),
            'awaiting' => $awaiting->values(),
            'validated' => $validated->sortByDesc(fn (EventSession $s) => $s->report?->validated_at)->take(6)->values(),
            'toRemind' => $toRemind->map(fn (Participant $p) => [
                'participant' => $p,
                'amount' => Fees::resolveAmount($p->edition, $p->title) ?? Fees::highestAmount($p->edition),
                'remindedAt' => $p->meta['payment_reminded_at'] ?? null,
            ])->values(),
            'stats' => [
                'to_validate' => $toValidate->count(),
                'validated' => $validated->count(),
                'awaiting' => $awaiting->count(),
                'to_remind' => $toRemind->count(),
            ],
        ]);
    }

    /** Relance de paiement : e-mail + notification in-app au participant. */
    public function remindPayment(Request $request, Participant $participant): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canOnEdition('participants', $participant->edition_id), 403);

        $edition = $participant->edition;
        $amount = Fees::resolveAmount($edition, $participant->title) ?? Fees::highestAmount($edition);
        $payUrl = route('account.payment', $edition);

        if (filled($participant->email)) {
            Mail::to($participant->email)->send(new PaymentReminderMail($participant, $edition, $payUrl, $amount));
        }

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

        return back()->with('ukc_status', 'Relance de paiement envoyée à '.$participant->full_name.'.');
    }
}
