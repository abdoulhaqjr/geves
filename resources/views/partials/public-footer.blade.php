{{-- Pied de page public partagé. --}}
<footer class="site-footer">
    <div class="ukc-container">
        <div class="footer-grid">
            <div>
                <img src="{{ \App\Support\Branding::wideWhite() }}" alt="{{ config('app.name') }}"
                     @class(['footer-logo', 'logo-auto-white' => \App\Support\Branding::whitensOnDark(\App\Support\Branding::WIDE)])>
                {{ __('Plateforme de Gestion des événements Scientifiques') }}<br>
                {{ __('Direction de la Recherche et de l’Innovation (DRI)') }}
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
            </div>
            <div>
                <h4>{{ __('Liens utiles') }}</h4>
                @if (auth('account')->check())
                    <a href="{{ route('account.dashboard') }}">{{ __('Mon espace') }}</a>
                @elseif (auth('web')->check())
                    <a href="{{ route('admin.dashboard') }}">{{ __('Mon espace') }}</a>
                @else
                    <a href="{{ route('account.login') }}">{{ __('Se connecter') }}</a>
                    <a href="{{ route('account.register') }}">{{ __('Créer un compte') }}</a>
                @endif
                <a href="{{ route('public.gesco') }}">{{ __('Assistante :name', ['name' => \App\Support\AiAgents::publicName()]) }}</a>
                {{-- ⚠️ Cette page sert un TIERS : un recruteur qui contrôle une attestation
                     reçue. Elle a quitté la barre du haut ; si elle quitte aussi le pied de
                     page, elle devient introuvable à qui n'a pas le lien. Gardée par
                     PageAccueilTest. --}}
                <a href="{{ route('attestation.verify.landing') }}">{{ __('Vérifier une attestation') }}</a>
            </div>
            <div>
                <h4>{{ __('Contact DRI') }}</h4>
                <span style="display:block;padding:4px 0;">{{ __('Campus Sud, Présidence') }}</span>
                <span style="display:block;padding:4px 0;">BP 404 Kara, Togo</span>
                <a href="mailto:dri@univkara.tg">dri@univkara.tg</a>
            </div>
        </div>
        <div class="footer-bottom">
            <div>© {{ now()->year }} {{ __('Université de Kara - Direction de la Recherche et de l’Innovation') }}</div>
            <div>Kara, Togo</div>
        </div>
    </div>
</footer>
