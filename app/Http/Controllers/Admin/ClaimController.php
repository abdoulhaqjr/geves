<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AnalyzeClaim;
use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\User;
use App\Support\StaffRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Réclamations & incidents (back-office). Traitement par l'admin, les
 * gestionnaires ou toute personne à qui la capacité `claims` a été déléguée sur
 * une édition. À la demande, l'IA propose une analyse de traitement.
 */
class ClaimController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->canAnyEdition('claims'), 403);

        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');
        $search = trim((string) $request->query('search', ''));

        $query = Claim::query()
            ->with(['edition', 'user', 'assignee'])
            ->when(! $user->isColloqueSuperAdmin(), fn ($q) => $q->where(function ($w) use ($user) {
                $w->whereNull('edition_id')->orWhereIn('edition_id', $user->accessibleEditionIds('claims'));
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")));

        $counts = (clone $query)->reorder()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.claims.index', [
            'claims' => $query->latest('id')->paginate(20)->withQueryString(),
            'statuses' => Claim::statuses(),
            'types' => Claim::types(),
            'status' => $status,
            'type' => $type,
            'search' => $search,
            'stats' => [
                'open' => (int) ($counts['open'] ?? 0),
                'in_progress' => (int) ($counts['in_progress'] ?? 0),
                'resolved' => (int) ($counts['resolved'] ?? 0),
                'total' => (int) $counts->sum(),
            ],
        ]);
    }

    public function show(Request $request, Claim $claim): View
    {
        $this->authorizeClaim($request, $claim);
        $claim->load(['edition.colloque', 'user', 'assignee']);

        return view('admin.claims.show', [
            'claim' => $claim,
            'statuses' => Claim::statuses(),
            'assignees' => $this->assigneePool($claim),
        ]);
    }

    public function updateStatus(Request $request, Claim $claim): RedirectResponse
    {
        $this->authorizeClaim($request, $claim);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(Claim::statuses()))],
            'resolution' => ['nullable', 'string', 'max:8000'],
        ]);

        $claim->status = $data['status'];
        if (filled($data['resolution'] ?? null)) {
            $claim->resolution = $data['resolution'];
        }
        $claim->resolved_at = in_array($data['status'], ['resolved', 'closed'], true) ? ($claim->resolved_at ?? now()) : null;
        $claim->save();

        return back()->with('ukc_status', 'Statut mis à jour.');
    }

    public function assign(Request $request, Claim $claim): RedirectResponse
    {
        $this->authorizeClaim($request, $claim);

        $data = $request->validate(['assigned_to' => ['nullable', 'exists:users,id']]);
        $claim->update(['assigned_to' => $data['assigned_to'] ?: null]);

        return back()->with('ukc_status', 'Affectation mise à jour.');
    }

    /** Analyse de traitement par l'IA (proposition, jamais exécutée automatiquement). */
    public function analyze(Request $request, Claim $claim, AnalyzeClaim $action): RedirectResponse
    {
        $this->authorizeClaim($request, $claim);

        try {
            $analysis = $action->handle($claim);
        } catch (\Throwable $e) {
            return back()->with('ukc_error', $e->getMessage());
        }

        $meta = (array) $claim->meta;
        $history = is_array($meta['analyses'] ?? null) ? $meta['analyses'] : [];
        array_unshift($history, $analysis + [
            'by' => $request->user()->name,
            'at' => now()->format('d/m/Y · H:i'),
        ]);
        $meta['analyses'] = array_slice($history, 0, 10);
        $claim->update(['meta' => $meta]);

        return back()->with('ukc_status', 'Analyse IA générée.');
    }

    /**
     * Vivier d'affectation : super-admins + membres du staff affectés à l'édition
     * de la réclamation avec la capacité `claims`.
     *
     * @return Collection<int, User>
     */
    private function assigneePool(Claim $claim): Collection
    {
        $ids = User::query()->where('access_level', User::ACCESS_SUPER_ADMIN)->pluck('id');

        if ($claim->edition_id !== null) {
            $rolesWithClaims = collect(StaffRoles::ROLES)
                ->filter(fn ($r) => in_array('claims', $r['permissions'], true))->keys();
            $assigned = DB::table('edition_user')
                ->where('edition_id', $claim->edition_id)
                ->whereIn('role', $rolesWithClaims)
                ->pluck('user_id');
            $ids = $ids->merge($assigned);
        }

        return User::whereIn('id', $ids->unique())->orderBy('name')->get(['id', 'name', 'email']);
    }

    /** L'utilisateur peut-il traiter cette réclamation ? */
    private function authorizeClaim(Request $request, Claim $claim): void
    {
        $user = $request->user();
        $ok = $user->isColloqueSuperAdmin()
            || $claim->edition_id === null && $user->canAnyEdition('claims')
            || ($claim->edition_id !== null && $user->canOnEdition('claims', $claim->edition_id));

        abort_unless($ok, 403);
    }
}
