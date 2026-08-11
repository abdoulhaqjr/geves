@php($account = auth('account')->user())
<x-layouts.app :title="$title ?? 'Mon espace'">
    <div class="ukc-shell" x-data="{ open: false }">
        <aside class="ukc-sidebar" :class="{ 'open': open }">
            <div class="ukc-sidebar-brand">
                <div class="logo">Espace participant</div>
                <div class="sub">Colloques · Université de Kara</div>
            </div>
            <nav class="ukc-sidebar-nav">
                <a href="{{ route('account.dashboard') }}" wire:navigate
                   @class(['active' => request()->routeIs('account.dashboard')])>
                    <x-ukc.icon name="home" :size="18" /> Tableau de bord
                </a>
                <a href="{{ route('account.submissions.index') }}" wire:navigate
                   @class(['active' => request()->routeIs('account.submissions.index')])>
                    <x-ukc.icon name="inbox" :size="18" /> Mes communications
                </a>
                <a href="{{ route('account.documents') }}" wire:navigate
                   @class(['active' => request()->routeIs('account.documents')])>
                    <x-ukc.icon name="folder" :size="18" /> Mes documents
                </a>
                <a href="{{ route('account.profile') }}" wire:navigate
                   @class(['active' => request()->routeIs('account.profile')])>
                    <x-ukc.icon name="user" :size="18" /> Mon profil
                </a>
                <a href="{{ route('account.security') }}" wire:navigate
                   @class(['active' => request()->routeIs('account.security')])>
                    <x-ukc.icon name="lock" :size="18" /> Sécurité
                </a>
            </nav>
            <div class="ukc-sidebar-foot">
                <div class="ukc-user-chip">
                    <div class="av">
                        @if ($account?->photo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($account->photo_path) }}" alt="">
                        @else
                            {{ $account?->initials }}
                        @endif
                    </div>
                    <div class="meta">
                        <div class="name">{{ $account?->full_name }}</div>
                        <div class="role">{{ $account?->grade ?: 'Participant' }}</div>
                    </div>
                    <form method="POST" action="{{ route('account.logout') }}">
                        @csrf
                        <button type="submit" class="logout" aria-label="Se déconnecter">
                            <x-ukc.icon name="logout" :size="18" />
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="ukc-overlay" :class="{ 'show': open }" @click="open = false"></div>

        <div class="ukc-main">
            <header class="ukc-topbar">
                <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                    <button type="button" class="ukc-burger" @click="open = !open" aria-label="Menu">
                        <x-ukc.icon name="menu" :size="24" />
                    </button>
                    <h1>{{ $title ?? 'Mon espace' }}</h1>
                </div>
            </header>

            <main class="ukc-content">
                @if (app('impersonate')->isImpersonating())
                    <div style="background:#fef3c7;border:1px solid #f59e0b;color:#92400e;border-radius:12px;
                                padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;
                                justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <span style="display:inline-flex;align-items:center;gap:8px;font-weight:600;">
                            <x-ukc.icon name="eye" :size="16" />
                            Impersonation en cours — vous agissez en tant que {{ $account?->full_name }}.
                        </span>
                        <form method="POST" action="{{ route('impersonation.leave') }}">
                            @csrf
                            <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                <x-ukc.icon name="logout" :size="14" /> Quitter l’impersonation
                            </button>
                        </form>
                    </div>
                @endif
                @if (session('ukc_status'))
                    <div class="ukc-alert ukc-alert--ok">
                        <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
                        <div>{{ session('ukc_status') }}</div>
                    </div>
                @endif
                @if (session('ukc_error'))
                    <div class="ukc-alert ukc-alert--err">
                        <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                        <div>{{ session('ukc_error') }}</div>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.app>
