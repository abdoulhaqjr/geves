@php($staff = auth('web')->user())
@php($navEdition = \App\Models\Edition::where('status', 'open')->latest('id')->first() ?? \App\Models\Edition::latest('id')->first())
@php($paletteItems = \App\Support\AdminPalette::items($staff))
@props(['title' => 'Back-office', 'subtitle' => null])
<x-layouts.app :title="($title ?? 'Back-office') . ' - '.config('app.name')" html-class="ukc-dense">
    <div class="wp-bar">
        <strong>{{ config('app.name') }}</strong>
        <span>Back-office</span>
        <span class="right">Admin · Université de Kara</span>
    </div>

    <div class="ukc-shell ukc-shell--admin"
         x-data="{ open: false, compact: localStorage.getItem('ukcCompact')==='1', toggleCompact(){ this.compact=!this.compact; localStorage.setItem('ukcCompact', this.compact?'1':'0'); } }"
         :class="{ 'ukc-compact': compact }">
        <aside class="ukc-sidebar" :class="{ 'open': open }">
            <div class="ukc-sidebar-brand">
                <div class="brand-lockup">
                    <a href="{{ route('admin.dashboard') }}" class="logo-img" aria-label="{{ config('app.name') }} - back-office">
                        <img src="{{ \App\Support\Branding::mark() }}" alt="{{ config('app.name') }}">
                    </a>
                    <span class="brand-rule" aria-hidden="true"></span>
                    <div class="logo-sub"><strong>{{ config('app.name') }}</strong><span>Back-office · Colloques</span></div>
                </div>
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
                    {{-- ⚠️ L'éditeur de formulaire est un onglet du module Soumissions
                         bien que sa route vive sous `admin.editions.*` : sans cette
                         exclusion, les deux entrées du menu s'allument ensemble. --}}
                    <a href="{{ route('admin.editions.index') }}" title="Éditions"
                       @class(['active' => request()->routeIs('admin.editions.*') && ! request()->routeIs('admin.editions.form*')])>
                        <x-ukc.icon name="calendar" :size="18" /> <span class="txt">Éditions</span>
                    </a>
                @endif
                @if ($staff?->canAnyEdition('submissions_list'))
                    <a href="{{ route('admin.submissions.index') }}" title="Soumissions"
                       @class(['active' => request()->routeIs('admin.submissions.*') || request()->routeIs('admin.editions.form')])>
                        <x-ukc.icon name="inbox" :size="18" /> <span class="txt">Soumissions</span>
                    </a>
                @endif
                @if ($staff?->canAnyEdition('participants'))
                    <a href="{{ route('admin.participants.index') }}" title="Participants"
                       @class(['active' => request()->routeIs('admin.participants.*')])>
                        <x-ukc.icon name="users" :size="18" /> <span class="txt">Participants</span>
                    </a>
                @endif
                @if ($staff?->canAnyEdition('editions_read'))
                    <a href="{{ route('admin.program.index') }}" title="Programme"
                       @class(['active' => request()->routeIs('admin.program.*')])>
                        <x-ukc.icon name="calendar" :size="18" /> <span class="txt">Programme</span>
                    </a>
                    <a href="{{ route('admin.secretariat') }}" title="Secrétariat"
                       @class(['active' => request()->routeIs('admin.secretariat')])>
                        <x-ukc.icon name="inbox" :size="18" /> <span class="txt">Secrétariat</span>
                    </a>
                @endif

                @if ($staff?->canAnyEdition('announcements'))
                    <a href="{{ route('admin.announcements') }}" title="Annonces du déroulement"
                       @class(['active' => request()->routeIs('admin.announcements')])>
                        <x-ukc.icon name="bell" :size="18" /> <span class="txt">Annonces</span>
                    </a>
                @endif

                @if ($staff?->canAnyEdition('formation'))
                    <a href="{{ route('admin.formations') }}" title="Formations"
                       @class(['active' => request()->routeIs('admin.formations')])>
                        <x-ukc.icon name="book" :size="18" /> <span class="txt">Formations</span>
                    </a>
                @endif

                @if ($staff?->canAnyEdition('project') || $staff?->canAnyEdition('payments') || $staff?->canAnyEdition('documents') || $staff?->canAnyEdition('emails'))
                    <span class="nav-label">Gestion</span>
                @endif
                @if ($staff?->canAnyEdition('project'))
                    <a href="{{ route('admin.project.index') }}" title="Projet"
                       @class(['active' => request()->routeIs('admin.project.*')])>
                        <x-ukc.icon name="layers" :size="18" /> <span class="txt">Projet</span>
                    </a>
                @endif
                @if ($staff?->canAnyEdition('payments'))
                    <a href="{{ route('admin.payments.index') }}" title="Paiements"
                       @class(['active' => request()->routeIs('admin.payments.*')])>
                        <x-ukc.icon name="card" :size="18" /> <span class="txt">Paiements</span>
                    </a>
                @endif
                @if ($staff?->canAnyEdition('documents'))
                    <a href="{{ route('admin.documents.attestations') }}" title="Documents"
                       @class(['active' => request()->routeIs('admin.documents.*')])>
                        <x-ukc.icon name="folder" :size="18" /> <span class="txt">Documents</span>
                    </a>
                    {{-- « Attestations délivrées » est un sous-onglet de Documents. --}}
                @endif
                @if ($staff?->canAnyEdition('emails'))
                    <a href="{{ route('admin.emails.index') }}" title="E-mails"
                       @class(['active' => request()->routeIs('admin.emails.*')])>
                        <x-ukc.icon name="mail" :size="18" /> <span class="txt">E-mails</span>
                    </a>
                @endif
                @if ($staff?->canAnyEdition('claims'))
                    <a href="{{ route('admin.claims.index') }}" title="Réclamations & incidents"
                       @class(['active' => request()->routeIs('admin.claims.*')])>
                        <x-ukc.icon name="inbox" :size="18" /> <span class="txt">Réclamations</span>
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

                @if (auth('web')->user()?->can('users_roles') || auth('web')->user()?->can('settings') || auth('web')->user()?->can('testimonials'))
                    <span class="nav-label">Système</span>
                @endif
                @can('testimonials')
                    <a href="{{ route('admin.testimonials.index') }}" title="Témoignages (accueil)"
                       @class(['active' => request()->routeIs('admin.testimonials.*')])>
                        <x-ukc.icon name="sparkles" :size="18" /> <span class="txt">Témoignages</span>
                    </a>
                @endcan
                @can('users_roles')
                    <a href="{{ route('admin.users.index') }}" title="Utilisateurs"
                       @class(['active' => request()->routeIs('admin.users.*')])>
                        <x-ukc.icon name="shield" :size="18" /> <span class="txt">Utilisateurs</span>
                    </a>
                @endcan
                @can('settings')
                    <a href="{{ route('admin.settings') }}" title="Réglages"
                       @class(['active' => request()->routeIs('admin.settings') || request()->routeIs('admin.settings.*')])>
                        <x-ukc.icon name="settings" :size="18" /> <span class="txt">Réglages</span>
                    </a>
                @endcan
            </nav>
            <div class="ukc-sidebar-foot">
                @php($staffPhoto = $staff?->photo_path ? \Illuminate\Support\Facades\Storage::url($staff->photo_path) : null)
                <div class="ukc-user-chip">
                    <a href="{{ route('admin.profile') }}" class="av" title="Mon compte" style="text-decoration:none;">
                        @if ($staffPhoto)<img src="{{ $staffPhoto }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">@else{{ strtoupper(mb_substr($staff?->name ?? 'A', 0, 1)) }}@endif
                    </a>
                    <a href="{{ route('admin.profile') }}" class="meta" style="text-decoration:none;color:inherit;">
                        <div class="name">{{ $staff?->name }}</div>
                        <div class="role">Mon compte</div>
                    </a>
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
                    <x-ukc.command-palette :items="$paletteItems" :search-base="route('admin.submissions.index').'?search='" />
                    {{ $actions ?? '' }}
                    <x-ukc.notification-bell />
                    <div class="ukc-usermenu" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" class="ukc-usermenu__btn" @click="open = !open" :aria-expanded="open" aria-label="Menu du compte">
                            <span class="av">@if ($staffPhoto)<img src="{{ $staffPhoto }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">@else{{ strtoupper(mb_substr($staff?->name ?? 'A', 0, 1)) }}@endif</span>
                            <x-ukc.icon name="chevron" :size="14" />
                        </button>
                        <div class="ukc-usermenu__pop" x-show="open" x-cloak x-transition.origin.top.right style="display:none;">
                            <div class="ukc-usermenu__head">
                                <div class="name">{{ $staff?->name }}</div>
                                <div class="mail">{{ $staff?->email }}</div>
                            </div>
                            <a href="{{ route('admin.profile') }}" class="ukc-usermenu__item"><x-ukc.icon name="user" :size="16" /> Mon compte</a>
                            <form method="POST" action="{{ route('admin.space.participant') }}">
                                @csrf
                                <button type="submit" class="ukc-usermenu__item"><x-ukc.icon name="external" :size="16" /> Mon espace participant</button>
                            </form>
                            @can('settings')
                                <a href="{{ route('admin.settings') }}" class="ukc-usermenu__item"><x-ukc.icon name="settings" :size="16" /> Réglages</a>
                            @endcan
                            <a href="{{ route('admin.notifications') }}" class="ukc-usermenu__item"><x-ukc.icon name="bell" :size="16" /> Notifications</a>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="ukc-usermenu__item ukc-usermenu__item--danger"><x-ukc.icon name="logout" :size="16" /> Se déconnecter</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="ukc-admin-body">
                {{-- Les messages flash (ukc_status/ukc_error) s'affichent en toasts (layout app). --}}
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.app>
