{{-- Pied de page public partagé. --}}
<footer class="site-footer">
    <div class="ukc-container">
        <div class="footer-grid">
            <div>
                <img src="{{ asset('img/logo-uk-white.png') }}" alt="Université de Kara" class="footer-logo">
                {{ __('Service de l’Université de Kara — Direction de la Recherche et de l’Innovation (DRI). Plateforme non commerciale.') }}
            </div>
            <div>
                <h4>{{ __('Navigation') }}</h4>
                <a href="{{ route('home') }}">{{ __('Accueil') }}</a>
                <a href="{{ route('home') }}#en-cours">{{ __('Colloque en cours') }}</a>
                <a href="{{ route('home') }}#a-venir">{{ __('À venir') }}</a>
                <a href="{{ route('public.archives') }}">{{ __('Colloques passés') }}</a>
                <a href="{{ route('home') }}#apropos">{{ __('À propos') }}</a>
                <a href="{{ route('home') }}#contact">{{ __('Contact') }}</a>
            </div>
            <div>
                <h4>{{ __('Liens utiles') }}</h4>
                @guest('account')
                    <a href="{{ route('account.login') }}">{{ __('Se connecter') }}</a>
                    <a href="{{ route('account.register') }}">{{ __('Créer un compte') }}</a>
                @else
                    <a href="{{ route('account.dashboard') }}">{{ __('Espace participant') }}</a>
                @endguest
                <a href="{{ route('public.gesco') }}">{{ __('Assistante Gesco') }}</a>
                <a href="mailto:dri@univkara.tg">dri@univkara.tg</a>
            </div>
            <div>
                <h4>{{ __('Contact DRI') }}</h4>
                <span style="display:block;padding:4px 0;">{{ __('Campus Sud, Présidence') }}</span>
                <span style="display:block;padding:4px 0;">BP 404 Kara, Togo</span>
                <a href="mailto:colloque@univkara.tg">colloque@univkara.tg</a>
            </div>
        </div>
        <div class="footer-bottom">
            <div>© {{ now()->year }} {{ __('Université de Kara · Direction de la Recherche et de l’Innovation') }}</div>
            <div>Kara, Togo</div>
        </div>
    </div>
</footer>
