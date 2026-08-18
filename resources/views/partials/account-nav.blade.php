{{-- Barre d'onglets « Mon compte » (espace participant) : Préférences / Notifications / Sécurité.
     Unifie les pages profil, notifications et sécurité (incl. 2FA) façon compte.html du mockup. --}}
<div class="account-head">
    <h1>Mon compte</h1>
    <p>Vos informations, préférences de notification et sécurité (mot de passe, double authentification).</p>
</div>
<nav class="account-nav">
    <a href="{{ route('account.profile') }}" wire:navigate @class(['active' => request()->routeIs('account.profile')])>Préférences</a>
    <a href="{{ route('account.notifications') }}" wire:navigate @class(['active' => request()->routeIs('account.notifications')])>Notifications</a>
    <a href="{{ route('account.security') }}" wire:navigate @class(['active' => request()->routeIs('account.security')])>Sécurité</a>
</nav>
