{{-- Barre d'onglets « Mon compte » (espace participant) : Préférences / Notifications / Sécurité.
     Unifie les pages profil, notifications et sécurité (incl. 2FA) façon compte.html du mockup. --}}
@php
    $navAccount = auth('account')->user();
    $navTwoFaOn = $navAccount && ($navAccount->hasTotpEnabled() || $navAccount->hasSmsEnabled());
    $navTwoFaMethod = $navAccount?->hasSmsEnabled() ? 'OTP SMS' : 'TOTP';
@endphp
<div class="account-head" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Mon compte</h1>
    @if ($navTwoFaOn)
        <span class="ukc-pill ukc-pill--ok account-badge">2FA activée · {{ $navTwoFaMethod }}</span>
    @else
        <span class="ukc-pill ukc-pill--muted account-badge">2FA désactivée</span>
    @endif
</div>
@php($isTwoFa = request()->routeIs('account.security') && request('tab') === '2fa')
<nav class="account-nav">
    <a href="{{ route('account.preferences') }}" @class(['active' => request()->routeIs('account.preferences')])>Préférences</a>
    <a href="{{ route('account.notifications') }}" @class(['active' => request()->routeIs('account.notifications')])>Notifications</a>
    <a href="{{ route('account.security') }}" @class(['active' => request()->routeIs('account.security') && ! $isTwoFa])>Sécurité</a>
    <a href="{{ route('account.security', ['tab' => '2fa']) }}" @class(['active' => $isTwoFa])>Double authentification</a>
</nav>
