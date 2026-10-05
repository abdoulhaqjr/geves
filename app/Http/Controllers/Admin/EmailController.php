<?php

namespace App\Http\Controllers\Admin;

use App\Actions\QueueEmailCampaign;
use App\Actions\SendDueEmails;
use App\Http\Controllers\Controller;
use App\Models\Email;
use App\Models\Event;
use App\Support\EmailAudience;
use App\Support\EmailTemplates;
use App\Support\ListSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Campagnes d'e-mails ciblées (audiences) et rappels programmés, contrôleur +
 * Blade. Compose un message pour une audience d'un événement, l'envoie tout de
 * suite ou à une date planifiée, et affiche le journal des e-mails. L'événement
 * et le statut filtrent journal/KPIs via query string ; le nombre de
 * destinataires par audience est pré-calculé et affiché côté client (Alpine).
 */
class EmailController extends Controller
{
    use Concerns\ScopesEditionAccess;

    public function index(Request $request): View
    {
        $events = $this->accessibleEditions($request, 'emails');
        $editionId = (string) $request->query('event_id', '');
        if ($editionId === '') {
            $editionId = (string) ($events->firstWhere('status', 'open')?->id ?? $events->first()?->id ?? '');
        }
        $status = (string) $request->query('status', '');

        $event = $editionId !== '' ? Event::find($editionId) : null;

        // Nombre de destinataires par audience pour l'événement sélectionné.
        $recipientCounts = [];
        foreach (array_keys(EmailAudience::AUDIENCES) as $key) {
            $recipientCounts[$key] = $event ? EmailAudience::count($event, $key) : 0;
        }

        // Journal détaillé (par destinataire) - conservé pour le filtrage/log.
        $journal = Email::query()
            ->with('participant')
            ->when($editionId !== '', fn ($q) => $q->where('event_id', $editionId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Campagnes agrégées (fidèle au mockup « E-mails · Programmés ») : on
        // regroupe les e-mails par lot d'envoi (batch_id) ou par déclencheur auto.
        $campaigns = Email::query()
            ->when($editionId !== '', fn ($q) => $q->where('event_id', $editionId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (Email $e) => $e->batch_id ?: 'tpl:'.$e->template)
            ->map(function ($group) {
                $first = $group->first();
                $sent = $group->where('status', 'sent')->count();
                $queued = $group->whereIn('status', ['queued'])->count();
                $failed = $group->where('status', 'failed')->count();
                $cancelled = $group->where('status', 'cancelled')->count();

                $rollup = match (true) {
                    $queued > 0 => 'queued',
                    $failed > 0 && $sent > 0 => 'partial',
                    $failed > 0 => 'failed',
                    $cancelled === $group->count() => 'cancelled',
                    default => 'sent',
                };

                return [
                    'batch_id' => $first->batch_id,
                    'is_campaign' => (bool) $first->batch_id,
                    'template' => $first->template,
                    'label' => (string) ($first->subject ?: EmailTemplates::label((string) $first->template)),
                    'audience' => $first->audience ? EmailAudience::label($first->audience) : 'Auto (événement)',
                    'scheduled_at' => $first->scheduled_at,
                    'total' => $group->count(),
                    'sent' => $sent,
                    'failed' => $failed,
                    'status' => $rollup,
                    // Journal d'envoi (par destinataire, plafonné pour l'aperçu).
                    'log' => $group->take(60)->map(fn (Email $e) => [
                        'to' => (string) $e->recipient,
                        'status' => (string) $e->status,
                        'at' => optional($e->sent_at ?: $e->scheduled_at)?->format('d/m/Y H:i') ?? '-',
                    ])->values()->all(),
                ];
            });

        // Tri par en-tête de colonne (campagnes) - collection agrégée triée en mémoire.
        $sort = ListSort::resolve(
            $request,
            ['label', 'audience', 'scheduled_at', 'total', 'status'],
            'scheduled_at',
            'desc'
        );
        $campaigns = $campaigns
            ->sortBy(
                fn ($c) => $sort['sort'] === 'scheduled_at'
                    ? (optional($c['scheduled_at'])?->timestamp ?? 0)
                    : $c[$sort['sort']],
                SORT_REGULAR,
                $sort['dir'] === 'desc'
            )
            ->values();

        $counts = Email::query()
            ->when($editionId !== '', fn ($q) => $q->where('event_id', $editionId))
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        // Modèles d'e-mails (onglet « Modèles ») : intégrés puis personnalisés.
        $templates = collect(EmailTemplates::keys())
            ->map(function ($key) {
                $raw = EmailTemplates::raw($key);

                return [
                    'key' => $key,
                    'label' => EmailTemplates::label($key),
                    'trigger' => EmailTemplates::trigger($key),
                    'subject' => $raw['subject'],
                    'heading' => $raw['heading'],
                    'intro' => $raw['intro'],
                    'body' => EmailTemplates::body($key),
                    'active' => EmailTemplates::active($key),
                    'custom' => EmailTemplates::isCustom($key),
                ];
            })
            ->values();

        return view('admin.emails.index', [
            'events' => $events,
            'audiences' => EmailAudience::AUDIENCES,
            'event_id' => $editionId,
            'status' => $status,
            'recipientCounts' => $recipientCounts,
            'journal' => $journal,
            'campaigns' => $campaigns,
            'sort' => $sort['sort'],
            'dir' => $sort['dir'],
            'templates' => $templates,
            'queuedDue' => Email::where('status', 'queued')->whereNotNull('scheduled_at')->where('scheduled_at', '<=', now())->count(),
            'stats' => [
                'total' => (int) $counts->sum(),
                'queued' => (int) $counts->get('queued', 0),
                'sent' => (int) $counts->get('sent', 0),
                'failed' => (int) $counts->get('failed', 0),
            ],
        ]);
    }

    /** Met la campagne en file (immédiate si aucune date, sinon programmée). */
    public function send(Request $request, QueueEmailCampaign $action, SendDueEmails $sender): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'template' => ['required', 'string'],
            'audience' => ['required', 'in:'.implode(',', array_keys(EmailAudience::AUDIENCES))],
            'date' => ['nullable', 'date'],
            'heure' => ['nullable', 'date_format:H:i'],
            'test' => ['nullable', 'boolean'],
        ]);

        if (! EmailTemplates::isValid($data['template'])) {
            return back()->withInput()->with('ukc_error', 'Modèle e-mail inconnu.');
        }

        $event = Event::findOrFail($data['event_id']);
        $subject = EmailTemplates::raw($data['template'])['subject'];
        $body = EmailTemplates::body($data['template']);

        // Date + heure → planification (date vide = envoi immédiat).
        $scheduled = ! empty($data['date'])
            ? Carbon::parse($data['date'].' '.($data['heure'] ?: '09:00'))
            : null;

        // E-mail test à l'admin d'abord (avant la campagne réelle).
        $testSent = false;
        if ($request->boolean('test') && ($adminEmail = optional($request->user())->email)) {
            Mail::raw($body, function ($m) use ($adminEmail, $subject) {
                $m->to($adminEmail)->subject('[TEST] '.$subject);
            });
            $testSent = true;
        }

        $count = $action->handle($event, $data['audience'], $subject, $body, $scheduled);

        if ($count === 0) {
            return back()->withInput()->with('ukc_error', 'Aucun destinataire pour cette audience.'.($testSent ? ' (E-mail test envoyé à l’admin.)' : ''));
        }

        $suffix = $testSent ? ' E-mail test envoyé à l’admin.' : '';

        // Envoi immédiat : on vide tout de suite la file arrivée à échéance.
        if ($scheduled === null || $scheduled->lessThanOrEqualTo(now())) {
            $sender->handle();

            return back()->with('ukc_status', "Campagne envoyée à {$count} destinataire(s).".$suffix);
        }

        return back()->with('ukc_status', "Campagne programmée pour {$count} destinataire(s) le {$scheduled->format('d/m/Y H:i')}.".$suffix);
    }

    /** Force l'envoi des e-mails programmés arrivés à échéance. */
    public function sendDue(SendDueEmails $sender): RedirectResponse
    {
        $report = $sender->handle();

        return back()->with('ukc_status', "Envoi : {$report['sent']} envoyé(s), {$report['failed']} échec(s).");
    }

    /** Annule un e-mail encore en file. */
    public function cancel(Email $email): RedirectResponse
    {
        if ($email->status === 'queued') {
            $email->delete();
        }

        return back()->with('ukc_status', 'E-mail annulé.');
    }

    /** Relance les e-mails en échec d'une campagne (lot) ou d'un déclencheur auto. */
    public function retryBatch(Request $request, SendDueEmails $sender): RedirectResponse
    {
        $batch = (string) $request->input('batch_id');
        $template = (string) $request->input('template');
        $editionId = (string) $request->input('event_id');

        $query = Email::where('status', 'failed');
        if ($batch !== '') {
            $query->where('batch_id', $batch);
        } elseif ($template !== '' && $editionId !== '') {
            $query->where('template', $template)->where('event_id', $editionId);
        } else {
            return back()->with('ukc_error', 'Campagne introuvable pour la relance.');
        }

        $n = $query->update(['status' => 'queued', 'sent_at' => null, 'scheduled_at' => now()]);

        if ($n === 0) {
            return back()->with('ukc_status', 'Aucun e-mail en échec à relancer.');
        }

        $report = $sender->handle();

        return back()->with('ukc_status', "Relance : {$report['sent']} envoyé(s), {$report['failed']} échec(s).");
    }

    /** Annule toute une campagne (lot) encore en file. */
    public function cancelBatch(Request $request): RedirectResponse
    {
        $batch = (string) $request->input('batch_id');
        if ($batch === '') {
            return back();
        }

        $n = Email::where('batch_id', $batch)->where('status', 'queued')->delete();

        return back()->with('ukc_status', $n > 0 ? "Campagne annulée ({$n} e-mail(s) retiré(s) de la file)." : 'Aucun e-mail en file à annuler.');
    }
}
