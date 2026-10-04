@php
    $account = auth('account')->user();

    // Colloque courant (sélection en session, sinon défaut) + liste des colloques
    // du compte pour le sélecteur. Toutes les pages personnelles s'y scopent.
    $myColloques = $account ? \App\Support\CurrentColloque::editions($account) : collect();
    // Appels à communications ouverts (colloques en cours / à venir) auxquels le
    // compte ne participe pas encore : sélectionnables pour soumettre.
    $openCalls = $account ? \App\Support\CurrentColloque::openCalls($account) : collect();
    // Édition réellement sélectionnée (null si le compte n'a rien de sélectionnable).
    $currentEdition = $account ? \App\Support\CurrentColloque::current($account) : null;

    // Repli pour les liens publics si le compte ne participe encore à rien.
    $colloqueEdition = $currentEdition
        ?? \App\Models\Edition::where('status', 'open')->latest('id')->first();

    // Paiement : l'édition courante si elle est à régler, sinon la première à régler.
    $payEditionId = ($colloqueEdition
        && \App\Models\Participant::where('user_id', $account?->id)
            ->where('edition_id', $colloqueEdition->id)
            ->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted'))
            ->whereDoesntHave('participationPayments', fn ($q) => $q->where('status', 'paid'))
            ->exists())
        ? $colloqueEdition->id
        : \App\Models\Participant::where('user_id', $account?->id)
            ->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted'))
            ->whereDoesntHave('participationPayments', fn ($q) => $q->where('status', 'paid'))
            ->latest('id')->value('edition_id');

    // Lien « Formations » : uniquement si l'ÉDITION SÉLECTIONNÉE propose des
    // formations (module activé pour elle + au moins une formation publiée).
    // Même règle que la garde de la page (404 sinon).
    $hasFormations = \App\Models\Formation::offeredIn($currentEdition);
@endphp
<x-layouts.app :title="$title ?? 'Mon espace'" html-class="ukc-dense">
    <div class="ukc-shell"
         x-data="{ open: false, compact: localStorage.getItem('ukcCompact')==='1', toggleCompact(){ this.compact=!this.compact; localStorage.setItem('ukcCompact', this.compact?'1':'0'); } }"
         :class="{ 'ukc-compact': compact }">
        <aside class="ukc-sidebar" :class="{ 'open': open }">
            <div class="ukc-sidebar-brand">
                <div class="brand-lockup">
                    <a href="{{ route('account.dashboard') }}" class="logo-img" aria-label="{{ config('app.name') }} - espace participant">
                        <img src="{{ \App\Support\Branding::mark() }}" alt="{{ config('app.name') }}">
                    </a>
                    <span class="brand-rule" aria-hidden="true"></span>
                    <div class="logo-sub"><strong>{{ config('app.name') }}</strong><span>Espace participant</span></div>
                </div>
                <button type="button" class="ukc-compact-toggle" @click="toggleCompact()"
                        :aria-pressed="compact" aria-label="Réduire ou déployer le menu" title="Réduire / déployer le menu">
                    <x-ukc.icon name="chevron" :size="16" />
                </button>

                @if ($myColloques->count() + $openCalls->count() > 1)
                    {{-- Sélecteur de colloque : colloques du compte + appels ouverts. --}}
                    <div class="ukc-colloque-switch" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" class="switch-btn" @click="open = !open" :aria-expanded="open">
                            <span class="lbl">
                                <span class="cur">{{ $colloqueEdition?->colloque?->name ?: 'Évènement' }}</span>
                                <span class="ed">{{ $colloqueEdition?->title }}</span>
                            </span>
                            <x-ukc.icon name="chevron" :size="16" />
                        </button>
                        <div class="switch-menu" x-show="open" x-cloak x-transition.origin.top>
                            @if ($openCalls->isNotEmpty())
                                <div class="switch-head">{{ __('Appels à communications ouverts') }}</div>
                                @foreach ($openCalls as $ed)
                                    <form method="POST" action="{{ route('account.colloque.switch') }}">
                                        @csrf
                                        <input type="hidden" name="edition_id" value="{{ $ed->id }}">
                                        <button type="submit" @class(['item', 'active' => $colloqueEdition?->id === $ed->id])>
                                            <span class="tick">@if ($colloqueEdition?->id === $ed->id)<x-ukc.icon name="check" :size="14" />@endif</span>
                                            <span class="txt">
                                                <span class="c">{{ $ed->colloque?->name ?: 'Évènement' }}</span>
                                                <span class="e">{{ $ed->title }} · {{ $ed->year }}</span>
                                            </span>
                                        </button>
                                    </form>
                                @endforeach
                            @endif
                            @if ($myColloques->isNotEmpty())
                                <div class="switch-head">{{ __('Mes évènements') }}</div>
                                @foreach ($myColloques as $ed)
                                    <form method="POST" action="{{ route('account.colloque.switch') }}">
                                        @csrf
                                        <input type="hidden" name="edition_id" value="{{ $ed->id }}">
                                        <button type="submit" @class(['item', 'active' => $colloqueEdition?->id === $ed->id])>
                                            <span class="tick">@if ($colloqueEdition?->id === $ed->id)<x-ukc.icon name="check" :size="14" />@endif</span>
                                            <span class="txt">
                                                <span class="c">{{ $ed->colloque?->name ?: 'Évènement' }}</span>
                                                <span class="e">{{ $ed->title }} · {{ $ed->year }}</span>
                                            </span>
                                        </button>
                                    </form>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @else
                    <div class="sub">{{ $colloqueEdition?->title ?: config('app.name').' · Université de Kara' }}</div>
                @endif
            </div>
            <nav class="ukc-sidebar-nav">
                <a href="{{ route('account.dashboard') }}" title="{{ __('Tableau de bord') }}"
                   @class(['active' => request()->routeIs('account.dashboard')])>
                    <x-ukc.icon name="home" :size="18" /> <span class="txt">{{ __('Tableau de bord') }}</span>
                </a>

                <span class="nav-label">{{ __('Mon dossier') }}</span>
                <a href="{{ route('account.profile') }}" title="{{ __('Mon compte') }}"
                   @class(['active' => request()->routeIs('account.profile') || request()->routeIs('account.preferences') || request()->routeIs('account.notifications') || request()->routeIs('account.security')])>
                    <x-ukc.icon name="user" :size="18" /> <span class="txt">{{ __('Mon compte') }}</span>
                </a>
                <a href="{{ route('account.submissions.index') }}" title="{{ __('Soumissions') }}"
                   @class(['active' => request()->routeIs('account.submissions.*')])>
                    <x-ukc.icon name="file" :size="18" /> <span class="txt">{{ __('Soumissions') }}</span>
                </a>
                <a href="{{ ($payEditionId ?: $colloqueEdition?->id) ? route('account.payment', $payEditionId ?: $colloqueEdition->id) : route('account.dashboard') }}" title="{{ __('Inscription & paiement') }}"
                   @class(['active' => request()->routeIs('account.payment')])>
                    <x-ukc.icon name="card" :size="18" /> <span class="txt">{{ __('Inscription & paiement') }}</span>
                </a>
                @if ($hasFormations)
                    <a href="{{ route('account.formations') }}" title="{{ __('Formations') }}"
                       @class(['active' => request()->routeIs('account.formations')])>
                        <x-ukc.icon name="book" :size="18" /> <span class="txt">{{ __('Formations') }}</span>
                    </a>
                @endif
                <a href="{{ route('account.documents') }}" title="{{ __('Documents') }}"
                   @class(['active' => request()->routeIs('account.documents')])>
                    <x-ukc.icon name="folder" :size="18" /> <span class="txt">{{ __('Documents') }}</span>
                </a>
                <a href="{{ route('account.testimonials') }}" title="{{ __('Mon témoignage') }}"
                   @class(['active' => request()->routeIs('account.testimonials')])>
                    <x-ukc.icon name="sparkles" :size="18" /> <span class="txt">{{ __('Mon témoignage') }}</span>
                </a>

                {{-- Fonctions événementielles : n'apparaissent que pour les personnes concernées. --}}
                @php
                    $isModerator = $account?->isModeratorSomewhere() ?? false;
                    $isRapporteur = $account?->isRapporteurSomewhere() ?? false;
                    $isReviewer = $account?->isReviewerSomewhere() ?? false;
                @endphp
                @if ($isModerator || $isRapporteur || $isReviewer)
                    <span class="nav-label">{{ __('Mes fonctions') }}</span>
                    @if ($isModerator)
                        <a href="{{ route('account.moderation') }}" title="{{ __('Séances à modérer') }}"
                           @class(['active' => request()->routeIs('account.moderation')])>
                            <x-ukc.icon name="mic" :size="18" /> <span class="txt">{{ __('Séances à modérer') }}</span>
                        </a>
                    @endif
                    @if ($isRapporteur)
                        <a href="{{ route('account.reports') }}" title="{{ __('Séances à rapporter') }}"
                           @class(['active' => request()->routeIs('account.reports')])>
                            <x-ukc.icon name="sheet" :size="18" /> <span class="txt">{{ __('Séances à rapporter') }}</span>
                        </a>
                    @endif
                    @if ($isReviewer)
                        <a href="{{ route('account.reviews') }}" title="{{ __('Mes relectures') }}"
                           @class(['active' => request()->routeIs('account.reviews')])>
                            <x-ukc.icon name="inbox" :size="18" /> <span class="txt">{{ __('Mes relectures') }}</span>
                        </a>
                    @endif
                @endif

                <span class="nav-label">{{ __('Colloque') }}</span>
                @if ($colloqueEdition)
                    <a href="{{ route('public.colloque', $colloqueEdition->slug) }}" title="{{ __('Page du colloque') }}">
                        <x-ukc.icon name="globe" :size="18" /> <span class="txt">{{ __('Page du colloque') }}</span>
                    </a>
                    <a href="{{ route('public.colloque', $colloqueEdition->slug) }}#programme" title="{{ __('Programme') }}">
                        <x-ukc.icon name="calendar" :size="18" /> <span class="txt">{{ __('Programme') }}</span>
                    </a>
                @endif
                <a href="{{ route('public.deroulement') }}" title="{{ __('Déroulement') }}">
                    <x-ukc.icon name="video" :size="18" /> <span class="txt">{{ __('Déroulement') }}</span>
                </a>
                <a href="{{ route('public.archives') }}" title="{{ __('Archives') }}">
                    <x-ukc.icon name="book" :size="18" /> <span class="txt">{{ __('Archives') }}</span>
                </a>
                <a href="{{ route('home') }}" title="{{ __('Accueil plateforme') }}">
                    <x-ukc.icon name="external" :size="18" /> <span class="txt">{{ __('Accueil plateforme') }}</span>
                </a>
                <a href="{{ route('public.gesco') }}" title="{{ __('Assistant Gesco') }}">
                    <x-ukc.icon name="sparkles" :size="18" /> <span class="txt">{{ __('Assistant Gesco') }}</span>
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
                    <div>
                        <h1>{{ $title ?? 'Mon espace' }}</h1>
                        @isset($subtitle)<div class="sub" style="font-size:13px;color:var(--ukc-muted);">{{ $subtitle }}</div>@endisset
                    </div>
                </div>
                <div class="actions">
                    @if (auth('web')->check() && ! app('impersonate')->isImpersonating())
                        <a href="{{ route('admin.dashboard') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm" title="{{ __('Revenir au back-office') }}">
                            <x-ukc.icon name="shield" :size="14" /> {{ __('Back-office') }}
                        </a>
                    @endif
                    @if ($colloqueEdition)
                        <a href="{{ route('public.colloque', $colloqueEdition->slug) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                            <x-ukc.icon name="external" :size="14" /> {{ __('Voir le colloque') }}
                        </a>
                    @endif
                    <x-ukc.lang-switcher />
                    <x-ukc.notification-bell />
                </div>
            </header>

            <main class="ukc-content">
                @if (app('impersonate')->isImpersonating())
                    <div style="background:#fef3c7;border:1px solid #f59e0b;color:#92400e;border-radius:12px;
                                padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;
                                justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <span style="display:inline-flex;align-items:center;gap:8px;font-weight:600;">
                            <x-ukc.icon name="eye" :size="16" />
                            Impersonation en cours - vous agissez en tant que {{ $account?->full_name }}.
                        </span>
                        <form method="POST" action="{{ route('impersonation.leave') }}">
                            @csrf
                            <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                <x-ukc.icon name="logout" :size="14" /> Quitter l’impersonation
                            </button>
                        </form>
                    </div>
                @endif
                {{-- Les messages flash (ukc_status/ukc_error) s'affichent en toasts (layout app). --}}
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.app>
