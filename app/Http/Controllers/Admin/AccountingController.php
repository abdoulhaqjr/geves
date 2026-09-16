<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Payment;
use App\Support\Fees;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Poste de travail de la comptabilité (back-office, Blade lecture seule) : vue
 * financière consolidée des encaissements — recettes, en attente, remboursés,
 * reste à encaisser, répartition par moyen de paiement (dont l'agrégateur
 * FedaPay), derniers encaissements, accès rapides (liste, export). Scopé aux
 * éditions accessibles avec la capacité `payments`. Sans modification des tarifs.
 */
class AccountingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $editions = $user->accessibleEditions('payments');
        $editionIds = $editions->pluck('id');

        $base = fn () => Payment::query()->whereIn('edition_id', $editionIds);

        $paidSum = $editionIds->isEmpty() ? 0.0 : (float) (clone $base())->where('status', 'paid')->sum('amount');
        $pendingSum = $editionIds->isEmpty() ? 0.0 : (float) (clone $base())->where('status', 'pending')->sum('amount');
        $refundedSum = $editionIds->isEmpty() ? 0.0 : (float) (clone $base())->where('status', 'refunded')->sum('amount');

        // Reste à encaisser : communications acceptées sans paiement réglé.
        $unpaid = $editionIds->isEmpty() ? collect()
            : Participant::whereIn('edition_id', $editionIds)
                ->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted'))
                ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
                ->with('edition')->get();
        $outstanding = $unpaid->sum(fn (Participant $p) => (float) (Fees::resolveAmount($p->edition, $p->title) ?? Fees::highestAmount($p->edition) ?? 0));

        // Répartition des recettes par moyen de paiement.
        $byMethodRaw = $editionIds->isEmpty() ? collect()
            : (clone $base())->where('status', 'paid')
                ->selectRaw('method, COUNT(*) as n, SUM(amount) as total')
                ->groupBy('method')->orderByDesc('total')->get();
        $labels = Payment::methodLabels();
        $byMethod = $byMethodRaw->map(fn ($r) => [
            'label' => $labels[$r->method] ?? ($r->method ?: 'Autre'),
            'count' => (int) $r->n,
            'total' => (float) $r->total,
            'pct' => $paidSum > 0 ? round((float) $r->total / $paidSum * 100) : 0,
        ]);

        $recent = $editionIds->isEmpty() ? collect()
            : (clone $base())->where('status', 'paid')
                ->with(['participant', 'edition'])->latest('paid_at')->latest('id')->limit(10)->get();

        return view('admin.accounting', [
            'editions' => $editions,
            'currency' => (string) config('payments.currency', 'XOF'),
            'stats' => [
                'revenue' => $paidSum,
                'pending' => $pendingSum,
                'pending_count' => $editionIds->isEmpty() ? 0 : (clone $base())->where('status', 'pending')->count(),
                'refunded' => $refundedSum,
                'outstanding' => $outstanding,
                'outstanding_count' => $unpaid->count(),
                'paid_count' => $editionIds->isEmpty() ? 0 : (clone $base())->where('status', 'paid')->count(),
            ],
            'byMethod' => $byMethod,
            'recent' => $recent,
        ]);
    }
}
