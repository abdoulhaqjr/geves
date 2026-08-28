@php($staff = auth('web')->user())
@props(['title' => 'Back-office', 'subtitle' => null])
<x-layouts.app :title="($title ?? 'Back-office') . ' — Colloques UK'">
    <div class="wp-bar">
        <strong>UK Colloques</strong>
        <span>Back-office</span>
        <span class="right">Admin · Université de Kara</span>
    </div>

    <div class="ukc-shell ukc-shell--admin" x-data="{ open: false }">
        <aside class="ukc-sidebar" :class="{ 'open': open }">
            <div class="ukc-sidebar-brand">
                <a href="{{ route('admin.dashboard') }}" class="logo-img" aria-label="Université de Kara — back-office">
                    <img src="{{ asset('img/logo-uk.png') }}" alt="Université de Kara">
                </a>
                <div class="logo-sub">Back-office · Colloques</div>
            </div>
            <nav class="ukc-sidebar-nav">
                @can('view_dashboard')
                    <a href="{{ route('admin.dashboard') }}"
                       @class(['active' => request()->routeIs('admin.dashboard')])>Tableau de bord</a>
                @endcan
                @can('editions_read')
                    <a href="{{ route('admin.editions.index') }}"
                       @class(['active' => request()->routeIs('admin.editions.*')])>Éditions</a>
                @endcan
                @can('submissions_list')
                    <a href="{{ route('admin.submissions.index') }}"
                       @class(['active' => request()->routeIs('admin.submissions.*')])>Soumissions</a>
                @endcan
                @can('users_roles')
                    <a href="{{ route('admin.users.index') }}"
                       @class(['active' => request()->routeIs('admin.users.*')])>Utilisateurs</a>
                @endcan
                @can('participants')
                    <a href="{{ route('admin.participants.index') }}"
                       @class(['active' => request()->routeIs('admin.participants.*')])>Participants</a>
                @endcan
                @can('payments')
                    <a href="{{ route('admin.payments.index') }}"
                       @class(['active' => request()->routeIs('admin.payments.*')])>Paiements</a>
                @endcan
                @can('documents')
                    <a href="{{ route('admin.documents.attestations') }}"
                       @class(['active' => request()->routeIs('admin.documents.*') || request()->routeIs('admin.badges.*')])>Documents</a>
                @endcan
                @can('emails')
                    <a href="{{ route('admin.emails.index') }}"
                       @class(['active' => request()->routeIs('admin.emails.*')])>E-mails</a>
                @endcan
                @can('editions_read')
                    <a href="{{ route('admin.program.slots') }}"
                       @class(['active' => request()->routeIs('admin.program.*')])>Créneaux</a>
                @endcan
                @can('view_dashboard')
                    <a href="{{ route('admin.reports.index') }}"
                       @class(['active' => request()->routeIs('admin.reports.*')])>Rapports</a>
                    <a href="{{ route('admin.ai') }}"
                       @class(['active' => request()->routeIs('admin.ai')])>Assistant IA</a>
                @endcan
                @can('settings')
                    <a href="{{ route('admin.settings') }}"
                       @class(['active' => request()->routeIs('admin.settings') || request()->routeIs('admin.settings.*')])>Réglages</a>
                @endcan
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
            <header class="ukc-admin-head">
                <div class="head-title">
                    <button type="button" class="ukc-burger" @click="open = !open" aria-label="Menu">
                        <x-ukc.icon name="menu" :size="24" />
                    </button>
                    <div>
                        <h1>{{ $title ?? 'Back-office' }}</h1>
                        @if ($subtitle)<div class="sub">{{ $subtitle }}</div>@endif
                    </div>
                </div>
                <div class="head-actions">
                    {{ $actions ?? '' }}
                    <x-ukc.notification-bell />
                </div>
            </header>

            <main class="ukc-admin-body">
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
