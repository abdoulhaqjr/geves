@php
    $heading = $twoFactor ? 'Vérification en deux étapes' : 'Connexion';
    $sub = ! $twoFactor
        ? 'Connectez-vous avec votre compte UK Colloques.'
        : ($method === 'sms'
            ? 'Saisissez le code envoyé par SMS'.($phoneHint ? ' au '.$phoneHint : '').'.'
            : 'Saisissez le code de votre application d’authentification.');
@endphp
<x-layouts.auth title="Connexion">
    <x-slot:heading>{{ $heading }}</x-slot:heading>
    <x-slot:subheading>{{ $sub }}</x-slot:subheading>

    @if ($twoFactor)
        {{-- ===== Second facteur (2FA) ===== --}}

        <form method="POST" action="{{ route('account.login.confirm') }}" class="ukc-form-grid" style="gap:16px;">
            @csrf
            <div class="ukc-field full">
                <label for="code">Code de vérification</label>
                <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                       autofocus maxlength="10" placeholder="123456" style="letter-spacing:.3em;font-size:1.1rem;">
                @error('code') <span class="error">{{ $message }}</span> @enderror
                <span class="hint">Vous pouvez aussi saisir un code de récupération.</span>
            </div>

            <div class="ukc-field full">
                <button type="submit" class="ukc-btn ukc-btn--blue" style="width:100%;">Vérifier et se connecter</button>
            </div>
        </form>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px;flex-wrap:wrap;">
            <form method="POST" action="{{ route('account.login.back') }}">
                @csrf
                <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Changer de compte</button>
            </form>
            @if ($method === 'sms')
                <form method="POST" action="{{ route('account.login.resend') }}">
                    @csrf
                    <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                        <x-ukc.icon name="mail" :size="14" /> Renvoyer le code
                    </button>
                </form>
            @endif
        </div>
    @else
        {{-- ===== Identifiants ===== --}}
        <form method="POST" action="{{ route('account.login.store') }}" class="ukc-form-grid" style="gap:16px;">
            @csrf
            <div class="ukc-field full">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus
                       placeholder="vous@exemple.tg">
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full" x-data="{ show: false }">
                <label for="password">Mot de passe</label>
                <div style="position:relative;">
                    <input :type="show ? 'text' : 'password'" id="password" name="password"
                           autocomplete="current-password" style="width:100%;padding-right:44px;">
                    <button type="button" @click="show = !show"
                            style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--ukc-light);padding:6px;"
                            :aria-label="show ? 'Masquer' : 'Afficher'">
                        <span x-show="!show"><x-ukc.icon name="eye" :size="18" /></span>
                        <span x-show="show" x-cloak><x-ukc.icon name="eye-off" :size="18" /></span>
                    </button>
                </div>
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full" style="flex-direction:row;align-items:center;justify-content:space-between;gap:10px;">
                <label style="display:flex;align-items:center;gap:8px;font-weight:500;cursor:pointer;">
                    <input type="checkbox" name="remember" value="1" style="width:auto;padding:0;">
                    Se souvenir de moi
                </label>
                <a href="{{ route('account.password.request') }}"
                   style="font-size:.85rem;color:var(--ukc-blue);font-weight:600;">Mot de passe oublié ?</a>
            </div>

            <div class="ukc-field full">
                <button type="submit" class="ukc-btn ukc-btn--blue" style="width:100%;">Se connecter</button>
            </div>
        </form>

        <div class="auth-divider">ou</div>
        <a href="{{ route('account.register') }}" class="ukc-btn ukc-btn--outline" style="width:100%;">Créer un compte</a>

        <p class="auth-footer-link" style="color:var(--ukc-muted);">
            Personnel de gestion (DRI, comités) : connectez-vous avec vos identifiants ci-dessus.
        </p>
    @endif
</x-layouts.auth>
