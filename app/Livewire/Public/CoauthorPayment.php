<?php

namespace App\Livewire\Public;

use App\Actions\UpsertCoauthorParticipant;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use App\Payments\GatewayRegistry;
use App\Support\Fees;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Parcours public de paiement des co-auteurs (sans compte). Le co-auteur se
 * retrouve par e-mail, voit les communications acceptées auxquelles il est
 * associé, corrige son grade si besoin, puis paie au tarif de son grade.
 * Un co-auteur ayant payé devient éligible à l'attestation de communication.
 */
#[Layout('components.layouts.app')]
#[Title('Paiement co-auteur')]
class CoauthorPayment extends Component
{
    public Edition $edition;

    public string $email = '';

    public string $grade = '';

    public bool $searched = false;

    /** @var array<int, array{ref:string, title:string}> */
    public array $foundSubmissions = [];

    public ?string $coauthorName = null;

    public ?string $coauthorAffiliation = null;

    public ?Participant $participant = null;

    public ?float $amount = null;

    public ?string $instructions = null;

    public function mount(string $slug): void
    {
        $edition = Edition::where('slug', $slug)->first();
        abort_unless($edition && $edition->isPublished(), 404);

        $this->edition = $edition;

        if (filled(request('email'))) {
            $this->email = (string) request('email');
            $this->search();
        }
    }

    /** @return array<int, array{label:string, amount:float}> */
    public function feeGrid(): array
    {
        return array_map(
            fn ($f) => ['label' => (string) ($f['label'] ?? ''), 'amount' => (float) ($f['amount'] ?? 0)],
            Fees::grid($this->edition),
        );
    }

    public function search(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        $email = mb_strtolower(trim($this->email));

        $accepted = Submission::where('edition_id', $this->edition->id)
            ->where('revision_of', 0)
            ->where('status', 'accepted')
            ->get();

        $mine = $accepted->filter(fn (Submission $s) => $this->coauthorEntry($s, $email) !== null)->values();

        $this->foundSubmissions = $mine
            ->map(fn (Submission $s) => ['ref' => (string) $s->ref_code, 'title' => (string) $s->title])
            ->all();

        // Coordonnées du co-auteur depuis la 1re communication trouvée.
        $entry = $mine->isNotEmpty() ? $this->coauthorEntry($mine->first(), $email) : null;
        $this->coauthorName = $entry['name'] ?? null;
        $this->coauthorAffiliation = $entry['affiliation'] ?? null;

        $this->participant = Participant::where('edition_id', $this->edition->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        // Grade pré-rempli : participant existant, sinon grade saisi à la soumission.
        $this->grade = $this->participant?->title ?: (string) ($entry['grade'] ?? '');
        $this->searched = true;
        $this->recomputeAmount();
    }

    public function updatedGrade(): void
    {
        $this->recomputeAmount();
    }

    protected function recomputeAmount(): void
    {
        if (! $this->isEligible()) {
            $this->amount = null;

            return;
        }

        $this->amount = Fees::resolveAmount($this->edition, $this->grade)
            ?? Fees::highestAmount($this->edition);
    }

    public function isEligible(): bool
    {
        return $this->searched && $this->foundSubmissions !== [];
    }

    public function hasPaid(): bool
    {
        return $this->participant && $this->participant->hasPaid();
    }

    public function paidReceipt(): ?Payment
    {
        return $this->participant?->payments()->where('status', 'paid')->latest('id')->first();
    }

    public function pay(string $gatewayKey, UpsertCoauthorParticipant $upsert, GatewayRegistry $registry)
    {
        if (! $this->isEligible() || $this->hasPaid()) {
            $this->addError('pay', 'Paiement indisponible.');

            return null;
        }

        if (blank($this->grade)) {
            $this->addError('grade', 'Choisissez votre grade.');

            return null;
        }

        $this->recomputeAmount();
        if (! $this->amount) {
            $this->addError('pay', 'Aucun tarif ne correspond à votre grade. Contactez l’organisation.');

            return null;
        }

        $gateway = $registry->get($gatewayKey);
        if (! $gateway || ! $gateway->isAvailable()) {
            $this->addError('pay', 'Ce moyen de paiement n’est pas disponible.');

            return null;
        }

        $this->participant = $upsert->handle(
            $this->edition,
            $this->email,
            (string) ($this->coauthorName ?? $this->email),
            $this->coauthorAffiliation,
            $this->grade,
        );

        if ($this->participant->hasPaid()) {
            $this->addError('pay', 'Vous avez déjà réglé vos frais.');

            return null;
        }

        $payment = Payment::firstOrCreate(
            [
                'edition_id' => $this->edition->id,
                'participant_id' => $this->participant->id,
                'status' => 'pending',
            ],
            [
                'amount' => $this->amount,
                'currency' => config('payments.currency', 'XOF'),
                'method' => $gateway->key(),
                'gateway' => $gateway->key(),
                'meta' => ['flow' => 'coauthor'],
            ],
        );

        try {
            $result = $gateway->initiate($payment);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('pay', 'Le service de paiement est momentanément indisponible. Réessayez plus tard.');

            return null;
        }

        if (($result['type'] ?? null) === 'redirect' && ! empty($result['url'])) {
            return $this->redirect($result['url']);
        }

        $this->instructions = $result['message'] ?? 'Votre demande de paiement est enregistrée.';

        return null;
    }

    /**
     * Entrée co-auteur (non correspondant) d'une soumission correspondant à
     * l'e-mail, ou null.
     *
     * @return array<string, mixed>|null
     */
    protected function coauthorEntry(Submission $submission, string $emailLower): ?array
    {
        foreach ($submission->authors ?? [] as $author) {
            if ($author['corresponding'] ?? false) {
                continue;
            }
            if (mb_strtolower(trim($author['email'] ?? '')) === $emailLower) {
                return $author;
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.public.coauthor-payment', [
            'gateways' => app(GatewayRegistry::class)->available(),
        ]);
    }
}
