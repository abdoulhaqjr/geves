<x-layouts.app :title="__('Association à une communication')">
    <div class="ukc-scope" style="max-width:640px;margin:40px auto;padding:0 16px;">
        <div class="ukc-card">
            @if (session('ukc_status'))
                <div class="ukc-alert ukc-alert--ok" style="margin-bottom:16px;">
                    <span class="ico"><x-ukc.icon name="check" :size="18" /></span>
                    <div>{{ session('ukc_status') }}</div>
                </div>
            @endif

            <h1 style="font-size:1.3rem;margin-bottom:8px;">{{ __('Association à une communication') }}</h1>

            @if ($link->status === 'declined')
                <p style="color:var(--ukc-muted);line-height:1.6;">
                    {{ __('Vous n’êtes plus associé(e) à la communication « :titre ». Aucune autre action n’est requise.', ['titre' => $submission?->title]) }}
                </p>
            @else
                <p style="color:var(--ukc-muted);line-height:1.6;">
                    {!! __('Vous avez été cité(e) comme auteur de la communication <strong>« :titre »</strong> pour :edition.', ['titre' => $submission?->title, 'event' => $submission?->event?->title]) !!}
                </p>
                <p style="color:var(--ukc-muted);line-height:1.6;">
                    {{ __('Si ce n’est pas vous, retirez cette association. Sinon, vous pouvez ignorer ce message ou créer votre compte pour retrouver la communication.') }}
                </p>
                <form method="POST" action="{{ route('author.decline.confirm', ['token' => $link->token]) }}" style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
                    @csrf
                    <button type="submit" class="ukc-btn ukc-btn--danger">
                        <x-ukc.icon name="close" :size="16" /> {{ __('Ce n’est pas moi - retirer l’association') }}
                    </button>
                    <a href="{{ route('home') }}" class="ukc-btn ukc-btn--ghost">{{ __('C’est bien moi') }}</a>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
