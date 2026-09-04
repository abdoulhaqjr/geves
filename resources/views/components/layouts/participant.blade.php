@php
    $account = auth('account')->user();

    // Colloque courant (sélection en session, sinon défaut) + liste des colloques
    // du compte pour le sélecteur. Toutes les pages personnelles s'y scopent.
    $myColloques = $account ? \App\Support\CurrentColloque::editions($account) : collect();
    $colloqueEdition = $account ? \App\Support\CurrentColloque::current($account) : null;

    // Repli pour les liens publics si le compte ne participe encore à rien.
    $colloqueEdition = $colloqueEdition
        ?? \App\Models\Edition::where('status', 'open')->latest('id')->first();

    // Paiement : l'édition courante si elle est à régler, sinon la première à régler.
    $payEditionId = ($colloqueEdition
        && \App\Models\Participant::where('account_id', $account?->id)
            ->where('edition_id', $colloqueEdition->id)
            ->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted'))
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
            ->exists())
        ? $colloqueEdition->id
        : \App\Models\Participant::where('account_id', $account?->id)
            ->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted'))
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
            ->latest('id')->value('edition_id');
@endphp
<x-layouts.app :title="$title ?? 'Mon espace'">
    <div class="ukc-shell" x-data="{ open: false }">
        <aside class="ukc-sidebar" :class="{ 'open': open }">
            <div class="ukc-sidebar-brand">
                <a href="{{ route('account.dashboard') }}" class="logo-img" aria-label="Université de Kara — espace participant">
                    <img src="{{ asset('img/logo-uk.png') }}" alt="Université de Kara">
                </a>
                <div class="logo-sub" style="margin-top:8px;">Espace participant</div>

                @if ($myColloques->count() > 1)
                    {{-- Sélecteur de colloque : le compte participe à plusieurs colloques. --}}
                    <div class="ukc-colloque-switch" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" class="switch-btn" @click="open = !open" :aria-expanded="open">
                            <span class="lbl">
                                <span class="cur">{{ $colloqueEdition?->colloque?->name ?: 'Colloque' }}</span>
                                <span class="ed">{{ $colloqueEdition?->title }}</span>
                            </span>
                            <x-ukc.icon name="chevron" :size="16" />
                        </button>
                        <div class="switch-menu" x-show="open" x-cloak x-transition.origin.top>
                            <div class="switch-head">Mes colloques</div>
                            @foreach ($myColloques as $ed)
                                <form method="POST" action="{{ route('account.colloque.switch') }}">
                                    @csrf
                                    <input type="hidden" name="edition_id" value="{{ $ed->id }}">
                                    <button type="submit" @class(['item', 'active' => $colloqueEdition?->id === $ed->id])>
                                        <span class="tick">@if ($colloqueEdition?->id === $ed->id)<x-ukc.icon name="check" :size="14" />@endif</span>
                                        <span class="txt">
                                            <span class="c">{{ $ed->colloque?->name ?: 'Colloque' }}</span>
                                            <span class="e">{{ $ed->title }} · {{ $ed->year }}</span>
                                        </span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="sub">{{ $colloqueEdition?->title ?: 'Colloques · Université de Kara' }}</div>
                @endif
            </div>
            <nav class="ukc-sidebar-nav">
                <a href="{{ route('account.dashboard') }}"
                   @class(['active' => request()->routeIs('account.dashboard')])>
                    <x-ukc.icon name="home" :size="18" /> Tableau de bord
                </a>

                <span class="nav-label">Mon dossier</span>
                <a href="{{ route('account.profile') }}"
                   @class(['active' => request()->routeIs('account.profile')])>
                    <x-ukc.icon name="user" :size="18" /> Mon profil
                </a>
                <a href="{{ route('account.submissions.index') }}"
                   @class(['active' => request()->routeIs('account.submissions.*')])>
                    <x-ukc.icon name="file" :size="18" /> Soumissions
                </a>
                <a href="{{ $payEditionId ? route('account.payment', $payEditionId) : ($colloqueEdition ? route('public.colloque', $colloqueEdition->slug) : route('account.dashboard')) }}"
                   @class(['active' => request()->routeIs('account.payment')])>
                    <x-ukc.icon name="card" :size="18" /> Inscription &amp; paiement
                </a>
                <a href="{{ route('account.documents') }}"
                   @class(['active' => request()->routeIs('account.documents')])>
                    <x-ukc.icon name="folder" :size="18" /> Documents
                </a>
                <a href="{{ route('account.preferences') }}"
                   @class(['active' => request()->routeIs('account.preferences') || request()->routeIs('account.notifications') || request()->routeIs('account.security')])>
                    <x-ukc.icon name="shield" :size="18" /> Mon compte
                </a>

                <span class="nav-label">Colloque</span>
                @if ($colloqueEdition)
                    <a href="{{ route('public.colloque', $colloqueEdition->slug) }}">
                        <x-ukc.icon name="globe" :size="18" /> Page du colloque
                    </a>
                    <a href="{{ route('public.colloque', $colloqueEdition->slug) }}#programme">
                        <x-ukc.icon name="calendar" :size="18" /> Programme
                    </a>
                @endif
                <a href="{{ route('public.deroulement') }}">
                    <x-ukc.icon name="video" :size="18" /> Déroulement
                </a>
                <a href="{{ route('public.archives') }}">
                    <x-ukc.icon name="book" :size="18" /> Archives
                </a>
                <a href="{{ route('home') }}">
                    <x-ukc.icon name="external" :size="18" /> Accueil plateforme
                </a>
                <a href="{{ route('public.gesco') }}">
                    <x-ukc.icon name="sparkles" :size="18" /> Assistant Gesco
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
                    @if ($colloqueEdition)
                        <a href="{{ route('public.colloque', $colloqueEdition->slug) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                            <x-ukc.icon name="external" :size="14" /> Voir le colloque
                        </a>
                    @endif
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
                {{-- Les messages flash (ukc_status/ukc_error) s'affichent en toasts (layout app). --}}
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.app>
