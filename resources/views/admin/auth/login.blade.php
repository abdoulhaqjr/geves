<x-layouts.auth title="Back-office — Connexion">
    <x-slot:heading>Back-office</x-slot:heading>
    <x-slot:subheading>Espace de gestion des colloques (DRI &amp; comités).</x-slot:subheading>

    {{-- Panneau de marque propre au back-office. --}}
    <x-slot:brandBody>
        <h1>Espace de gestion <em>DRI &amp; comités</em></h1>
        <p>
            Pilotez les éditions, les soumissions et l’évaluation, les inscriptions
            et paiements, le programme, les documents officiels et les archives.
        </p>
        <ul>
            <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Soumissions, évaluation & décisions
            </li>
            <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Inscriptions, paiements & badges
            </li>
            <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Programme, attestations & rapports
            </li>
        </ul>
    </x-slot:brandBody>

    <form method="POST" action="{{ route('admin.login.store') }}" class="ukc-form-grid" style="gap:16px;">
        @csrf
        <div class="ukc-field full">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus>
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field full" x-data="{ show: false }">
            <label for="password">Mot de passe</label>
            <div style="position:relative;">
                <input :type="show ? 'text' : 'password'" id="password" name="password"
                       autocomplete="current-password" style="width:100%;padding-right:44px;">
                <button type="button" @click="show = !show"
                        style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--ukc-light);padding:6px;">
                    <span x-show="!show"><x-ukc.icon name="eye" :size="18" /></span>
                    <span x-show="show" x-cloak><x-ukc.icon name="eye-off" :size="18" /></span>
                </button>
            </div>
            @error('password') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field full">
            <label style="display:flex;align-items:center;gap:8px;font-weight:500;cursor:pointer;">
                <input type="checkbox" name="remember" value="1" style="width:auto;padding:0;">
                Se souvenir de moi
            </label>
        </div>

        <div class="ukc-field full">
            <button type="submit" class="ukc-btn ukc-btn--blue" style="width:100%;">Se connecter</button>
        </div>
    </form>
</x-layouts.auth>
