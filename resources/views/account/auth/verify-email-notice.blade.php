<x-layouts.auth :title="__('Vérifiez votre e-mail')">
    <x-slot:heading>{{ __('Confirmez votre e-mail') }}</x-slot:heading>
    <x-slot:subheading>{{ __('Une dernière étape avant de pouvoir soumettre.') }}</x-slot:subheading>

    <p style="font-size:.92rem;color:var(--ukc-muted);line-height:1.6;">
        {{ __('Nous avons envoyé un lien de confirmation à') }}
        <strong style="color:var(--ukc-text);">{{ auth('account')->user()->email }}</strong>.
        {{ __('Cliquez sur ce lien pour activer votre compte. Vous pouvez déjà consulter votre espace, mais la soumission d\'une communication nécessite un e-mail confirmé.') }}
    </p>

    @if (session('verification_resent'))
        <div class="ukc-alert ukc-alert--ok" style="margin-top:18px;margin-bottom:0;">
            <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
            <div>{{ __('Un nouveau lien vient d\'être envoyé.') }}</div>
        </div>
    @endif

    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:20px;">
        <form method="POST" action="{{ route('account.verification.resend') }}">
            @csrf
            <button type="submit" class="ukc-btn ukc-btn--blue">
                <x-ukc.icon name="mail" :size="18" /> {{ __('Renvoyer le lien') }}
            </button>
        </form>
        <a href="{{ route('account.dashboard') }}" class="ukc-btn ukc-btn--outline">
            {{ __('Aller à mon espace') }}
        </a>
        <form method="POST" action="{{ route('account.logout') }}" style="margin-left:auto;">
            @csrf
            <button type="submit" class="ukc-btn ukc-btn--ghost">
                <x-ukc.icon name="logout" :size="18" /> {{ __('Se déconnecter') }}
            </button>
        </form>
    </div>
</x-layouts.auth>
