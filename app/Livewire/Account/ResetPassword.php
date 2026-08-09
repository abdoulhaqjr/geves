<?php

namespace App\Livewire\Account;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Réinitialisation effective du mot de passe à partir d'un jeton signé
 * (broker « accounts »).
 */
#[Layout('components.layouts.auth')]
#[Title('Réinitialiser le mot de passe')]
class ResetPassword extends Component
{
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    protected function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ];
    }

    public function resetPassword(): void
    {
        $this->validate();

        $status = Password::broker('accounts')->reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($account) {
                $account->forceFill([
                    'password' => $this->password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($account));
            }
        );

        if ($status !== Password::PasswordReset) {
            $this->addError('email', __($status));

            return;
        }

        session()->flash('ukc_status', __('Mot de passe réinitialisé. Vous pouvez vous connecter.'));
        $this->redirect(route('account.login'), navigate: true);
    }

    public function render()
    {
        return view('livewire.account.reset-password');
    }
}
