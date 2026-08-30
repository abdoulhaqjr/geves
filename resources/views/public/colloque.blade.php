<x-layouts.app :title="$edition->title">
    <style>
        html { scroll-behavior: smooth; }
        section[id] { scroll-margin-top: 84px; }
        nav [data-spy].spy-active { background: var(--ukc-blue); color: #fff; }
    </style>
    @php
        $e = $edition;
        $dateLabel = null;
        if ($e->date_start) {
            $dateLabel = $e->date_start->translatedFormat('d F Y');
            if ($e->date_end && $e->date_end->ne($e->date_start)) {
                $dateLabel = $e->date_start->translatedFormat('d') . '–' . $e->date_end->translatedFormat('d F Y');
            }
        }
    @endphp

    {{-- Bannière promo --}}
    @if (! empty($promo['text']))
        <div style="background:var(--ukc-gold);color:var(--ukc-blue-dark);font-weight:700;text-align:center;
                    padding:10px 16px;font-size:.92rem;display:flex;gap:12px;align-items:center;justify-content:center;flex-wrap:wrap;">
            <span>{{ $promo['text'] }}</span>
            @if (! empty($promo['url']))
                <a href="{{ $promo['url'] }}" target="_blank" rel="noopener"
                   style="background:var(--ukc-blue);color:#fff;padding:5px 14px;border-radius:999px;font-size:.85rem;white-space:nowrap;">
                    {{ $promo['cta'] ?: 'En savoir plus' }}
                </a>
            @endif
        </div>
    @endif

    {{-- ===== Hero (fidèle au mockup page-colloque-amelioree.html) ===== --}}
    @php
        $languages = $e->settings['languages'] ?? null;
        $languages = is_array($languages) ? implode(' · ', $languages) : $languages;
        $countdownToDeadline = $e->submission_open && $e->submission_deadline && $e->submission_deadline->isFuture();
        $cdTarget = $countdownToDeadline ? $e->submission_deadline : $e->date_start;
        $cdLabel = $countdownToDeadline ? 'Clôture des soumissions dans' : 'Le colloque commence dans';
        $hasCountdown = $cdTarget && $cdTarget->isFuture();
    @endphp
    <header class="ukc-hero" id="top">
        @if ($heroVideo)
            <video autoplay muted loop playsinline style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;">
                <source src="{{ $heroVideo }}" type="video/mp4">
            </video>
            <div style="position:absolute;inset:0;z-index:1;background:linear-gradient(135deg, rgba(15,26,51,.86), rgba(34,62,164,.78));"></div>
        @elseif ($heroImage)
            <img src="{{ $heroImage }}" alt="" aria-hidden="true" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;">
            <div style="position:absolute;inset:0;z-index:1;background:linear-gradient(135deg, rgba(15,26,51,.88), rgba(34,62,164,.72));"></div>
        @endif
        <div class="ukc-container" style="position:relative;z-index:2;">
            <div class="ukc-hero-inner">
                <div>
                    <img src="{{ asset('img/logo-uk-white.png') }}" alt="Université de Kara" class="ukc-hero-logo">
                    <div class="ukc-hero-badge">
                        <span class="dot" aria-hidden="true"></span>
                        <span>{{ $e->phase()->label() }}</span>
                    </div>
                    <h1>{{ $e->title }}</h1>
                    @if ($e->subtitle)
                        <p class="ukc-hero-theme">{{ $e->subtitle }}</p>
                    @endif
                    <div class="ukc-hero-meta">
                        @if ($dateLabel)
                            <div><x-ukc.icon name="calendar" :size="16" /> <strong>{{ $dateLabel }}</strong></div>
                        @endif
                        @if ($e->location)
                            <div><x-ukc.icon name="pin" :size="16" /> {{ str_replace(["\r\n", "\r", "\n"], ' · ', $e->location) }}</div>
                        @endif
                        @if ($languages)
                            <div><x-ukc.icon name="users" :size="16" /> {{ $languages }}</div>
                        @endif
                    </div>
                    <div class="ukc-hero-cta">
                        @if ($e->submission_open)
                            <a href="{{ route('account.submissions.create', $e->slug) }}" class="ukc-btn ukc-btn--primary">
                                <x-ukc.icon name="upload" :size="18" /> Soumettre une communication
                            </a>
                        @endif
                        <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--ghost">S’inscrire</a>
                    </div>
                </div>

                @if ($hasCountdown)
                    <div class="ukc-countdown" x-data="ukcCountdown('{{ $cdTarget->toIso8601String() }}')" x-cloak>
                        <div class="ukc-countdown-label">{{ $cdLabel }}</div>
                        <div class="ukc-countdown-grid">
                            <div class="ukc-countdown-item"><div class="num" x-text="String(days).padStart(2,'0')">--</div><div class="unit">Jours</div></div>
                            <div class="ukc-countdown-item"><div class="num" x-text="String(hours).padStart(2,'0')">--</div><div class="unit">Heures</div></div>
                            <div class="ukc-countdown-item"><div class="num" x-text="String(minutes).padStart(2,'0')">--</div><div class="unit">Min</div></div>
                            <div class="ukc-countdown-item"><div class="num" x-text="String(seconds).padStart(2,'0')">--</div><div class="unit">Sec</div></div>
                        </div>
                        @if ($countdownToDeadline)
                            <p style="margin-top:16px;font-size:13px;color:rgba(255,255,255,0.7);">
                                Dernier délai : <strong style="color:var(--ukc-gold);">{{ $e->submission_deadline->translatedFormat('d F Y') }}</strong>
                            </p>
                        @elseif ($dateLabel)
                            <p style="margin-top:16px;font-size:13px;color:rgba(255,255,255,0.7);">Dates : <strong style="color:var(--ukc-gold);">{{ $dateLabel }}</strong></p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </header>

    {{-- ===== Barre de navigation sticky (scrollspy) ===== --}}
    <nav class="ukc-nav-bar">
        <div class="ukc-container">
            <div class="ukc-nav-bar-inner" x-data="ukcScrollspy()">
                @php
                    $cfShort = $e->settings['short_name'] ?? null;
                    $cfShort = $cfShort ?: ($e->edition_label ?: \Illuminate\Support\Str::limit($e->colloque?->name ?? 'Colloque', 22));
                @endphp
                <a href="#top" class="ukc-nav-brand">
                    <span class="dot" aria-hidden="true"></span>
                    {{ $cfShort }} <span>· {{ $e->year ?: $e->date_start?->format('Y') }}</span>
                </a>
                <ul class="ukc-nav-links">
                    @if (count($stats))
                        <li><a href="#chiffres" data-spy>Chiffres</a></li>
                    @endif
                    @if ($e->date_start || $e->date_end || $e->submission_deadline)
                        <li><a href="#dates" data-spy>Dates</a></li>
                    @endif
                    @if ($e->theme || $intro)
                        <li><a href="#presentation" data-spy>Présentation</a></li>
                    @endif
                    @if ($plenaries->count() || $ateliers->count())
                        <li><a href="#programme" data-spy>Programme</a></li>
                    @endif
                    @if (count($speakers))
                        <li><a href="#intervenants" data-spy>Intervenants</a></li>
                    @endif
                    @if (count($fees))
                        <li><a href="#tarifs" data-spy>Tarifs</a></li>
                    @endif
                    @if (array_filter($practical))
                        <li><a href="#infos" data-spy>Infos pratiques</a></li>
                    @endif
                    @if (count($faq))
                        <li><a href="#faq" data-spy>FAQ</a></li>
                    @endif
                </ul>
                <div class="ukc-nav-actions">
                    <a href="{{ route('home') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">Accueil</a>
                    <a href="{{ route('public.deroulement') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">Déroulement</a>
                    <a href="{{ route('account.dashboard') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">Mon espace</a>
                    @if ($e->submission_open)
                        <a href="{{ route('account.submissions.create', $e->slug) }}" class="ukc-btn ukc-btn--primary ukc-btn--sm">Soumettre</a>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <div style="background:var(--ukc-bg);">
        <div style="max-width:1380px;margin:0 auto;padding:clamp(40px,6vw,72px) clamp(16px,4vw,32px);display:flex;flex-direction:column;gap:clamp(40px,6vw,64px);">

            {{-- Bannière officielle (activable/désactivable ; fidèle au mockup) --}}
            @if (($e->settings['banner_enabled'] ?? true))
                <section id="banniere">
                    <div class="ukc-banner-frame">
                        @if ($e->banner_url)
                            <img src="{{ $e->banner_url }}" alt="Bannière du colloque {{ $e->title }}" width="1200" height="514" loading="eager">
                        @else
                            <div class="ukc-banner-placeholder">
                                <div class="tag">Bannière officielle</div>
                                <h2>{{ $e->title }}</h2>
                                @if ($dateLabel || $e->subtitle)
                                    <p>{{ $dateLabel }}@if ($dateLabel && $e->subtitle) · @endif{{ $e->subtitle }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Chiffres clés (fidèle au mockup) --}}
            @if (count($stats))
                <section id="chiffres" style="scroll-margin-top:72px;">
                    <div class="ukc-stats">
                        @foreach ($stats as $stat)
                            <div class="ukc-stat">
                                <div class="num">{!! $stat['num'] !!}</div>
                                <div class="label">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Présentation (fidèle au mockup : prose + callout) --}}
            @if ($e->theme || $intro)
                @php
                    $objectives = is_array($e->settings['objectives'] ?? null) ? $e->settings['objectives'] : [];
                @endphp
                <section id="presentation" style="scroll-margin-top:72px;">
                    <div class="ukc-two-col">
                        <div class="ukc-prose">
                            <h3>Thème général</h3>
                            @if ($e->theme)<p><strong>{{ $e->theme }}</strong></p>@endif
                            @if ($intro)<p style="white-space:pre-line;">{{ $intro }}</p>@endif
                            @if (count($objectives))
                                <h3>Objectifs</h3>
                                <ul>@foreach ($objectives as $obj)<li>{{ $obj }}</li>@endforeach</ul>
                            @endif
                        </div>
                        <div class="ukc-callout" id="soumettre">
                            <h3>Appel à communications</h3>
                            <p>
                                Résumé de <strong>250 mots max.</strong> (FR ou EN), structure imposée. Support oral ou poster.
                                @if ($e->submission_deadline) Dernier délai : <strong>{{ $e->submission_deadline->translatedFormat('d F Y') }}</strong>.@endif
                            </p>
                            @if ($e->submission_open)
                                <a href="{{ route('account.submissions.create', $e->slug) }}" class="ukc-btn ukc-btn--primary">
                                    <x-ukc.icon name="upload" :size="18" /> Déposer mon résumé
                                </a>
                            @else
                                <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--primary">
                                    <x-ukc.icon name="user" :size="18" /> Mon espace
                                </a>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            {{-- Dates clés (fidèle au mockup : cartes avec statut) --}}
            @php
                $keyDates = [];
                if ($e->submission_deadline) {
                    $past = $e->submission_deadline->isPast();
                    $keyDates[] = ['icon' => 'upload', 'label' => 'Soumissions', 'value' => $e->submission_deadline->translatedFormat('d M Y'), 'pill' => $past ? 'closed' : 'soon', 'pillLabel' => $past ? 'Clôturé' : 'À venir'];
                }
                if ($e->date_start) {
                    $future = $e->date_start->isFuture();
                    $keyDates[] = ['icon' => 'calendar', 'label' => 'Ouverture', 'value' => $e->date_start->translatedFormat('d M Y'), 'pill' => $future ? 'soon' : 'info', 'pillLabel' => $future ? 'À venir' : 'En cours'];
                }
                if ($e->date_end && (! $e->date_start || $e->date_end->ne($e->date_start))) {
                    $days = ($e->date_start && $e->date_end) ? ((int) $e->date_start->startOfDay()->diffInDays($e->date_end->startOfDay()) + 1).' jours' : 'Clôture';
                    $keyDates[] = ['icon' => 'calendar-check', 'label' => 'Colloque', 'value' => $e->date_end->translatedFormat('d M Y'), 'pill' => 'info', 'pillLabel' => $days];
                }
            @endphp
            @if (count($keyDates))
                <section id="dates" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>Dates clés</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>Retenez les échéances importantes de cette édition.</p>
                    </div>
                    <div class="ukc-dates-grid">
                        @foreach ($keyDates as $d)
                            <div class="ukc-date-card">
                                <div class="icon"><x-ukc.icon :name="$d['icon']" :size="24" /></div>
                                <h3>{{ $d['label'] }}</h3>
                                <div class="date">{{ $d['value'] }}</div>
                                <div class="status"><span class="ukc-pill ukc-pill--{{ $d['pill'] }}">{{ $d['pillLabel'] }}</span></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Ateliers disciplinaires (fidèle au mockup : theme-cards titre + description) --}}
            @php
                $disciplines = count($workshops)
                    ? $workshops
                    : collect($tracks)->map(fn ($t) => ['title' => $t, 'desc' => null])->all();
            @endphp
            @if (count($disciplines))
                <section id="thematiques" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>Ateliers disciplinaires</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>{{ count($disciplines) }} ateliers regroupant les champs disciplinaires du colloque.</p>
                    </div>
                    <div class="ukc-themes">
                        @foreach ($disciplines as $i => $disc)
                            <div class="ukc-theme-card">
                                <div class="num">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</div>
                                <div>
                                    <h4>{{ $disc['title'] }}</h4>
                                    @if (! empty($disc['desc']))<p>{{ $disc['desc'] }}</p>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Programme (exact mockup : onglets jours + sessions) --}}
            @if ($plenaries->count() || $ateliers->count())
                @php
                    $atelierNames = $ateliers->pluck('title', 'id');
                    $allSessions = $plenaries->concat($ateliers->flatMap->sessions)
                        ->sortBy(fn ($s) => optional($s->starts_at)?->timestamp ?? 0)
                        ->values();
                    // Regroupe par jour ; les sessions non encore datées vont dans un bucket « » affiché en dernier.
                    $byDay = $allSessions->groupBy(fn ($s) => optional($s->session_day ?? $s->starts_at)?->format('Y-m-d') ?? '');
                    $progDays = $byDay->keys()
                        ->sortBy(fn ($d) => $d === '' ? '9999' : $d)
                        ->values();
                @endphp
                @if ($progDays->isNotEmpty())
                    <section id="programme" style="scroll-margin-top:72px;" x-data="{ progDay: '{{ $progDays->first() }}' }">
                        <div class="ukc-block-head">
                            <h2>Programme</h2>
                            <div class="ukc-accent"><i></i><i></i><i></i></div>
                            <p>Aperçu du déroulement du colloque.</p>
                        </div>

                        <div class="ukc-tabs-scroll" role="tablist">
                            @php $dayNo = 0; @endphp
                            @foreach ($progDays as $d)
                                <button type="button" class="ukc-tab" :class="{ active: progDay==='{{ $d }}' }" @click="progDay='{{ $d }}'" role="tab">@if ($d === ''){{ 'À programmer' }}@else{{ 'Jour '.(++$dayNo).' — '.\Illuminate\Support\Carbon::parse($d)->translatedFormat('d M') }}@endif</button>
                            @endforeach
                        </div>

                        @foreach ($progDays as $d)
                            <div class="ukc-program-day" :class="{ active: progDay==='{{ $d }}' }">
                                @foreach ($byDay[$d] as $s)
                                    @php
                                        $isPlenary = $s->type === 'plenary';
                                        $roles = collect([
                                            $s->moderator_name ? 'Mod. '.$s->moderator_name : null,
                                            $s->rapporteur_name ? 'Rapp. '.$s->rapporteur_name : null,
                                        ])->filter()->implode(' · ');
                                        $atelierTitle = $s->atelier_id ? ($atelierNames[$s->atelier_id] ?? null) : null;
                                    @endphp
                                    <div class="ukc-session">
                                        <div class="ukc-session-time">{{ $s->starts_at?->format('H:i') ?: '—' }}</div>
                                        <div class="ukc-session-body">
                                            <span class="type {{ $isPlenary ? 'type--plenary' : 'type--workshop' }}">{{ $isPlenary ? 'Plénière' : 'Atelier' }}</span>
                                            @if ($atelierTitle)<span class="type type--poster">{{ $atelierTitle }}</span>@endif
                                            <h4>{{ $s->label }}</h4>
                                            @if ($s->room || $roles)
                                                <div class="meta">{{ $s->room }}@if ($s->room && $roles) · @endif{{ $roles }}</div>
                                            @endif
                                            @php $comms = ($commsBySession[$s->id] ?? collect())->pluck('submission')->filter(); @endphp
                                            @if ($comms->isNotEmpty())
                                                <ul class="ukc-session-comms">
                                                    @foreach ($comms as $comm)
                                                        <li>{{ $comm->title }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </section>
                @endif
            @endif

            {{-- Intervenants invités (fidèle au mockup) --}}
            @if (count($speakers))
                <section id="intervenants" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>Intervenants invités</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>Des chercheurs de renom qui partageront leur expertise.</p>
                    </div>
                    <div class="ukc-speakers">
                        @foreach ($speakers as $sp)
                            @php
                                $initials = collect(explode(' ', preg_replace('/^(Pr\.|Dr\.|M\.|Mme|Mr)\s*/u', '', $sp['name'])))
                                    ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                            @endphp
                            <div class="ukc-speaker">
                                <div class="avatar">@if (! empty($sp['photo_url']))<img src="{{ $sp['photo_url'] }}" alt="">@else{{ $initials }}@endif</div>
                                <h4>{{ $sp['name'] }}</h4>
                                @if (! empty($sp['role']))<div class="role">{{ $sp['role'] }}</div>@endif
                                @if (! empty($sp['affil']))<div class="affil">{{ $sp['affil'] }}</div>@endif
                                @if (! empty($sp['talk']))<div class="talk">« {{ $sp['talk'] }} »</div>@endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Comités (exact mockup : onglets + carrousel) --}}
            @if ($committees->count())
                @php
                    $comLabels = ['organisation' => 'Comité d’organisation', 'scientifique' => 'Comité scientifique', 'supervision' => 'Comité de supervision'];
                    $firstComRole = collect(array_keys($comLabels))->first(fn ($r) => ($committees[$r] ?? collect())->count()) ?? 'organisation';
                @endphp
                <section id="comites" style="scroll-margin-top:72px;" x-data="{ comTab: '{{ $firstComRole }}' }">
                    <div class="ukc-block-head">
                        <h2>Comités</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>Organisation et évaluation scientifique.</p>
                    </div>

                    <div class="ukc-tabs-scroll" role="tablist">
                        @foreach ($comLabels as $role => $label)
                            @if (($committees[$role] ?? collect())->count())
                                <button type="button" class="ukc-tab" :class="{ active: comTab==='{{ $role }}' }" @click="comTab='{{ $role }}'" role="tab">{{ $label }}</button>
                            @endif
                        @endforeach
                    </div>

                    @foreach ($comLabels as $role => $label)
                        @php $members = $committees[$role] ?? collect(); @endphp
                        @if ($members->count())
                            <div x-show="comTab==='{{ $role }}'" x-cloak>
                                <div class="ukc-swiper" x-data="{ scroll(d) { const t = $refs.track; t.scrollBy({ left: d * t.clientWidth * 0.8, behavior: 'smooth' }); } }">
                                    <button type="button" class="swiper-nav prev" @click="scroll(-1)" aria-label="Précédent">‹</button>
                                    <div class="ukc-swiper-track" x-ref="track">
                                        @foreach ($members as $m)
                                            <div class="ukc-person">
                                                <div class="avatar">
                                                    @if ($m->photo_url)<img src="{{ $m->photo_url }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">@else{{ mb_substr($m->person_name, 0, 1) }}@endif
                                                </div>
                                                <h4>{{ $m->person_name }}</h4>
                                                @if ($m->person_title)<div class="role">{{ $m->person_title }}</div>@endif
                                                @if ($m->institution)<div class="affil">{{ $m->institution }}</div>@endif
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="swiper-nav next" @click="scroll(1)" aria-label="Suivant">›</button>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </section>
            @endif

            {{-- Votre parcours en 5 étapes (fidèle au mockup : stepper) --}}
            <section id="parcours" style="scroll-margin-top:72px;">
                <div class="ukc-block-head">
                    <h2>Votre parcours en 5 étapes</h2>
                    <div class="ukc-accent"><i></i><i></i><i></i></div>
                    <p>Du dépôt de résumé jusqu’à l’attestation de participation.</p>
                </div>
                <div class="ukc-stepper">
                    <div class="ukc-step active"><div class="circle">1</div><h4>Compte</h4><p>Création &amp; vérification</p></div>
                    <div class="ukc-step"><div class="circle">2</div><h4>Soumission</h4><p>Résumé en ligne</p></div>
                    <div class="ukc-step"><div class="circle">3</div><h4>Évaluation</h4><p>Relecture en aveugle</p></div>
                    <div class="ukc-step"><div class="circle">4</div><h4>Paiement</h4><p>Mobile money / carte</p></div>
                    <div class="ukc-step"><div class="circle">5</div><h4>Attestation</h4><p>Documents &amp; livret</p></div>
                </div>
            </section>

            {{-- Frais de participation (fidèle au mockup : cartes de prix) --}}
            @if (count($fees))
                @php $featuredIndex = intdiv(count($fees), 2); @endphp
                <section id="tarifs" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>Frais de participation</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>Tarifs officiels du colloque (FCFA).@if ($feesNote) {{ $feesNote }}@endif</p>
                    </div>
                    <div class="ukc-pricing">
                        @foreach ($fees as $i => $fee)
                            <div @class(['ukc-price-card', 'ukc-price-card--featured' => $i === $featuredIndex])>
                                <h3>{{ $fee['label'] }}</h3>
                                <div class="amount">{{ number_format((float) ($fee['amount'] ?? 0), 0, ',', ' ') }} <small>FCFA</small></div>
                                <ul>
                                    <li><x-ukc.icon name="check" :size="16" /> Accès aux sessions</li>
                                    <li><x-ukc.icon name="check" :size="16" /> Communications &amp; ateliers</li>
                                    <li><x-ukc.icon name="check" :size="16" /> Attestation de participation</li>
                                </ul>
                                <a href="{{ route('account.register') }}"
                                   @class(['ukc-btn', 'ukc-btn--primary' => $i === $featuredIndex, 'ukc-btn--outline' => $i !== $featuredIndex])
                                   style="width:100%;justify-content:center;">Choisir</a>
                            </div>
                        @endforeach
                    </div>
                    <p style="margin:20px 0 0;font-size:.9rem;color:var(--ukc-muted);text-align:center;">
                        Co-auteur d’une communication acceptée ?
                        <a href="{{ route('public.coauthor', $edition->slug) }}" style="color:var(--ukc-blue);font-weight:700;">Réglez vos frais ici</a>.
                    </p>
                </section>
            @endif

            {{-- Partenaires & Sponsors (exact mockup : marquee de logos) --}}
            @if (count($partners))
                <section id="partenaires" class="ukc-partners" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head" style="margin-bottom:0;">
                        <h2>Partenaires &amp; Sponsors</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>Ils soutiennent cette édition.</p>
                    </div>
                    <div class="ukc-marquee">
                        <div class="ukc-marquee-track">
                            {{-- Dupliqué pour la boucle infinie (translateX -50%) --}}
                            @foreach (array_merge($partners, $partners) as $p)
                                <a class="logo-frame" @if (! empty($p['url'])) href="{{ $p['url'] }}" target="_blank" rel="noopener" @endif title="{{ $p['name'] }}">
                                    @if (! empty($p['logo_url']))
                                        <img src="{{ $p['logo_url'] }}" alt="{{ $p['name'] }}">
                                    @else
                                        {{ $p['name'] }}
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            {{-- FAQ (fidèle au mockup : accordéon) --}}
            @if (count($faq))
                <section id="faq" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>Questions fréquentes</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                    </div>
                    <div class="ukc-faq">
                        @foreach ($faq as $item)
                            <details class="ukc-faq-item">
                                <summary>{{ $item['question'] }}</summary>
                                <div class="answer" style="white-space:pre-line;">{{ $item['answer'] }}</div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Infos pratiques (fidèle au mockup : 6 cartes + plan du campus) --}}
            @php
                // Le 4e élément indique un rendu en liste à puces (comme le mockup pour Langues/Hébergement).
                $infos = [
                    ['pin', 'Lieu', $practical['venue'] ?? null, false],
                    ['users', 'Langues', $practical['languages'] ?? null, true],
                    ['bed', 'Hébergement', $practical['accommodation'] ?? null, true],
                    ['video', 'Format hybride', $practical['hybrid'] ?? null, false],
                    ['shield', 'Visa & formalités', $practical['visa'] ?? null, false],
                    ['mail', 'Contacts', $practical['contacts'] ?? null, false],
                    ['bus', 'Transport', $practical['transport'] ?? null, true],
                ];
                $infos = array_values(array_filter($infos, fn ($i) => ! empty($i[2])));
            @endphp
            @if (count($infos) || ! empty($map['embed']))
                <section id="infos" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>Informations pratiques</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>Tout ce qu’il faut savoir pour préparer votre venue.</p>
                    </div>
                    @if (count($infos))
                        <div class="ukc-practical">
                            @foreach ($infos as [$icon, $label, $value, $asList])
                                <div class="ukc-practical-card">
                                    <div class="ico-wrap"><x-ukc.icon :name="$icon" :size="22" /></div>
                                    <h4>{{ $label }}</h4>
                                    @php $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value)), fn ($l) => $l !== '')); @endphp
                                    @if ($asList && count($lines) > 1)
                                        <ul>
                                            @foreach ($lines as $line)<li>{{ $line }}</li>@endforeach
                                        </ul>
                                    @else
                                        <p style="white-space:pre-line;">{{ $value }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (! empty($map['embed']))
                        <div class="ukc-campus-map">
                            <div class="ukc-campus-map-head">
                                <h3>Plan du campus</h3>
                                @if (! empty($map['link']))
                                    <a href="{{ $map['link'] }}" target="_blank" rel="noopener noreferrer">Ouvrir dans OpenStreetMap →</a>
                                @endif
                            </div>
                            <iframe src="{{ $map['embed'] }}" title="Plan du campus — {{ $e->location }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                        </div>
                    @endif
                </section>
            @endif

            {{-- Éditions précédentes (fidèle au mockup : past-cards) --}}
            @if ($siblings->count())
                <section id="editions" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>Éditions précédentes</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                    </div>
                    <div class="ukc-editions">
                        @foreach ($siblings as $sib)
                            <a href="{{ route('public.archive', $sib->slug) }}" class="ukc-past-card" style="color:inherit;">
                                <div class="ukc-past-thumb" @if ($sib->banner_url) style="background-image:url('{{ $sib->banner_url }}')" @endif></div>
                                <div class="ukc-past-body">
                                    <span class="ukc-past-year">{{ $sib->year ?: $sib->date_start?->format('Y') }}</span>
                                    <h3>{{ \Illuminate\Support\Str::limit($sib->title ?: $sib->colloque?->name, 70) }}</h3>
                                    @if ($sib->location)<p>{{ $sib->location }}</p>@endif
                                    <span class="ukc-link">Voir l’édition →</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Contact --}}
            <section id="contact" class="ukc-card">
                <h2 style="font-size:1.3rem;font-weight:800;color:var(--ukc-blue);margin-bottom:16px;">Contact</h2>
                <div class="ukc-grid-2" style="grid-template-columns:1fr 1.2fr;gap:24px;align-items:start;">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        @if (!empty($contact['email']))
                            <div style="display:flex;gap:10px;align-items:center;">
                                <x-ukc.icon name="mail" :size="18" />
                                <a href="mailto:{{ $contact['email'] }}" style="color:var(--ukc-text);">{{ $contact['email'] }}</a>
                            </div>
                        @endif
                        @if (!empty($contact['phone']))
                            <div style="display:flex;gap:10px;align-items:center;">
                                <x-ukc.icon name="phone" :size="18" /> <span>{{ $contact['phone'] }}</span>
                            </div>
                        @endif
                        @if (!empty($contact['address']))
                            <div style="display:flex;gap:10px;align-items:flex-start;">
                                <x-ukc.icon name="pin" :size="18" /> <span>{{ $contact['address'] }}</span>
                            </div>
                        @endif
                        @if (empty($contact['email']) && empty($contact['phone']) && empty($contact['address']))
                            <p style="color:var(--ukc-muted);margin:0;">Envoyez-nous un message via le formulaire.</p>
                        @endif
                    </div>
                    <div>
                        @if (session('contact_sent'))
                            <div class="ukc-alert ukc-alert--ok" style="margin:0;">
                                <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
                                <div>Merci, votre message a bien été envoyé. Nous vous répondrons rapidement.</div>
                            </div>
                        @else
                            <form method="POST" action="{{ route('public.contact', $e->id) }}"
                                  x-data="{ loadedAt: Math.floor(Date.now() / 1000) }"
                                  style="display:flex;flex-direction:column;gap:12px;">
                                @csrf
                                <input type="hidden" name="loaded_at" :value="loadedAt">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div class="ukc-field" style="margin:0;">
                                        <label for="c_name">Votre nom</label>
                                        <input type="text" id="c_name" name="name" value="{{ old('name') }}">
                                        @error('name') <span class="error">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="ukc-field" style="margin:0;">
                                        <label for="c_email">Votre e-mail</label>
                                        <input type="email" id="c_email" name="email" value="{{ old('email') }}">
                                        @error('email') <span class="error">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="ukc-field" style="margin:0;">
                                    <label for="c_message">Message</label>
                                    <textarea id="c_message" name="message" rows="5">{{ old('message') }}</textarea>
                                    @error('message') <span class="error">{{ $message }}</span> @enderror
                                </div>

                                {{-- Honeypot anti-spam : masqué, doit rester vide. --}}
                                <div style="position:absolute;left:-9999px;" aria-hidden="true">
                                    <label>Ne pas remplir <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                                </div>

                                <div>
                                    <button type="submit" class="ukc-btn ukc-btn--blue">
                                        <x-ukc.icon name="mail" :size="18" /> Envoyer le message
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </section>

            {{-- CTA final (fidèle au mockup : bloc bleu) --}}
            <div class="ukc-final-cta" id="inscription">
                <h2>Prêt à participer ?</h2>
                <p>Créez votre compte en quelques minutes, soumettez votre résumé ou inscrivez-vous comme auditeur. Tout se passe en ligne.</p>
                <div class="actions">
                    <a href="{{ route('account.register') }}" class="ukc-btn ukc-btn--primary">
                        <x-ukc.icon name="user-plus" :size="18" /> Créer mon compte
                    </a>
                    <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--ghost">Se connecter</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer PROPRE au colloque (barre d'info), et non le footer plateforme. --}}
    @include('partials.colloque-footer', ['e' => $e])
</x-layouts.app>
