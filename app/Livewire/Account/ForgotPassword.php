<?php

namespace App\Livewire\Account;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Demande de réinitialisation de mot de passe (broker « accounts »). Réponse
 * anti-énumération : on affiche toujours un message neutre de succès.
 */
#[Layout('components.layouts.auth')]
#[Title('Mot de passe oublié')]
class ForgotPassword extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    public bool $sent = false;

    public function sendResetLink(): void
    {
        $this->validate();

        Password::broker('accounts')->sendResetLink(['email' => $this->email]);

        // Toujours neutre (ne pas révéler si l'e-mail existe).
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.account.forgot-password');
    }
}
