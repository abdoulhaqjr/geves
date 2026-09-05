{{-- En-tête public partagé (accueil, archives, déroulement…). --}}
<header class="site-header">
    <div class="ukc-container inner">
        <a href="{{ route('home') }}" class="brand">
            <img src="{{ asset('img/logo-uk.png') }}" alt="Université de Kara" class="brand-logo">
            <div class="brand-tag">
                Colloques scientifiques
                <span>Direction de la Recherche et de l’Innovation</span>
            </div>
        </a>
        <button type="button" class="burger" id="burger" aria-label="Ouvrir le menu" aria-controls="mainNav" aria-expanded="false">☰</button>
        <nav class="nav" id="mainNav">
            <a href="{{ route('home') }}#en-cours">En cours</a>
            <a href="{{ route('home') }}#a-venir">À venir</a>
            <a href="{{ route('public.archives') }}">Passés</a>
            @if ($navEdition ?? null)
                <a href="{{ route('public.colloque', $navEdition->slug) }}">Édition en cours</a>
            @endif
            @if ($navHasLive ?? false)
                <a href="{{ route('public.deroulement') }}">Déroulement</a>
            @endif
            <a href="{{ route('home') }}#apropos">À propos</a>
            <a href="{{ route('home') }}#contact">Contact</a>
            <a href="{{ route('attestation.verify.landing') }}">Vérifier une attestation</a>
            @guest('account')
                <a href="{{ route('account.login') }}">Se connecter</a>
            @else
                <a href="{{ route('account.dashboard') }}">Espace participant</a>
            @endguest
        </nav>
    </div>
</header>
