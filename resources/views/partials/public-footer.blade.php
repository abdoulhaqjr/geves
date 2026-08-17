{{-- Pied de page public partagé. --}}
<footer class="site-footer">
    <div class="ukc-container">
        <div class="footer-grid">
            <div>
                <div class="brand-foot">UK Colloques</div>
                Service de l’Université de Kara — Direction de la Recherche et de l’Innovation (DRI). Plateforme non commerciale.
            </div>
            <div>
                <h4>Navigation</h4>
                <a href="{{ route('home') }}">Accueil</a>
                <a href="{{ route('home') }}#en-cours">Colloque en cours</a>
                <a href="{{ route('home') }}#a-venir">À venir</a>
                <a href="{{ route('public.archives') }}">Colloques passés</a>
                <a href="{{ route('home') }}#apropos">À propos</a>
                <a href="{{ route('home') }}#contact">Contact</a>
            </div>
            <div>
                <h4>Liens utiles</h4>
                <a href="{{ route('account.login') }}">Se connecter</a>
                <a href="{{ route('account.register') }}">Créer un compte</a>
                <a href="{{ route('account.dashboard') }}">Espace participant</a>
                <a href="mailto:dri@univkara.tg">dri@univkara.tg</a>
            </div>
            <div>
                <h4>Contact DRI</h4>
                <span style="display:block;padding:4px 0;">Campus Sud, Présidence</span>
                <span style="display:block;padding:4px 0;">BP 404 Kara, Togo</span>
                <a href="mailto:colloque@univkara.tg">colloque@univkara.tg</a>
            </div>
        </div>
        <div class="footer-bottom">
            <div>© {{ now()->year }} Université de Kara · Direction de la Recherche et de l’Innovation</div>
            <div>Kara, Togo</div>
        </div>
    </div>
</footer>
