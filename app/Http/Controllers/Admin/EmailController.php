<?php

namespace App\Http\Controllers\Admin;

use App\Actions\QueueEmailCampaign;
use App\Actions\SendDueEmails;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Email;
use App\Support\EmailAudience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Campagnes d'e-mails ciblées (audiences) et rappels programmés, contrôleur +
 * Blade. Compose un message pour une audience d'une édition, l'envoie tout de
 * suite ou à une date planifiée, et affiche le journal des e-mails. L'édition
 * et le statut filtrent journal/KPIs via query string ; le nombre de
 * destinataires par audience est pré-calculé et affiché côté client (Alpine).
 */
class EmailController extends Controller
{
    public function index(Request $request): View
    {
        $editionId = (string) $request->query('edition_id', '');
        if ($editionId === '') {
            $editionId = (string) (Edition::where('status', 'open')->latest('id')->value('id')
                ?? Edition::latest('id')->value('id') ?? '');
        }
        $status = (string) $request->query('status', '');

        $edition = $editionId !== '' ? Edition::find($editionId) : null;

        // Nombre de destinataires par audience pour l'édition sélectionnée.
        $recipientCounts = [];
        foreach (array_keys(EmailAudience::AUDIENCES) as $key) {
            $recipientCounts[$key] = $edition ? EmailAudience::count($edition, $key) : 0;
        }

        $journal = Email::query()
            ->with('participant')
            ->when($editionId !== '', fn ($q) => $q->where('edition_id', $editionId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $counts = Email::query()
            ->when($editionId !== '', fn ($q) => $q->where('edition_id', $editionId))
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return view('admin.emails.index', [
            'editions' => Edition::latest('id')->get(),
            'audiences' => EmailAudience::AUDIENCES,
            'edition_id' => $editionId,
            'status' => $status,
            'recipientCounts' => $recipientCounts,
            'journal' => $journal,
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
            'edition_id' => ['required', 'exists:editions,id'],
            'audience' => ['required', 'in:'.implode(',', array_keys(EmailAudience::AUDIENCES))],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $edition = Edition::findOrFail($data['edition_id']);
        $scheduled = ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;

        $count = $action->handle($edition, $data['audience'], $data['subject'], $data['body'], $scheduled);

        if ($count === 0) {
            return back()->withInput()->with('ukc_error', 'Aucun destinataire pour cette audience.');
        }

        // Envoi immédiat : on vide tout de suite la file arrivée à échéance.
        if ($scheduled === null || $scheduled->lessThanOrEqualTo(now())) {
            $sender->handle();

            return back()->with('ukc_status', "Campagne envoyée à {$count} destinataire(s).");
        }

        return back()->with('ukc_status', "Campagne programmée pour {$count} destinataire(s) le {$scheduled->format('d/m/Y H:i')}.");
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
}
