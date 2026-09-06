<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ConfirmPayment;
use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Payment;
use App\Support\Fees;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Encaissements du back-office (Blade, fidèle au mockup admin-payments.html) :
 * liste par édition, totaux, filtres (query string) et actions (marquer réglé,
 * rembourser) en POST. L'enregistrement manuel est un formulaire Blade dont le
 * filtrage édition→participant et le pré-remplissage du montant sont pilotés
 * côté client (Alpine).
 */
class PaymentController extends Controller
{
    use Concerns\ScopesEditionAccess;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $editionId = (string) $request->query('edition_id', '');
        $method = (string) $request->query('method', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');
        $scoped = $this->scopedEditionIds($request, 'payments');

        $base = Payment::query()
            ->when($scoped !== null, fn ($q) => $q->whereIn('edition_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('edition_id', $editionId));

        $payments = $this->applyFilters(clone $base, $status, $method, $search, $from, $to)
            ->with(['participant', 'edition'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $paidCount = (clone $base)->where('status', 'paid')->count();
        $pendingCount = (clone $base)->where('status', 'pending')->count();
        $totalCount = (clone $base)->count();

        return view('admin.payments.index', [
            'payments' => $payments,
            'editions' => $this->accessibleEditions($request, 'payments'),
            'statuses' => Payment::statuses(),
            'methods' => Payment::MANUAL_METHODS,
            'search' => $search,
            'status' => $status,
            'edition_id' => $editionId,
            'method' => $method,
            'from' => $from,
            'to' => $to,
            'currency' => (clone $base)->value('currency') ?: 'XOF',
            'stats' => [
                'paid' => (float) (clone $base)->where('status', 'paid')->sum('amount'),
                'paidCount' => $paidCount,
                'pending' => (float) (clone $base)->where('status', 'pending')->sum('amount'),
                'pendingCount' => $pendingCount,
                'failed' => (clone $base)->where('status', 'failed')->count(),
                'rate' => $totalCount > 0 ? (int) round($paidCount / $totalCount * 100) : 0,
            ],
        ]);
    }

    /** Applique les filtres communs (liste + export). */
    /**
     * @param  Builder<Payment>  $q
     * @return Builder<Payment>
     */
    private function applyFilters(Builder $q, string $status, string $method, string $search, string $from, string $to): Builder
    {
        return $q
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($method !== '', fn ($q) => $q->where('method', $method))
            ->when($from !== '', fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($search !== '', function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where(fn ($p) => $p->where('receipt_no', 'like', $term)
                    ->orWhereHas('participant', fn ($pa) => $pa
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term)));
            });
    }

    /** Export CSV des paiements filtrés (mêmes filtres que la liste). */
    public function export(Request $request): StreamedResponse
    {
        $editionId = (string) $request->query('edition_id', '');
        $scoped = $this->scopedEditionIds($request, 'payments');
        $base = Payment::query()
            ->when($scoped !== null, fn ($q) => $q->whereIn('edition_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('edition_id', $editionId));

        $rows = $this->applyFilters(
            $base,
            (string) $request->query('status', ''),
            (string) $request->query('method', ''),
            trim((string) $request->query('search', '')),
            (string) $request->query('from', ''),
            (string) $request->query('to', ''),
        )->with(['participant', 'edition'])->latest('id')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Reçu', 'Participant', 'E-mail', 'Catégorie', 'Montant', 'Devise', 'Moyen', 'Statut', 'Date', 'Édition']);
            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->receipt_no,
                    $p->participant?->full_name,
                    $p->participant?->email,
                    $p->participant?->title,
                    number_format((float) $p->amount, 0, '.', ''),
                    $p->currency,
                    $p->methodLabel(),
                    $p->statusLabel(),
                    $p->created_at?->format('d/m/Y H:i'),
                    $p->edition?->title ?: $p->edition?->year,
                ]);
            }
            fclose($out);
        }, 'paiements-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Formulaire d'enregistrement manuel d'un encaissement. */
    public function create(Request $request): View
    {
        $editions = $this->accessibleEditions($request, 'payments');
        $scoped = $this->scopedEditionIds($request, 'payments');
        $defaultEdition = (string) ($editions->firstWhere('status', 'open')?->id ?? $editions->first()?->id ?? '');

        $participants = Participant::with('edition')
            ->when($scoped !== null, fn ($q) => $q->whereIn('edition_id', $scoped))
            ->orderBy('last_name')->get()->map(fn ($p) => [
                'id' => (string) $p->id,
                'name' => $p->full_name,
                'email' => $p->email,
                'edition_id' => (string) $p->edition_id,
                'amount' => $p->edition ? Fees::resolveAmount($p->edition, $p->title) : null,
            ])->values();

        return view('admin.payments.record', [
            'editions' => $editions,
            'participants' => $participants,
            'methods' => Payment::MANUAL_METHODS,
            'defaultEdition' => $defaultEdition,
        ]);
    }

    /** Enregistre l'encaissement (participant existant ou nouveau). */
    public function store(Request $request, ConfirmPayment $confirm): RedirectResponse
    {
        $data = $request->validate([
            'edition_id' => ['required', 'exists:editions,id'],
            'participant_id' => ['nullable', 'exists:participants,id'],
            'first_name' => ['nullable', 'string', 'max:150'],
            'last_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'in:'.implode(',', array_keys(Payment::MANUAL_METHODS))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        // Résolution du participant (existant ou nouveau).
        if ($request->filled('participant_id')) {
            $participant = Participant::findOrFail($data['participant_id']);
        } else {
            if (blank($data['last_name'] ?? null) || blank($data['email'] ?? null)) {
                return back()->withInput()->withErrors(['last_name' => 'Choisissez un participant existant ou renseignez nom + e-mail.']);
            }
            $participant = Participant::firstOrCreate(
                ['edition_id' => $data['edition_id'], 'email' => mb_strtolower((string) $data['email'])],
                ['first_name' => $data['first_name'] ?? '', 'last_name' => $data['last_name'], 'role' => 'attendee']
            );
        }

        // Garde anti-double-paiement.
        if ($participant->hasPaid()) {
            return back()->withInput()->withErrors(['amount' => 'Ce participant a déjà réglé ses frais.']);
        }

        $payment = Payment::create([
            'edition_id' => $participant->edition_id,
            'participant_id' => $participant->id,
            'amount' => $data['amount'],
            'currency' => 'XOF',
            'method' => $data['method'],
            'gateway' => 'manual',
            'status' => 'pending',
            'recorded_by' => Auth::id() ?? 0,
            'meta' => ['note' => ($data['note'] ?? null) ?: null, 'recorded_by_name' => Auth::user()?->name],
        ]);

        if ($request->boolean('markPaidNow')) {
            try {
                $confirm->handle($payment);
            } catch (\DomainException $e) {
                return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.payments.index')->with('ukc_status', 'Encaissement enregistré.');
    }

    public function markPaid(Payment $payment, ConfirmPayment $confirm): RedirectResponse
    {
        try {
            $confirm->handle($payment);

            return back()->with('ukc_status', 'Paiement marqué réglé — reçu envoyé au participant.');
        } catch (\DomainException $e) {
            return back()->with('ukc_error', $e->getMessage());
        }
    }

    public function refund(Payment $payment): RedirectResponse
    {
        $payment->update(['status' => 'refunded']);

        return back()->with('ukc_status', 'Paiement remboursé.');
    }
}
