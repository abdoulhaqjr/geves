<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ConfirmPayment;
use App\Actions\ReconcilePayments;
use App\Http\Controllers\Controller;
use App\Models\FormationEnrollment;
use App\Models\Participant;
use App\Models\Payment;
use App\Support\Fees;
use App\Support\ListSort;
use App\Support\Trend;
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
        $method = (string) $request->query('method', '');
        $flow = (string) $request->query('flow', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');
        $scoped = $this->scopedEditionIds($request, 'payments');
        $editions = $this->accessibleEditions($request, 'payments');

        // Par défaut (aucun filtre explicite), on se cale sur le colloque courant
        // (ouvert le plus récent, sinon le plus récent) - pas « toutes les éditions ».
        if ($request->has('edition_id')) {
            $editionId = (string) $request->query('edition_id', '');
        } else {
            // La page est gardée par edition.cap:payments → au moins une édition accessible.
            $default = $editions->firstWhere('status', 'open') ?? $editions->first();
            $editionId = $default !== null ? (string) $default->id : '';
        }

        $base = Payment::query()
            ->when($scoped !== null, fn ($q) => $q->whereIn('edition_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('edition_id', $editionId));

        $sort = ListSort::resolve($request, ['reference', 'receipt_no', 'amount', 'created_at', 'status', 'method'], 'created_at', 'desc');
        $payments = $this->applyFilters(clone $base, $status, $method, $search, $from, $to, $flow)
            ->with(['participant', 'edition'])
            ->orderBy($sort['sort'], $sort['dir'])
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $paidCount = (clone $base)->where('status', 'paid')->count();
        $pendingCount = (clone $base)->where('status', 'pending')->count();
        $totalCount = (clone $base)->count();

        // Tendance des encaissements : 7 derniers jours vs les 7 précédents.
        $trendPaid = Trend::of(
            (int) (clone $base)->where('status', 'paid')->whereBetween('created_at', [now()->subDays(7), now()])->count(),
            (int) (clone $base)->where('status', 'paid')->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count(),
        );

        return view('admin.payments.index', [
            'trendPaid' => $trendPaid,
            'sort' => $sort['sort'],
            'dir' => $sort['dir'],
            'payments' => $payments,
            'editions' => $editions,
            'statuses' => Payment::statuses(),
            'methods' => Payment::methodLabels(),
            'methodGroups' => Payment::methodGroups(),
            'flows' => Payment::FLOWS,
            'flow' => $flow,
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
    private function applyFilters(Builder $q, string $status, string $method, string $search, string $from, string $to, string $flow = ''): Builder
    {
        return $q
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($method !== '', fn ($q) => $q->where('method', $method))
            // Nature affichée : « formation » d'un côté, « participation » de
            // l'autre - qui englobe le co-auteur (il règle pour participer) ET les
            // paiements sans meta.flow (participation par défaut).
            ->when($flow !== '', function ($q) use ($flow) {
                $flow === 'formation'
                    ? $q->where('meta', 'like', '%"flow":"formation"%')
                    // Groupé : sinon le OU s'échapperait et annulerait les autres filtres.
                    : $q->where(fn ($p) => $p->where('meta', 'not like', '%"flow":"formation"%')
                        ->orWhereNull('meta'));
            })
            ->when($from !== '', fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($search !== '', function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where(fn ($p) => $p->where('reference', 'like', $term)
                    ->orWhere('receipt_no', 'like', $term)
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
            (string) $request->query('flow', ''),
        )->with(['participant', 'edition'])->latest('id')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Référence', 'Reçu', 'Participant', 'E-mail', 'Nature', 'Objet', 'Catégorie', 'Montant', 'Devise', 'Moyen', 'Statut', 'Date', 'Édition']);
            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->reference,
                    $p->receipt_no,
                    $p->participant?->full_name,
                    $p->participant?->email,
                    $p->flowLabel(),
                    $p->flowDetail(),
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

    /**
     * Supprime un paiement **en attente ou échoué** (erreur de saisie, tentative
     * abandonnée, doublon). Un paiement **réglé n'est jamais supprimable** : il
     * porte un reçu et ouvre des droits, il se « rembourse » (refund), ce qui
     * laisse une trace.
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        if (! in_array($payment->status, ['pending', 'failed'], true)) {
            return back()->with('ukc_error', 'Seul un paiement en attente ou échoué peut être supprimé ; un paiement réglé se rembourse.');
        }

        // Une inscription à une formation pointe sur ce paiement : on la détache
        // pour ne pas laisser une référence morte.
        FormationEnrollment::where('payment_id', $payment->id)->update(['payment_id' => null]);

        $reference = (string) $payment->reference;
        $payment->delete();

        return back()->with('ukc_status', 'Paiement '.$reference.' supprimé.');
    }

    public function markPaid(Payment $payment, ConfirmPayment $confirm): RedirectResponse
    {
        try {
            $confirm->handle($payment);

            return back()->with('ukc_status', 'Paiement marqué réglé - reçu envoyé au participant.');
        } catch (\DomainException $e) {
            return back()->with('ukc_error', $e->getMessage());
        }
    }

    /**
     * Revérifie un paiement en ligne auprès de la passerelle (cas « le participant
     * dit avoir payé ») : interroge le serveur du marchand avec les identifiants de
     * l'édition et confirme si le paiement est réellement abouti.
     */
    public function recheck(Payment $payment, ReconcilePayments $reconciler): RedirectResponse
    {
        $report = $reconciler->handle(paymentId: $payment->id);

        if ($report['errors'] > 0) {
            return back()->with('ukc_error', 'Passerelle injoignable : impossible de vérifier ce paiement pour le moment.');
        }
        if ($report['checked'] === 0) {
            return back()->with('ukc_error', 'Ce paiement ne peut pas être vérifié automatiquement (pas de référence passerelle).');
        }

        return match ($payment->fresh()?->status) {
            'paid' => back()->with('ukc_status', 'Paiement confirmé par la passerelle - reçu envoyé au participant.'),
            'failed' => back()->with('ukc_error', 'La passerelle indique un paiement échoué ou annulé.'),
            default => back()->with('ukc_status', 'La passerelle indique un paiement toujours en attente.'),
        };
    }

    public function refund(Payment $payment): RedirectResponse
    {
        $payment->update(['status' => 'refunded']);

        // Rembourser doit RETIRER les droits acquis, sinon le participant garde une
        // « inscription réglée » et l'accès à la visio après remboursement.
        // Miroir exact de ce que ConfirmPayment applique à la confirmation.
        if ($payment->flow() === 'formation' && ($enrollmentId = $payment->meta['enrollment_id'] ?? null)) {
            FormationEnrollment::whereKey($enrollmentId)
                ->where('status', 'paid')
                ->update(['status' => 'registered']);
        }

        return back()->with('ukc_status', 'Paiement remboursé.');
    }
}
