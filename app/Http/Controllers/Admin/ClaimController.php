<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AnalyzeClaim;
use App\Actions\NotifyClaimRecipients;
use App\Http\Controllers\Controller;
use App\Mail\ClaimUpdateMail;
use App\Models\Claim;
use App\Models\Event;
use App\Models\User;
use App\Support\StaffRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Réclamations & incidents (back-office). Traitement par l'admin, les
 * gestionnaires ou toute personne à qui la capacité `claims` a été déléguée sur
 * un événement. À la demande, l'IA propose une analyse de traitement.
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
            ->with(['event', 'user', 'assignee'])
            ->when(! $user->isColloqueAdmin(), fn ($q) => $q->where(function ($w) use ($user) {
                $w->whereNull('event_id')->orWhereIn('event_id', $user->accessibleEditionIds('claims'));
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
        $claim->load(['event.series', 'user', 'assignee']);

        return view('admin.claims.show', [
            'claim' => $claim,
            'statuses' => Claim::statuses(),
            'assignees' => $this->assigneePool($claim),
            // Seul l'administrateur transfère une réclamation vers un événement.
            'events' => $request->user()->isColloqueAdmin()
                ? Event::query()->with('series')->orderByDesc('year')->orderBy('title')->get()
                : collect(),
        ]);
    }

    /**
     * Rattacher une réclamation à un événement (réservé à l'administrateur).
     * Les gestionnaires de l'événement d'arrivée sont alors notifiés.
     */
    public function transfer(Request $request, Claim $claim, NotifyClaimRecipients $notifier): RedirectResponse
    {
        abort_unless($request->user()->isColloqueAdmin(), 403);

        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
        ]);

        if ((int) $claim->event_id === (int) $data['event_id']) {
            return back()->with('ukc_status', 'La réclamation est déjà rattachée à cet événement.');
        }

        $claim->update(['event_id' => (int) $data['event_id']]);
        $notifier->handle($claim, transferred: true);

        return back()->with('ukc_status', 'Réclamation transférée. Les gestionnaires de l’événement ont été notifiés.');
    }

    public function updateStatus(Request $request, Claim $claim): RedirectResponse
    {
        $this->authorizeClaim($request, $claim);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(Claim::statuses()))],
            'resolution' => ['nullable', 'string', 'max:8000'],
        ]);

        $wasResolved = $claim->status === 'resolved';
        $newResponse = trim((string) ($data['resolution'] ?? ''));
        $responseChanged = $newResponse !== '' && $newResponse !== trim((string) $claim->resolution);
        $becameResolved = ! $wasResolved && $data['status'] === 'resolved';

        $claim->status = $data['status'];
        if ($newResponse !== '') {
            $claim->resolution = $newResponse;
        }
        $claim->resolved_at = in_array($data['status'], ['resolved', 'closed'], true) ? ($claim->resolved_at ?? now()) : null;

        // Conserver l'envoi à effectuer si le serveur mail échoue : un second
        // clic sur Enregistrer pourra alors relancer l'envoi sans créer de doublon.
        $meta = (array) $claim->meta;
        if ($becameResolved || $responseChanged) {
            $meta['claim_mail_pending'] = [
                'resolved' => $becameResolved,
                'response' => $claim->resolution,
            ];
            $claim->meta = $meta;
        } elseif (($meta['claim_mail_pending']['resolved'] ?? false) && $data['status'] !== 'resolved') {
            // Ne pas annoncer une résolution qui vient d'être annulée.
            unset($meta['claim_mail_pending']);
            $claim->meta = $meta;
        }
        $claim->save();

        $pending = $meta['claim_mail_pending'] ?? null;
        if (! is_array($pending)) {
            return back()->with('ukc_status', 'Statut mis à jour.');
        }

        $email = collect([$claim->email, $claim->user?->email])
            ->map(fn ($address) => trim((string) $address))
            ->first(fn ($address) => filter_var($address, FILTER_VALIDATE_EMAIL) !== false);
        if ($email === null) {
            return back()
                ->with('ukc_status', 'Réclamation mise à jour.')
                ->with('ukc_error', 'Notification non envoyée : aucune adresse e-mail valide pour le déclarant.');
        }

        try {
            Mail::to($email)->send(new ClaimUpdateMail(
                $claim->loadMissing('event'),
                (bool) ($pending['resolved'] ?? false),
                $pending['response'] ?? null,
            ));
        } catch (Throwable $exception) {
            Log::warning('Notification de réclamation non envoyée.', [
                'claim_id' => $claim->id,
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->with('ukc_status', 'Réclamation mise à jour.')
                ->with('ukc_error', 'L’e-mail au déclarant n’a pas pu être envoyé. Enregistrez à nouveau pour réessayer.');
        }

        unset($meta['claim_mail_pending']);
        $claim->update(['meta' => $meta]);

        return back()->with('ukc_status', 'Réclamation mise à jour et e-mail envoyé au déclarant.');
    }

    public function assign(Request $request, Claim $claim): RedirectResponse
    {
        $this->authorizeClaim($request, $claim);

        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer', Rule::in($this->assigneePool($claim)->pluck('id')->all())],
        ]);
        $claim->update(['assigned_to' => $data['assigned_to'] ?: null]);

        return back()->with('ukc_status', 'Affectation mise à jour.');
    }

    /** Analyse de traitement par l'IA (proposition, jamais exécutée automatiquement). */
    public function analyze(Request $request, Claim $claim, AnalyzeClaim $action): RedirectResponse
    {
        $this->authorizeClaim($request, $claim);

        try {
            $analysis = $action->handle($claim);
        } catch (Throwable $e) {
            return back()->with('ukc_error', $e->getMessage());
        }

        $meta = (array) $claim->meta;
        $history = is_array($meta['analyses'] ?? null) ? $meta['analyses'] : [];
        array_unshift($history, $analysis + [
            'by' => $request->user()->name,
            'at' => now()->format('d/m/Y - H:i'),
        ]);
        $meta['analyses'] = array_slice($history, 0, 10);
        $claim->update(['meta' => $meta]);

        return back()->with('ukc_status', 'Analyse IA générée.');
    }

    /**
     * Une réclamation générale peut être confiée aux administrateurs globaux.
     * Pour un événement, seuls les membres explicitement affectés à son équipe
     * sont proposés ; les administrateurs affectés y restent éligibles.
     *
     * @return Collection<int, User>
     */
    private function assigneePool(Claim $claim): Collection
    {
        // ⚠️ `administrateurs()` et non la seule colonne `access_level` : un
        // compte qui tient ses droits du rôle hérité ou de la permission
        // `manage_colloques` est administrateur pour le modèle, et serait
        // pourtant absent de ce vivier.
        $adminIds = User::query()->administrateurs()->pluck('id');

        if ($claim->event_id === null) {
            return User::whereIn('id', $adminIds)->orderBy('name')->get(['id', 'name', 'email']);
        }

        $rolesWithClaims = collect(StaffRoles::ROLES)
            ->filter(fn ($r) => in_array('claims', $r['permissions'], true))->keys();
        $assigned = DB::table('event_user')
            ->where('event_id', $claim->event_id)
            ->where(fn ($q) => $q->whereIn('role', $rolesWithClaims)
                ->orWhereIn('user_id', $adminIds))
            ->pluck('user_id');

        return User::whereIn('id', $assigned->unique())->orderBy('name')->get(['id', 'name', 'email']);
    }

    /** L'utilisateur peut-il traiter cette réclamation ? */
    private function authorizeClaim(Request $request, Claim $claim): void
    {
        $user = $request->user();
        $ok = $user->isColloqueAdmin()
            || $claim->event_id === null && $user->canAnyEdition('claims')
            || ($claim->event_id !== null && $user->canOnEdition('claims', $claim->event_id));

        abort_unless($ok, 403);
    }
}
