<div>
    @php
        $heading = $twoFactor ? 'Vérification en deux étapes' : 'Connexion';
        $sub = ! $twoFactor
            ? 'Accédez à votre espace participant.'
            : ($method === 'sms'
                ? 'Saisissez le code envoyé par SMS'.($phoneHint ? ' au '.$phoneHint : '').'.'
                : 'Saisissez le code de votre application d’authentification.');
    @endphp
    <x-slot:heading>{{ $heading }}</x-slot:heading>
    <x-slot:subheading>{{ $sub }}</x-slot:subheading>

    @if ($twoFactor)
        {{-- ===== Second facteur (2FA) ===== --}}
        @if (session('ukc_status'))
            <div class="ukc-alert ukc-alert--ok" style="margin-bottom:14px;">
                <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
                <div>{{ session('ukc_status') }}</div>
            </div>
        @endif

        <form wire:submit="confirmChallenge" class="ukc-form-grid" style="gap:16px;">
            <div class="ukc-field full">
                <label for="code">Code de vérification</label>
                <input type="text" id="code" wire:model="code" inputmode="numeric" autocomplete="one-time-code"
                       autofocus maxlength="10" placeholder="123456" style="letter-spacing:.3em;font-size:1.1rem;">
                @error('code') <span class="error">{{ $message }}</span> @enderror
                <span class="hint">Vous pouvez aussi saisir un code de récupération.</span>
            </div>

            <div class="ukc-field full">
                <button type="submit" class="ukc-btn ukc-btn--blue" style="width:100%;" wire:loading.attr="disabled" wire:target="confirmChallenge">
                    <span wire:loading.remove wire:target="confirmChallenge">Vérifier et se connecter</span>
                    <span wire:loading wire:target="confirmChallenge">Vérification…</span>
                </button>
            </div>
        </form>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px;flex-wrap:wrap;">
            <button type="button" wire:click="backToLogin" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Changer de compte</button>
            @if ($method === 'sms')
                <button type="button" wire:click="resendOtp" class="ukc-btn ukc-btn--ghost ukc-btn--sm"
                        wire:loading.attr="disabled" wire:target="resendOtp">
                    <x-ukc.icon name="mail" :size="14" /> Renvoyer le code
                </button>
            @endif
        </div>
    @else
        {{-- ===== Identifiants ===== --}}
        <form wire:submit="authenticate" class="ukc-form-grid" style="gap:16px;">
            <div class="ukc-field full">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" wire:model="email" autocomplete="email" autofocus
                       placeholder="vous@exemple.tg">
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full" x-data="{ show: false }">
                <label for="password">Mot de passe</label>
                <div style="position:relative;">
                    <input :type="show ? 'text' : 'password'" id="password" wire:model="password"
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
                    <input type="checkbox" wire:model="remember" style="width:auto;padding:0;">
                    Se souvenir de moi
                </label>
                <a href="{{ route('account.password.request') }}" wire:navigate
                   style="font-size:.85rem;color:var(--ukc-blue);font-weight:600;">Mot de passe oublié ?</a>
            </div>

            <div class="ukc-field full">
                <button type="submit" class="ukc-btn ukc-btn--blue" style="width:100%;"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="authenticate">Se connecter</span>
                    <span wire:loading wire:target="authenticate">Connexion…</span>
                </button>
            </div>
        </form>

        <p style="text-align:center;font-size:.9rem;color:var(--ukc-muted);margin-top:18px;">
            Pas encore de compte ?
            <a href="{{ route('account.register') }}" wire:navigate
               style="color:var(--ukc-blue);font-weight:700;">Créer un compte</a>
        </p>
    @endif
</div>
