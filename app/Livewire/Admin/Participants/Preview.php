<?php

namespace App\Livewire\Admin\Participants;

use App\Models\Attestation;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * « Sa page » — aperçu en LECTURE SEULE de l'espace d'un participant, tel qu'il
 * le voit (communications, paiements & reçus, attestations). Aucune action
 * possible : les boutons de paiement/profil sont neutralisés. Réservé au staff.
 */
#[Title('Aperçu participant')]
class Preview extends Component
{
    public Participant $participant;

    public function mount(Participant $participant): void
    {
        $this->participant = $participant->load('edition', 'account');
    }

    public function render()
    {
        $id = $this->participant->id;

        return view('livewire.admin.participants.preview', [
            'submissions' => Submission::where('participant_id', $id)
                ->where('revision_of', 0)->latest('id')->get(),
            'payments' => Payment::where('participant_id', $id)->latest('id')->get(),
            'attestations' => Attestation::where('participant_id', $id)
                ->where('revoked', false)->latest('id')->get(),
        ])->layout('components.layouts.admin', ['title' => 'Aperçu — '.$this->participant->full_name]);
    }
}
