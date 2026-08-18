@php($staff = auth('web')->user())
<x-layouts.app :title="($title ?? 'Back-office') . ' — Colloques UK'">
    <div class="ukc-shell ukc-shell--admin" x-data="{ open: false }">
        <aside class="ukc-sidebar" :class="{ 'open': open }">
            <div class="ukc-sidebar-brand">
                <div class="logo">Back-office</div>
                <div class="sub">Colloques · Université de Kara</div>
            </div>
            <nav class="ukc-sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.dashboard')])>
                    <x-ukc.icon name="home" :size="18" /> Tableau de bord
                </a>

                <span class="nav-label">Événements</span>
                <a href="{{ route('admin.editions.index') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.editions.*')])>
                    <x-ukc.icon name="calendar" :size="18" /> Éditions
                </a>

                <a href="{{ route('admin.submissions.index') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.submissions.*')])>
                    <x-ukc.icon name="inbox" :size="18" /> Soumissions
                </a>
                <a href="{{ route('admin.payments.index') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.payments.*')])>
                    <x-ukc.icon name="card" :size="18" /> Paiements
                </a>
                <a href="{{ route('admin.participants.index') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.participants.*')])>
                    <x-ukc.icon name="users" :size="18" /> Participants
                </a>
                <a href="{{ route('admin.reports.index') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.reports.*')])>
                    <x-ukc.icon name="chart" :size="18" /> Rapports
                </a>

                <span class="nav-label">Communication</span>
                <a href="{{ route('admin.emails.index') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.emails.index')])>
                    <x-ukc.icon name="mail" :size="18" /> E-mails
                </a>
                <a href="{{ route('admin.emails.templates') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.emails.templates')])>
                    <x-ukc.icon name="pen" :size="18" /> Modèles d’e-mails
                </a>

                <span class="nav-label">Programme</span>
                <a href="{{ route('admin.program.sessions') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.program.sessions')])>
                    <x-ukc.icon name="layers" :size="18" /> Ateliers & sessions
                </a>
                <a href="{{ route('admin.program.slots') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.program.slots')])>
                    <x-ukc.icon name="calendar" :size="18" /> Créneaux
                </a>

                <span class="nav-label">Documents</span>
                <a href="{{ route('admin.documents.attestations') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.documents.attestations')])>
                    <x-ukc.icon name="award" :size="18" /> Documents
                </a>
                <a href="{{ route('admin.documents.templates') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.documents.templates')])>
                    <x-ukc.icon name="palette" :size="18" /> Modèles (badges/attest.)
                </a>
                <a href="{{ route('admin.badges.scan') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.badges.scan')])>
                    <x-ukc.icon name="video" :size="18" /> Pointage badges
                </a>

                <span class="nav-label">Configuration</span>
                <a href="{{ route('admin.settings') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.settings')])>
                    <x-ukc.icon name="settings" :size="18" /> Réglages
                </a>
                <a href="{{ route('admin.ai') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.ai')])>
                    <x-ukc.icon name="sparkles" :size="18" /> Assistant IA
                </a>
                <a href="{{ route('admin.settings.agents') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.settings.agents')])>
                    <x-ukc.icon name="sparkles" :size="18" /> Agents IA
                </a>
            </nav>
            <div class="ukc-sidebar-foot">
                <div class="ukc-user-chip">
                    <div class="av">{{ strtoupper(mb_substr($staff?->name ?? 'A', 0, 1)) }}</div>
                    <div class="meta">
                        <div class="name">{{ $staff?->name }}</div>
                        <div class="role">Staff</div>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
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
                    <h1>{{ $title ?? 'Back-office' }}</h1>
                </div>
                <div class="actions">
                    @isset($actions){{ $actions }}@endisset
                    <livewire:notifications.bell />
                </div>
            </header>

            <main class="ukc-content">
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
