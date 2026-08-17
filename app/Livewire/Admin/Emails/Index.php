<?php

namespace App\Livewire\Admin\Emails;

use App\Actions\QueueEmailCampaign;
use App\Actions\SendDueEmails;
use App\Models\Edition;
use App\Models\Email;
use App\Support\EmailAudience;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Campagnes d'e-mails ciblées (audiences) et rappels programmés. Compose un
 * message pour une audience d'une édition, l'envoie tout de suite ou à une
 * date planifiée, et affiche le journal des e-mails (en file / envoyés / échecs).
 */
#[Title('E-mails')]
class Index extends Component
{
    use WithPagination;

    public string $edition_id = '';

    public string $audience = 'all';

    public string $subject = '';

    public string $body = '';

    public string $scheduled_at = '';

    public string $statusFilter = '';

    public function mount(): void
    {
        $this->edition_id = (string) (Edition::where('status', 'open')->latest('id')->value('id')
            ?? Edition::latest('id')->value('id') ?? '');
    }

    public function updating($name): void
    {
        if (in_array($name, ['edition_id', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    protected function rules(): array
    {
        return [
            'edition_id' => ['required', 'exists:editions,id'],
            'audience' => ['required', 'in:'.implode(',', array_keys(EmailAudience::AUDIENCES))],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }

    /** Met la campagne en file (immédiate si aucune date, sinon programmée). */
    public function queueCampaign(QueueEmailCampaign $action, SendDueEmails $sender): void
    {
        $data = $this->validate();

        $edition = Edition::findOrFail($data['edition_id']);
        $scheduled = $data['scheduled_at'] !== '' ? Carbon::parse($data['scheduled_at']) : null;

        $count = $action->handle($edition, $data['audience'], $data['subject'], $data['body'], $scheduled);

        if ($count === 0) {
            session()->flash('ukc_error', 'Aucun destinataire pour cette audience.');

            return;
        }

        // Envoi immédiat : on vide tout de suite la file arrivée à échéance
        // (sans attendre le prochain passage du planificateur).
        if ($scheduled === null || $scheduled->lessThanOrEqualTo(now())) {
            $sender->handle();
            session()->flash('ukc_status', "Campagne envoyée à {$count} destinataire(s).");
        } else {
            session()->flash('ukc_status', "Campagne programmée pour {$count} destinataire(s) le {$scheduled->format('d/m/Y H:i')}.");
        }

        $this->reset('subject', 'body', 'scheduled_at');
    }

    /** Force l'envoi des e-mails programmés arrivés à échéance. */
    public function sendDueNow(SendDueEmails $sender): void
    {
        $report = $sender->handle();
        session()->flash('ukc_status', "Envoi : {$report['sent']} envoyé(s), {$report['failed']} échec(s).");
    }

    /** Annule un e-mail encore en file. */
    public function cancel(int $id): void
    {
        Email::where('id', $id)->where('status', 'queued')->delete();
        session()->flash('ukc_status', 'E-mail annulé.');
    }

    public function render()
    {
        $edition = $this->edition_id !== '' ? Edition::find($this->edition_id) : null;

        $recipientCount = $edition && EmailAudience::isValid($this->audience)
            ? EmailAudience::count($edition, $this->audience)
            : 0;

        $journal = Email::query()
            ->with('participant')
            ->when($this->edition_id !== '', fn ($q) => $q->where('edition_id', $this->edition_id))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate(15);

        $queuedDue = Email::where('status', 'queued')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->count();

        // KPIs du journal (portée = édition sélectionnée si filtrée).
        $counts = Email::query()
            ->when($this->edition_id !== '', fn ($q) => $q->where('edition_id', $this->edition_id))
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $stats = [
            'total' => (int) $counts->sum(),
            'queued' => (int) $counts->get('queued', 0),
            'sent' => (int) $counts->get('sent', 0),
            'failed' => (int) $counts->get('failed', 0),
        ];

        return view('livewire.admin.emails.index', [
            'editions' => Edition::latest('id')->get(),
            'audiences' => EmailAudience::AUDIENCES,
            'recipientCount' => $recipientCount,
            'journal' => $journal,
            'queuedDue' => $queuedDue,
            'stats' => $stats,
        ])->layout('components.layouts.admin', ['title' => 'E-mails']);
    }
}
