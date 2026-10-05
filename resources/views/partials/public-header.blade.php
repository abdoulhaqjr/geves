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
                <span>{{ __('Direction de la Recherche et de l’Innovation') }}</span>
            </div>
        </a>
        <button type="button" class="burger" id="burger" aria-label="{{ __('Ouvrir le menu') }}" aria-controls="mainNav" aria-expanded="false">☰</button>
        <nav class="nav" id="mainNav">
            <a href="{{ route('home') }}#en-cours">{{ __('En cours') }}</a>
            <a href="{{ route('home') }}#a-venir">{{ __('À venir') }}</a>
            <a href="{{ route('public.archives') }}">{{ __('Passés') }}</a>
            @if ($navEdition ?? null)
                <a href="{{ route('public.colloque', $navEdition->slug) }}">{{ __('Événement en cours') }}</a>
            @endif
            @if ($navHasLive ?? false)
                <a href="{{ route('public.deroulement') }}">{{ __('Déroulement') }}</a>
            @endif
            <a href="{{ route('home') }}#apropos">{{ __('À propos') }}</a>
            <a href="{{ route('home') }}#contact">{{ __('Contact') }}</a>
            <a href="{{ route('attestation.verify.landing') }}">{{ __('Vérifier une attestation') }}</a>
            @guest('account')
                <a href="{{ route('account.login') }}">{{ __('Se connecter') }}</a>
            @else
                <a href="{{ route('account.dashboard') }}">{{ __('Espace participant') }}</a>
            @endguest
        </nav>
    </div>
</header>
