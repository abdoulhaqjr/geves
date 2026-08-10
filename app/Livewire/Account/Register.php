<?php

namespace App\Livewire\Account;

use App\Models\Account;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Inscription d'un compte participant. Envoie l'e-mail de vérification puis
 * connecte la personne (l'accès à la soumission reste conditionné à la
 * vérification + profil complet, cf. Account::canSubmit()).
 */
#[Layout('components.layouts.auth')]
#[Title('Créer un compte')]
class Register extends Component
{
    public string $civility = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $grade = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** Civilités par défaut (surchargeable plus tard par la config d'édition). */
    public array $civilities = ['M.', 'Mme', 'Dr', 'Pr'];

    protected function rules(): array
    {
        return [
            'civility' => ['nullable', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:150'],
            'last_name' => ['required', 'string', 'max:150'],
            'grade' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:accounts,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function register(): void
    {
        $data = $this->validate();

        $account = Account::create($data);

        event(new Registered($account));

        Auth::guard('account')->login($account);

        $this->redirect(route('account.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.account.register');
    }
}
