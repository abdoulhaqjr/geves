<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Civilities;
use App\Support\ParticipantTypes;
use App\Support\Phone;
use App\Support\Turnstile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Inscription d'un compte participant (contrôleur + Blade, sans Livewire).
 * Envoie l'e-mail de vérification puis connecte la personne (l'accès à la
 * soumission reste conditionné à la vérification + profil complet, cf.
 * User::canSubmit()).
 */
class RegisterController extends Controller
{
    public function create(): View|RedirectResponse
    {
        // Déjà connecté au back-office (guard web) : pas de création de compte,
        // renvoyer vers l'espace. (Le guard `account` est déjà géré par `guest`.)
        if (Auth::guard('web')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('account.auth.register', [
            // Civilités proposées - source unique App\Support\Civilities.
            'civilities' => Civilities::all(),
            // ⚠️ Source UNIQUE des grades (cf. ParticipantTypes) : une liste propre
            // à ce formulaire produisait des valeurs (« Doctorant / étudiant »…) que
            // la grille tarifaire ne pouvait plus rapprocher - le montant restait
            // « à préciser » au moment de payer.
            'titles' => ParticipantTypes::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Turnstile::validate($request, 'registration');

        $request->merge(['phone' => Phone::normalize($request->input('phone'))]);

        $data = $request->validate([
            'civility' => ['nullable', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:150'],
            'last_name' => ['required', 'string', 'max:150'],
            'grade' => ['nullable', 'string', 'max:100'],
            'affiliation' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            // Identifiant fort (rapprochement auteurs/formateurs) + pré-remplissage
            // de la passerelle de paiement : obligatoire dès l'inscription.
            // ⚠️ UNIQUE : le serveur impose l'identité du compte qu'un numéro
            // désigne, deux comptes ne peuvent donc pas partager le même.
            'phone' => ['required', 'string', 'max:50', 'unique:users,phone'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ]);

        // `terms` sert uniquement au consentement : on ne le persiste pas.
        unset($data['terms']);

        $account = User::create($data);

        event(new Registered($account));

        Auth::guard('account')->login($account);
        $account->recordSuccessfulLogin($request->ip());

        return redirect()->route('account.dashboard');
    }
}
