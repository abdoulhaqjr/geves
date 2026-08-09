<?php

namespace App\Livewire\Account;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Édition du profil du compte participant. L'affiliation est requise pour
 * pouvoir soumettre (cf. Account::canSubmit()).
 */
#[Title('Mon profil')]
class Profile extends Component
{
    public string $civility = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $grade = '';
    public string $phone = '';
    public string $affiliation = '';
    public string $country = '';
    public string $bio = '';

    public array $civilities = ['M.', 'Mme', 'Dr', 'Pr'];

    public function mount(): void
    {
        $account = Auth::guard('account')->user();

        foreach (['civility', 'first_name', 'last_name', 'grade', 'phone', 'affiliation', 'country', 'bio'] as $field) {
            $this->{$field} = (string) ($account->{$field} ?? '');
        }
    }

    protected function rules(): array
    {
        return [
            'civility' => ['nullable', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:150'],
            'last_name' => ['required', 'string', 'max:150'],
            'grade' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'affiliation' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        Auth::guard('account')->user()->update($data);

        session()->flash('ukc_status', 'Profil enregistré.');
    }

    public function render()
    {
        return view('livewire.account.profile', [
            'account' => Auth::guard('account')->user(),
        ])->layout('components.layouts.participant', ['title' => 'Mon profil']);
    }
}
