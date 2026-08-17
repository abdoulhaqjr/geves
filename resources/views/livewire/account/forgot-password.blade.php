<div>
    <x-slot:heading>Mot de passe oublié</x-slot:heading>
    <x-slot:subheading>Recevez un lien de réinitialisation par e-mail.</x-slot:subheading>

    @if ($sent)
        <div class="ukc-alert ukc-alert--ok" style="margin-bottom:0;">
            <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
            <div>Si un compte existe pour cette adresse, un lien de réinitialisation vient d'être envoyé.
                Vérifiez votre boîte de réception.</div>
        </div>
        <p style="text-align:center;font-size:.9rem;margin-top:18px;">
            <a href="{{ route('account.login') }}" wire:navigate style="color:var(--ukc-blue);font-weight:700;">Retour à la connexion</a>
        </p>
    @else
        <form wire:submit="sendResetLink" class="ukc-form-grid" style="gap:16px;">
            <div class="ukc-field full">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" wire:model="email" autocomplete="email" autofocus
                       placeholder="vous@exemple.tg">
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="ukc-field full">
                <button type="submit" class="ukc-btn ukc-btn--blue" style="width:100%;" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="sendResetLink">Envoyer le lien</span>
                    <span wire:loading wire:target="sendResetLink">Envoi…</span>
                </button>
            </div>
        </form>
        <p style="text-align:center;font-size:.9rem;color:var(--ukc-muted);margin-top:18px;">
            <a href="{{ route('account.login') }}" wire:navigate style="color:var(--ukc-blue);font-weight:700;">Retour à la connexion</a>
        </p>
    @endif
</div>
