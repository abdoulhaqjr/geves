<x-layouts.auth title="Créer un compte" width="620px">
    <x-slot:heading>Créer un compte</x-slot:heading>
    <x-slot:subheading>Inscription pour participer aux colloques : ouvrez votre espace participant.</x-slot:subheading>

    {{-- Panneau de marque propre à l'inscription (mockup inscription.html). --}}
    <x-slot:brandBody>
        <h1>Rejoignez la communauté <em>scientifique</em></h1>
        <p>
            Un compte unique pour soumettre des résumés, vous inscrire aux colloques,
            télécharger badges et attestations, et échanger avec les comités.
        </p>
        <ul>
            <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Compte sécurisé (2FA optionnelle)
            </li>
            <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Notifications sur vos dossiers
            </li>
            <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Plateforme institutionnelle non commerciale
            </li>
        </ul>
    </x-slot:brandBody>

    <form method="POST" action="{{ route('account.register.store') }}" class="ukc-form-grid" style="gap:16px;">
        @csrf
        {{-- Ligne 1 : Civilité + Titre --}}
        <div class="ukc-field">
            <label for="civility">Civilité</label>
            <select id="civility" name="civility">
                <option value="">—</option>
                @foreach ($civilities as $c)
                    <option value="{{ $c }}" @selected(old('civility') === $c)>{{ $c }}</option>
                @endforeach
            </select>
            @error('civility') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field">
            <label for="grade">Titre <span class="hint">(profil principal)</span></label>
            <select id="grade" name="grade">
                <option value="">— Choisir</option>
                @foreach ($titles as $t)
                    <option value="{{ $t }}" @selected(old('grade') === $t)>{{ $t }}</option>
                @endforeach
            </select>
            @error('grade') <span class="error">{{ $message }}</span> @enderror
        </div>

        {{-- Ligne 2 : Prénom + Nom --}}
        <div class="ukc-field">
            <label for="first_name">Prénom <span class="req">*</span></label>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name">
            @error('first_name') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field">
            <label for="last_name">Nom <span class="req">*</span></label>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name">
            @error('last_name') <span class="error">{{ $message }}</span> @enderror
        </div>

        {{-- Ligne 3 : E-mail + Institution --}}
        <div class="ukc-field">
            <label for="email">Adresse e-mail <span class="req">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="vous@exemple.tg">
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field">
            <label for="affiliation">Institution / affiliation</label>
            <input type="text" id="affiliation" name="affiliation" value="{{ old('affiliation') }}" autocomplete="organization" placeholder="Ex. Université de Kara">
            @error('affiliation') <span class="error">{{ $message }}</span> @enderror
        </div>

        {{-- Ligne 4 : Mot de passe + Confirmation (œil sur les deux) --}}
        <div class="ukc-field" x-data="{ show: false }">
            <label for="password">Mot de passe <span class="req">*</span></label>
            <div style="position:relative;">
                <input :type="show ? 'text' : 'password'" id="password" name="password"
                       autocomplete="new-password" style="width:100%;padding-right:44px;" placeholder="8 caractères min.">
                <button type="button" @click="show = !show"
                        style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--ukc-light);padding:6px;"
                        :aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                    <span x-show="!show"><x-ukc.icon name="eye" :size="18" /></span>
                    <span x-show="show" x-cloak><x-ukc.icon name="eye-off" :size="18" /></span>
                </button>
            </div>
            @error('password') <span class="error">{{ $message }}</span> @enderror
            <span class="hint">8 caractères min. · lettres, chiffres, symbole</span>
        </div>

        <div class="ukc-field" x-data="{ show: false }">
            <label for="password_confirmation">Confirmer le mot de passe <span class="req">*</span></label>
            <div style="position:relative;">
                <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation"
                       autocomplete="new-password" style="width:100%;padding-right:44px;" placeholder="Retapez le mot de passe">
                <button type="button" @click="show = !show"
                        style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--ukc-light);padding:6px;"
                        :aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                    <span x-show="!show"><x-ukc.icon name="eye" :size="18" /></span>
                    <span x-show="show" x-cloak><x-ukc.icon name="eye-off" :size="18" /></span>
                </button>
            </div>
        </div>

        <div class="ukc-field full">
            <label style="display:flex;align-items:flex-start;gap:8px;font-weight:500;cursor:pointer;font-size:.9rem;">
                <input type="checkbox" name="terms" value="1" @checked(old('terms')) style="width:auto;padding:0;margin-top:3px;">
                <span>J’accepte les conditions d’utilisation de la plateforme institutionnelle.</span>
            </label>
            @error('terms') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field full">
            <button type="submit" class="ukc-btn ukc-btn--blue" style="width:100%;">Créer mon compte</button>
        </div>
    </form>

    <p style="text-align:center;font-size:.9rem;color:var(--ukc-muted);margin-top:18px;">
        Déjà inscrit ?
        <a href="{{ route('account.login') }}" style="color:var(--ukc-blue);font-weight:700;">Se connecter</a>
    </p>
</x-layouts.auth>
