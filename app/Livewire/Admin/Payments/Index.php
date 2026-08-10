<?php

namespace App\Livewire\Admin\Payments;

use App\Actions\ConfirmPayment;
use App\Models\Edition;
use App\Models\Payment;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Encaissements du back-office : liste par édition, totaux, filtres et actions
 * (marquer réglé, rembourser).
 */
#[Title('Paiements')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $edition_id = '';

    public function updating($name): void
    {
        if (in_array($name, ['search', 'status', 'edition_id'], true)) {
            $this->resetPage();
        }
    }

    public function markPaid(int $id, ConfirmPayment $confirm): void
    {
        $payment = Payment::find($id);
        if (! $payment) {
            return;
        }

        try {
            $confirm->handle($payment);
            session()->flash('ukc_status', 'Paiement marqué réglé — reçu envoyé au participant.');
        } catch (\DomainException $e) {
            session()->flash('ukc_error', $e->getMessage());
        }
    }

    public function refund(int $id): void
    {
        Payment::whereKey($id)->update(['status' => 'refunded']);
        session()->flash('ukc_status', 'Paiement remboursé.');
    }

    public function render()
    {
        $base = Payment::query()
            ->when($this->edition_id !== '', fn ($q) => $q->where('edition_id', $this->edition_id));

        $payments = (clone $base)
            ->with(['participant', 'edition'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->search !== '', function ($q) {
                $term = "%{$this->search}%";
                $q->where(fn ($p) => $p->where('receipt_no', 'like', $term)
                    ->orWhereHas('participant', fn ($pa) => $pa
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term)));
            })
            ->latest('id')
            ->paginate(15);

        return view('livewire.admin.payments.index', [
            'payments' => $payments,
            'editions' => Edition::latest('id')->get(),
            'statuses' => Payment::statuses(),
            'totalPaid' => (clone $base)->where('status', 'paid')->sum('amount'),
            'totalPending' => (clone $base)->where('status', 'pending')->sum('amount'),
        ])->layout('components.layouts.admin', ['title' => 'Paiements']);
    }
}
