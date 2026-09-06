@php($staff = auth('web')->user())
@php($navEdition = \App\Models\Edition::where('status', 'open')->latest('id')->first() ?? \App\Models\Edition::latest('id')->first())
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
                <a href="{{ route('admin.dashboard') }}"
                   @class(['active' => request()->routeIs('admin.dashboard')])>Tableau de bord</a>
                @if ($staff?->canAnyEdition('editions_read'))
                    <a href="{{ route('admin.editions.index') }}"
                       @class(['active' => request()->routeIs('admin.editions.*')])>Éditions</a>
                @endif
                @if ($staff?->canAnyEdition('submissions_list'))
                    <a href="{{ route('admin.submissions.index') }}"
                       @class(['active' => request()->routeIs('admin.submissions.*') || request()->routeIs('admin.editions.form')])>Soumissions</a>
                    @if (request()->routeIs('admin.submissions.*') || request()->routeIs('admin.editions.form'))
                        <a href="{{ route('admin.submissions.index') }}" @class(['sub', 'active' => request()->routeIs('admin.submissions.*')])>— Liste</a>
                        <a href="{{ $navEdition ? route('admin.editions.form', $navEdition) : route('admin.editions.index') }}" @class(['sub', 'active' => request()->routeIs('admin.editions.form')])>— Formulaires</a>
                    @endif
                @endif
                @can('users_roles')
                    <a href="{{ route('admin.users.index') }}"
                       @class(['active' => request()->routeIs('admin.users.*')])>Utilisateurs</a>
                    @if (request()->routeIs('admin.users.*'))
                        @php($uTab = request('tab', 'list'))
                        <a href="{{ route('admin.users.index') }}" @class(['sub', 'active' => $uTab === 'list'])>— Liste</a>
                        <a href="{{ route('admin.users.index', ['tab' => 'roles']) }}" @class(['sub', 'active' => $uTab === 'roles'])>— Rôles</a>
                        <a href="{{ route('admin.users.index', ['tab' => 'access']) }}" @class(['sub', 'active' => $uTab === 'access'])>— Accès</a>
                    @endif
                @endcan
                @if ($staff?->canAnyEdition('participants'))
                    <a href="{{ route('admin.participants.index') }}"
                       @class(['active' => request()->routeIs('admin.participants.*')])>Participants</a>
                @endif
                @if ($staff?->canAnyEdition('payments'))
                    <a href="{{ route('admin.payments.index') }}"
                       @class(['active' => request()->routeIs('admin.payments.*')])>Paiements</a>
                @endif
                @if ($staff?->canAnyEdition('documents'))
                    <a href="{{ route('admin.documents.attestations') }}"
                       @class(['active' => request()->routeIs('admin.documents.*') || request()->routeIs('admin.badges.*')])>Documents</a>
                    @if (request()->routeIs('admin.documents.attestations'))
                        @php($dTab = request('tab', 'attestations'))
                        <a href="{{ route('admin.documents.attestations') }}" @class(['sub', 'active' => $dTab === 'attestations'])>— Attestations</a>
                        <a href="{{ route('admin.documents.attestations', ['tab' => 'badges']) }}" @class(['sub', 'active' => $dTab === 'badges'])>— Badges</a>
                        <a href="{{ route('admin.documents.attestations', ['tab' => 'livre']) }}" @class(['sub', 'active' => $dTab === 'livre'])>— Livre des résumés</a>
                    @endif
                    <a href="{{ route('admin.attestations.index') }}"
                       @class(['active' => request()->routeIs('admin.attestations.*')])>Attestations délivrées</a>
                @endif
                @if ($staff?->canAnyEdition('emails'))
                    <a href="{{ route('admin.emails.index') }}"
                       @class(['active' => request()->routeIs('admin.emails.*')])>E-mails</a>
                @endif
                @if ($staff?->canAnyEdition('editions_read'))
                    <a href="{{ route('admin.program.slots') }}"
                       @class(['active' => request()->routeIs('admin.program.*')])>Créneaux</a>
                @endif
                @if ($staff?->canAnyEdition('view_dashboard'))
                    <a href="{{ route('admin.reports.index') }}"
                       @class(['active' => request()->routeIs('admin.reports.*')])>Rapports</a>
                    <a href="{{ route('admin.ai') }}"
                       @class(['active' => request()->routeIs('admin.ai')])>Assistant IA</a>
                @endif
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
                {{-- Les messages flash (ukc_status/ukc_error) s'affichent en toasts (layout app). --}}
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.app>
