<?php

namespace App\Livewire\Admin\Documents;

use App\Actions\IssueAttestation;
use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Submission;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Hub Documents d'une édition : émission/gestion des attestations vérifiables
 * (par type et audience), génération des badges imprimables, et livre des
 * résumés (aperçu HTML + export Word). Trois volets, une édition sélectionnée.
 */
#[Title('Documents')]
class Attestations extends Component
{
    public string $edition_id = '';

    public string $type = 'participation';

    public string $audience = 'paid';

    public function mount(): void
    {
        $this->edition_id = (string) (Edition::where('status', 'open')->latest('id')->value('id')
            ?? Edition::latest('id')->value('id') ?? '');
    }

    /** Participants ciblés selon l'audience choisie. */
    protected function targets(): Collection
    {
        $query = Participant::where('edition_id', $this->edition_id);

        return match ($this->audience) {
            'paid' => $query->whereHas('payments', fn ($q) => $q->where('status', 'paid'))->get(),
            'communicants' => $this->communicantTargets(),
            default => $query->get(),
        };
    }

    /**
     * Communicants : auteurs correspondants d'une communication acceptée, plus
     * les co-auteurs ayant réglé leurs frais (règle « co-auteur payé =
     * éligible à l'attestation de communication »).
     */
    protected function communicantTargets(): Collection
    {
        $accepted = Submission::where('edition_id', $this->edition_id)
            ->where('revision_of', 0)
            ->where('status', 'accepted')
            ->get();

        $correspondingIds = $accepted->pluck('participant_id')->filter()->unique();

        $coauthorEmails = $accepted
            ->flatMap(fn (Submission $s) => collect($s->authors ?? [])
                ->reject(fn ($a) => $a['corresponding'] ?? false)
                ->pluck('email'))
            ->filter()
            ->map(fn ($e) => mb_strtolower(trim((string) $e)))
            ->unique();

        $corresponding = Participant::where('edition_id', $this->edition_id)
            ->whereIn('id', $correspondingIds)
            ->get();

        $paidCoauthors = Participant::where('edition_id', $this->edition_id)
            ->whereHas('payments', fn ($q) => $q->where('status', 'paid'))
            ->get()
            ->filter(fn (Participant $p) => $coauthorEmails->contains(mb_strtolower(trim((string) $p->email))));

        return $corresponding->concat($paidCoauthors)->unique('id')->values();
    }

    public function issue(IssueAttestation $action): void
    {
        if ($this->edition_id === '') {
            return;
        }

        $count = 0;
        foreach ($this->targets() as $participant) {
            $action->handle($participant, $this->type);
            $count++;
        }

        session()->flash('ukc_status', $count.' attestation(s) « '.(Attestation::TYPES[$this->type] ?? $this->type).' » émise(s).');
    }

    public function revoke(int $id): void
    {
        Attestation::whereKey($id)->update(['revoked' => true]);
        session()->flash('ukc_status', 'Attestation révoquée.');
    }

    public function restore(int $id): void
    {
        Attestation::whereKey($id)->update(['revoked' => false]);
        session()->flash('ukc_status', 'Attestation rétablie.');
    }

    public function render()
    {
        $edition = $this->edition_id !== '' ? Edition::find($this->edition_id) : null;

        $attestations = $edition
            ? Attestation::where('edition_id', $edition->id)->latest('id')->get()
            : collect();

        // Compteurs de contexte pour les volets Badges / Livre.
        $counts = $edition ? [
            'participants' => Participant::where('edition_id', $edition->id)->count(),
            'accepted' => Submission::where('edition_id', $edition->id)
                ->where('revision_of', 0)->where('status', 'accepted')->count(),
            'issued' => $attestations->where('revoked', false)->count(),
        ] : ['participants' => 0, 'accepted' => 0, 'issued' => 0];

        return view('livewire.admin.documents.attestations', [
            'editions' => Edition::latest('id')->get(),
            'edition' => $edition,
            'types' => Attestation::TYPES,
            'attestations' => $attestations,
            'counts' => $counts,
        ])->layout('components.layouts.admin', ['title' => 'Documents']);
    }
}
