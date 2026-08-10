<?php

namespace App\Livewire\Admin\Payments;

use App\Actions\ConfirmPayment;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Support\Fees;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Enregistrement d'un encaissement manuel : participant existant ou nouveau,
 * montant pré-rempli selon le profil, moyen, option « marquer réglé ».
 */
#[Title('Enregistrer un encaissement')]
class Record extends Component
{
    public string $edition_id = '';

    public string $participant_id = '';

    // Nouveau participant
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $amount = '';

    public string $method = 'cash';

    public string $note = '';

    public bool $markPaidNow = true;

    public function mount(): void
    {
        $this->edition_id = (string) (Edition::where('status', 'open')->latest('id')->value('id')
            ?? Edition::latest('id')->value('id') ?? '');
    }

    /** Pré-remplit le montant depuis le profil du participant sélectionné. */
    public function updatedParticipantId($value): void
    {
        $participant = Participant::find($value);
        if ($participant && $participant->edition) {
            $amount = Fees::resolveAmount($participant->edition, $participant->title);
            if ($amount !== null) {
                $this->amount = (string) $amount;
            }
        }
    }

    protected function rules(): array
    {
        return [
            'edition_id' => ['required', 'exists:editions,id'],
            'participant_id' => ['nullable', 'exists:participants,id'],
            'first_name' => ['nullable', 'string', 'max:150'],
            'last_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'in:'.implode(',', array_keys(Payment::MANUAL_METHODS))],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(ConfirmPayment $confirm)
    {
        $data = $this->validate();

        // Résolution du participant (existant ou nouveau).
        if ($this->participant_id !== '') {
            $participant = Participant::findOrFail($this->participant_id);
        } else {
            if (blank($this->last_name) || blank($this->email)) {
                $this->addError('last_name', 'Choisissez un participant existant ou renseignez nom + e-mail.');

                return null;
            }
            $participant = Participant::firstOrCreate(
                ['edition_id' => $data['edition_id'], 'email' => mb_strtolower($this->email)],
                ['first_name' => $this->first_name, 'last_name' => $this->last_name, 'role' => 'attendee']
            );
        }

        // Garde anti-double-paiement.
        if ($participant->hasPaid()) {
            $this->addError('amount', 'Ce participant a déjà réglé ses frais.');

            return null;
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
            'meta' => ['note' => $this->note ?: null, 'recorded_by_name' => Auth::user()?->name],
        ]);

        if ($this->markPaidNow) {
            try {
                $confirm->handle($payment);
            } catch (\DomainException $e) {
                $this->addError('amount', $e->getMessage());

                return null;
            }
        }

        session()->flash('ukc_status', 'Encaissement enregistré.');

        return $this->redirect(route('admin.payments.index'), navigate: true);
    }

    public function render()
    {
        $participants = $this->edition_id !== ''
            ? Participant::where('edition_id', $this->edition_id)->orderBy('last_name')->get()
            : collect();

        return view('livewire.admin.payments.record', [
            'editions' => Edition::latest('id')->get(),
            'participants' => $participants,
            'methods' => Payment::MANUAL_METHODS,
        ])->layout('components.layouts.admin', ['title' => 'Enregistrer un encaissement']);
    }
}
