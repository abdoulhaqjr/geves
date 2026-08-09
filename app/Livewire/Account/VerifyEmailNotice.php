<?php

namespace App\Livewire\Account;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Écran invitant à confirmer l'adresse e-mail, avec renvoi du lien.
 */
#[Layout('components.layouts.auth')]
#[Title('Vérifiez votre e-mail')]
class VerifyEmailNotice extends Component
{
    public bool $resent = false;

    public function mount(): void
    {
        // Déjà vérifié : on file au tableau de bord.
        if (Auth::guard('account')->user()?->hasVerifiedEmail()) {
            $this->redirect(route('account.dashboard'), navigate: true);
        }
    }

    public function resend(): void
    {
        Auth::guard('account')->user()->sendEmailVerificationNotification();
        $this->resent = true;
    }

    public function render()
    {
        return view('livewire.account.verify-email-notice');
    }
}
