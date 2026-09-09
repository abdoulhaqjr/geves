<x-layouts.auth :title="__('Mot de passe oublié')">
    <x-slot:heading>{{ __('Mot de passe oublié') }}</x-slot:heading>
    <x-slot:subheading>{{ __('Recevez un lien de réinitialisation par e-mail.') }}</x-slot:subheading>

    @if (session('password_reset_sent'))
        <div class="ukc-alert ukc-alert--ok" style="margin-bottom:0;">
            <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
            <div>{{ __('Si un compte existe pour cette adresse, un lien de réinitialisation vient d\'être envoyé. Vérifiez votre boîte de réception.') }}</div>
        </div>
        <p style="text-align:center;font-size:.9rem;margin-top:18px;">
            <a href="{{ route('account.login') }}" style="color:var(--ukc-blue);font-weight:700;">{{ __('Retour à la connexion') }}</a>
        </p>
    @else
        <form method="POST" action="{{ route('account.password.email') }}" class="ukc-form-grid" style="gap:16px;">
            @csrf
            <div class="ukc-field full">
                <label for="email">{{ __('Adresse e-mail') }}</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus
                       placeholder="vous@exemple.tg">
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="ukc-field full">
                <button type="submit" class="ukc-btn ukc-btn--blue" style="width:100%;">{{ __('Envoyer le lien') }}</button>
            </div>
        </form>
        <p style="text-align:center;font-size:.9rem;color:var(--ukc-muted);margin-top:18px;">
            <a href="{{ route('account.login') }}" style="color:var(--ukc-blue);font-weight:700;">{{ __('Retour à la connexion') }}</a>
        </p>
    @endif
</x-layouts.auth>
