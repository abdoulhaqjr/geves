<?php

namespace App\Livewire\Admin;

use App\Models\Edition;
use App\Models\Participant;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pointage à l'accueil par scan du QR de badge (ou saisie manuelle du code).
 * Le QR encode « {edition}-{participant} » ; on retrouve le participant, on
 * affiche son statut (rôle, paiement, présence) et on enregistre sa présence.
 *
 * @phpstan-type FoundParticipant array{id: int, name: string, role: string, institution: string, email: string, paid: bool, checkedIn: bool, checkedAt: ?string, editionTitle: ?string}
 */
#[Title('Pointage des badges')]
class BadgeScan extends Component
{
    public string $code = '';

    /** @var FoundParticipant|null */
    public ?array $found = null;

    public string $message = '';

    public string $messageType = '';

    /** @var list<array{name: string, at: string}> */
    public array $recent = [];

    /** Recherche un participant depuis un code scanné/saisi. */
    public function lookup(?string $raw = null): void
    {
        $raw = trim($raw ?? $this->code);
        $this->code = '';

        if ($raw === '') {
            return;
        }

        // Format du QR : « {editionId}-{participantId} » ; on tolère un id seul.
        $pid = null;
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $raw, $m)) {
            $pid = (int) $m[2];
        } elseif (ctype_digit($raw)) {
            $pid = (int) $raw;
        }

        $participant = $pid ? Participant::with('edition')->find($pid) : null;

        if (! $participant) {
            $this->found = null;
            $this->flash('Badge non reconnu : '.$raw, 'err');

            return;
        }

        $this->found = $this->present($participant);
        $this->message = '';
    }

    /** Enregistre la présence du participant affiché. */
    public function checkIn(): void
    {
        if (! $this->found) {
            return;
        }

        $participant = Participant::find($this->found['id']);
        if (! $participant) {
            return;
        }

        $already = $participant->isCheckedIn();
        if (! $already) {
            $participant->update(['checked_in_at' => now()]);
            array_unshift($this->recent, ['name' => $participant->full_name, 'at' => now()->format('H:i')]);
            $this->recent = array_slice($this->recent, 0, 8);
        }

        $this->found = $this->present($participant->fresh());
        $this->flash($participant->full_name.($already ? ' était déjà pointé.' : ' — présence enregistrée.'), 'ok');
    }

    public function clearFound(): void
    {
        $this->found = null;
        $this->message = '';
    }

    /**
     * @return FoundParticipant
     */
    private function present(Participant $p): array
    {
        return [
            'id' => (int) $p->id,
            'name' => $p->full_name,
            'role' => $p->roleLabel(),
            'institution' => (string) $p->institution,
            'email' => (string) $p->email,
            'paid' => $p->payments()->where('status', 'paid')->exists(),
            'checkedIn' => $p->isCheckedIn(),
            'checkedAt' => $p->checked_in_at?->translatedFormat('d/m H:i'),
            'editionTitle' => $p->edition?->title,
        ];
    }

    private function flash(string $message, string $type): void
    {
        $this->message = $message;
        $this->messageType = $type;
    }

    public function render()
    {
        return view('livewire.admin.badge-scan', [
            'checkedCount' => Participant::whereNotNull('checked_in_at')->count(),
            'totalCount' => Participant::count(),
            'todayEditions' => Edition::latest('id')->take(1)->get(),
        ])->layout('components.layouts.admin', ['title' => 'Pointage des badges']);
    }
}
