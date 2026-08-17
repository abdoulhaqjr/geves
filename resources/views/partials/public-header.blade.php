{{-- En-tête public partagé (accueil, archives, déroulement…). --}}
<header class="site-header">
    <div class="ukc-container inner">
        <a href="{{ route('home') }}" class="brand">
            <div class="mark">UK</div>
            <div>
                UK Colloques
                <span>Université de Kara · DRI</span>
            </div>
        </a>
        <button type="button" class="burger" id="burger" aria-label="Ouvrir le menu" aria-controls="mainNav" aria-expanded="false">☰</button>
        <nav class="nav" id="mainNav">
            <a href="{{ route('home') }}#en-cours">En cours</a>
            <a href="{{ route('home') }}#a-venir">À venir</a>
            <a href="{{ route('public.archives') }}">Passés</a>
            <a href="{{ route('home') }}#apropos">À propos</a>
            <a href="{{ route('home') }}#contact">Contact</a>
            <a href="{{ route('attestation.verify.landing') }}">Vérifier une attestation</a>
            <a href="{{ route('account.login') }}">Se connecter</a>
            <a href="{{ route('account.dashboard') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">Espace participant</a>
        </nav>
    </div>
</header>
