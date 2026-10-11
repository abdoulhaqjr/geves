{{-- Pied de page public partagé. --}}
<footer class="site-footer">
    <div class="ukc-container">
        <div class="footer-grid">
            <div>
                <img src="{{ \App\Support\Branding::wideWhite() }}" alt="{{ config('app.name') }}"
                     @class(['footer-logo', 'logo-auto-white' => \App\Support\Branding::whitensOnDark(\App\Support\Branding::WIDE)])>
                {{ __('Service de l’Université de Kara - Direction de la Recherche et de l’Innovation (DRI). Plateforme non commerciale.') }}
            </div>
            <div>
                <h4>{{ __('Navigation') }}</h4>
                <a href="{{ route('home') }}">{{ __('Accueil') }}</a>
                {{-- ⚠️ Mêmes ancres que le menu, mêmes gardes : ces sections ne
                     s'affichent sur l'accueil qu'en présence d'événements, et un
                     lien vers une ancre absente ne fait rien du tout. --}}
                @if ($navHasOngoing ?? false)
                    <a href="{{ route('home') }}#en-cours">{{ __('Événement en cours') }}</a>
                @endif
                @if ($navHasUpcoming ?? false)
                    <a href="{{ route('home') }}#a-venir">{{ __('À venir') }}</a>
                @endif
                <a href="{{ route('public.archives') }}">{{ __('Événements passés') }}</a>
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
                <a href="{{ route('public.gesco') }}">{{ __('Assistante :name', ['name' => \App\Support\AiAgents::publicName()]) }}</a>
                {{-- ⚠️ Retirée du menu du haut, mais PAS du site : cette page
                     existe pour un tiers - un recruteur qui contrôle une
                     attestation reçue. La laisser sans aucune entrée de
                     navigation la rendrait introuvable à qui n'a pas le lien. --}}
                <a href="{{ route('attestation.verify.landing') }}">{{ __('Vérifier une attestation') }}</a>
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
