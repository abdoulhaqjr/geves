<div>
    <x-slot:heading>Confirmez votre e-mail</x-slot:heading>
    <x-slot:subheading>Une dernière étape avant de pouvoir soumettre.</x-slot:subheading>

    <p style="font-size:.92rem;color:var(--ukc-muted);line-height:1.6;">
        Nous avons envoyé un lien de confirmation à
        <strong style="color:var(--ukc-text);">{{ auth('account')->user()->email }}</strong>.
        Cliquez sur ce lien pour activer votre compte. Vous pouvez déjà consulter votre espace, mais la soumission
        d'une communication nécessite un e-mail confirmé.
    </p>

    @if ($resent)
        <div class="ukc-alert ukc-alert--ok" style="margin-top:18px;margin-bottom:0;">
            <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
            <div>Un nouveau lien vient d'être envoyé.</div>
        </div>
    @endif

    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:20px;">
        <button type="button" wire:click="resend" class="ukc-btn ukc-btn--primary" wire:loading.attr="disabled">
            <x-ukc.icon name="mail" :size="18" /> Renvoyer le lien
        </button>
        <a href="{{ route('account.dashboard') }}" wire:navigate class="ukc-btn ukc-btn--outline">
            Aller à mon espace
        </a>
        <form method="POST" action="{{ route('account.logout') }}" style="margin-left:auto;">
            @csrf
            <button type="submit" class="ukc-btn ukc-btn--ghost">
                <x-ukc.icon name="logout" :size="18" /> Se déconnecter
            </button>
        </form>
    </div>
</div>
