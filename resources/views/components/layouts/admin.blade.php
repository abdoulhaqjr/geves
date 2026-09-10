@php($staff = auth('web')->user())
@php($navEdition = \App\Models\Edition::where('status', 'open')->latest('id')->first() ?? \App\Models\Edition::latest('id')->first())
@props(['title' => 'Back-office', 'subtitle' => null])
<x-layouts.app :title="($title ?? 'Back-office') . ' — Colloques UK'">
    <div class="wp-bar">
        <strong>UK Colloques</strong>
        <span>Back-office</span>
        <span class="right">Admin · Université de Kara</span>
    </div>

    <div class="ukc-shell ukc-shell--admin"
         x-data="{ open: false, compact: localStorage.getItem('ukcCompact')==='1', toggleCompact(){ this.compact=!this.compact; localStorage.setItem('ukcCompact', this.compact?'1':'0'); } }"
         :class="{ 'ukc-compact': compact }">
        <aside class="ukc-sidebar" :class="{ 'open': open }">
            <div class="ukc-sidebar-brand">
                <a href="{{ route('admin.dashboard') }}" class="logo-img" aria-label="Université de Kara — back-office">
                    <img src="{{ asset('img/logo-uk.png') }}" alt="Université de Kara">
                </a>
                <div class="logo-sub">Back-office · Colloques</div>
                <button type="button" class="ukc-compact-toggle" @click="toggleCompact()"
                        :aria-pressed="compact" aria-label="Réduire ou déployer le menu" title="Réduire / déployer le menu">
                    <x-ukc.icon name="chevron" :size="16" />
                </button>
            </div>
            <nav class="ukc-sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" title="Tableau de bord"
                   @class(['active' => request()->routeIs('admin.dashboard')])>
                    <x-ukc.icon name="home" :size="18" /> <span class="txt">Tableau de bord</span>
                </a>

                @if ($staff?->canAnyEdition('editions_read') || $staff?->canAnyEdition('submissions_list') || $staff?->canAnyEdition('participants'))
                    <span class="nav-label">Scientifique</span>
                @endif
                @if ($staff?->canAnyEdition('editions_read'))
                    <a href="{{ route('admin.editions.index') }}" title="Éditions"
                       @class(['active' => request()->routeIs('admin.editions.*')])>
                        <x-ukc.icon name="calendar" :size="18" /> <span class="txt">Éditions</span>
                    </a>
                @endif
                @if ($staff?->canAnyEdition('submissions_list'))
                    <a href="{{ route('admin.submissions.index') }}" title="Soumissions"
                       @class(['active' => request()->routeIs('admin.submissions.*') || request()->routeIs('admin.editions.form')])>
                        <x-ukc.icon name="inbox" :size="18" /> <span class="txt">Soumissions</span>
                    </a>
                    @if (request()->routeIs('admin.submissions.*') || request()->routeIs('admin.editions.form'))
                        <a href="{{ route('admin.submissions.index') }}" @class(['sub', 'active' => request()->routeIs('admin.submissions.index') || request()->routeIs('admin.submissions.show')])><span class="txt">Liste</span></a>
                        <a href="{{ $navEdition ? route('admin.editions.form', $navEdition) : route('admin.editions.index') }}" @class(['sub', 'active' => request()->routeIs('admin.editions.form')])><span class="txt">Formulaires</span></a>
                        <a href="{{ route('admin.submissions.auto-assign') }}" @class(['sub', 'active' => request()->routeIs('admin.submissions.auto-assign')])><span class="txt">Attribution auto</span></a>
                    @endif
                @endif
                @if ($staff?->canAnyEdition('participants'))
                    <a href="{{ route('admin.participants.index') }}" title="Participants"
                       @class(['active' => request()->routeIs('admin.participants.*')])>
                        <x-ukc.icon name="users" :size="18" /> <span class="txt">Participants</span>
                    </a>
                @endif
                @if ($staff?->canAnyEdition('editions_read'))
                    <a href="{{ route('admin.program.slots') }}" title="Créneaux"
                       @class(['active' => request()->routeIs('admin.program.*')])>
                        <x-ukc.icon name="clock" :size="18" /> <span class="txt">Créneaux</span>
                    </a>
                @endif

                @if ($staff?->canAnyEdition('payments') || $staff?->canAnyEdition('documents') || $staff?->canAnyEdition('emails'))
                    <span class="nav-label">Gestion</span>
                @endif
                @if ($staff?->canAnyEdition('payments'))
                    <a href="{{ route('admin.payments.index') }}" title="Paiements"
                       @class(['active' => request()->routeIs('admin.payments.*')])>
                        <x-ukc.icon name="card" :size="18" /> <span class="txt">Paiements</span>
                    </a>
                @endif
                @if ($staff?->canAnyEdition('documents'))
                    <a href="{{ route('admin.documents.attestations') }}" title="Documents"
                       @class(['active' => request()->routeIs('admin.documents.*') || request()->routeIs('admin.badges.*')])>
                        <x-ukc.icon name="folder" :size="18" /> <span class="txt">Documents</span>
                    </a>
                    @if (request()->routeIs('admin.documents.attestations'))
                        @php($dTab = request('tab', 'attestations'))
                        <a href="{{ route('admin.documents.attestations') }}" @class(['sub', 'active' => $dTab === 'attestations'])><span class="txt">Attestations</span></a>
                        <a href="{{ route('admin.documents.attestations', ['tab' => 'badges']) }}" @class(['sub', 'active' => $dTab === 'badges'])><span class="txt">Badges</span></a>
                        <a href="{{ route('admin.documents.attestations', ['tab' => 'livre']) }}" @class(['sub', 'active' => $dTab === 'livre'])><span class="txt">Livre des résumés</span></a>
                    @endif
                    <a href="{{ route('admin.attestations.index') }}" title="Attestations délivrées"
                       @class(['active' => request()->routeIs('admin.attestations.*')])>
                        <x-ukc.icon name="diploma" :size="18" /> <span class="txt">Attestations délivrées</span>
                    </a>
                @endif
                @if ($staff?->canAnyEdition('emails'))
                    <a href="{{ route('admin.emails.index') }}" title="E-mails"
                       @class(['active' => request()->routeIs('admin.emails.*')])>
                        <x-ukc.icon name="mail" :size="18" /> <span class="txt">E-mails</span>
                    </a>
                @endif

                @if ($staff?->canAnyEdition('view_dashboard'))
                    <span class="nav-label">Pilotage</span>
                    <a href="{{ route('admin.reports.index') }}" title="Rapports"
                       @class(['active' => request()->routeIs('admin.reports.*')])>
                        <x-ukc.icon name="chart" :size="18" /> <span class="txt">Rapports</span>
                    </a>
                    <a href="{{ route('admin.ai') }}" title="Assistant IA"
                       @class(['active' => request()->routeIs('admin.ai')])>
                        <x-ukc.icon name="sparkles" :size="18" /> <span class="txt">Assistant IA</span>
                    </a>
                @endif

                @if (auth('web')->user()?->can('users_roles') || auth('web')->user()?->can('settings'))
                    <span class="nav-label">Système</span>
                @endif
                @can('users_roles')
                    <a href="{{ route('admin.users.index') }}" title="Utilisateurs"
                       @class(['active' => request()->routeIs('admin.users.*')])>
                        <x-ukc.icon name="shield" :size="18" /> <span class="txt">Utilisateurs</span>
                    </a>
                    @if (request()->routeIs('admin.users.*'))
                        @php($uTab = request('tab', 'list'))
                        <a href="{{ route('admin.users.index') }}" @class(['sub', 'active' => $uTab === 'list'])><span class="txt">Liste</span></a>
                        <a href="{{ route('admin.users.index', ['tab' => 'roles']) }}" @class(['sub', 'active' => $uTab === 'roles'])><span class="txt">Rôles</span></a>
                        <a href="{{ route('admin.users.index', ['tab' => 'access']) }}" @class(['sub', 'active' => $uTab === 'access'])><span class="txt">Accès</span></a>
                    @endif
                @endcan
                @can('settings')
                    <a href="{{ route('admin.settings') }}" title="Réglages"
                       @class(['active' => request()->routeIs('admin.settings') || request()->routeIs('admin.settings.*')])>
                        <x-ukc.icon name="settings" :size="18" /> <span class="txt">Réglages</span>
                    </a>
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
