{{-- En-tête public partagé (accueil, archives, déroulement…). --}}
<header class="site-header">
    <div class="ukc-container inner">
        <a href="{{ route('home') }}" class="brand">
            {{-- Monogramme : l'en-tête n'a pas la largeur du logo complet, et le
                 nom de la plateforme est déjà écrit à côté. --}}
            <img src="{{ \App\Support\Branding::mark() }}" alt="{{ config('app.name') }}" class="brand-logo brand-logo--mark">
            <span class="brand-rule" aria-hidden="true"></span>
            <div class="brand-tag">
                {{ config('app.name') }}
                <span>{{ __('Gestion des événements scientifiques') }}</span>
            </div>
        </a>
        <button type="button" class="burger" id="burger" aria-label="{{ __('Ouvrir le menu') }}" aria-controls="mainNav" aria-expanded="false">☰</button>
        <nav class="nav" id="mainNav">
            {{-- ⚠️ Ces deux entrées visent des ANCRES de la page d'accueil, et
                 ces sections ne s'y affichent qu'en présence d'événements. Sans
                 garde, le menu promettait « En cours » et « Passés » alors que
                 la page n'avait ni l'un ni l'autre : le clic ne faisait rien.
                 Constaté sur geves.univkara.tg. --}}
            @if ($navHasOngoing ?? false)
                <a href="{{ route('home') }}#en-cours">{{ __('En cours') }}</a>
            @endif
            @if ($navHasUpcoming ?? false)
                <a href="{{ route('home') }}#a-venir">{{ __('À venir') }}</a>
            @endif
            @if ($navHasPast ?? false)
                <a href="{{ route('home') }}#passes">{{ __('Passés') }}</a>
            @endif
            @if ($navEdition ?? null)
                <a href="{{ route('public.colloque', $navEdition->slug) }}">{{ __('Événement en cours') }}</a>
            @endif
            @if ($navHasLive ?? false)
                <a href="{{ route('public.deroulement') }}">{{ __('Déroulement') }}</a>
            @endif
            <a href="{{ route('home') }}#apropos">{{ __('À propos') }}</a>
            <a href="{{ route('home') }}#contact">{{ __('Contact') }}</a>
            @if (auth('account')->check())
                <a href="{{ route('account.dashboard') }}">{{ __('Mon espace') }}</a>
            @elseif (auth('web')->check())
                <a href="{{ route('admin.dashboard') }}">{{ __('Mon espace') }}</a>
            @else
                <a href="{{ route('account.login') }}">{{ __('Se connecter') }}</a>
            @endif
        </nav>
    </div>
</header>
