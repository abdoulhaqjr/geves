<x-layouts.app :title="$event->tr('title')">
    <style>
        html { scroll-behavior: smooth; }
        section[id] { scroll-margin-top: 84px; }
        nav [data-spy].spy-active { background: var(--ukc-blue); color: #fff; }
    </style>
    @php
        $e = $event;
        $dateLabel = $e->dateRangeLabel();
        // Le programme synthétique (onglet Programme du module) a-t-il du contenu ?
        $synHasContent = collect($programSynthesis['days'] ?? [])->contains(
            fn ($d) => trim((string) ($d['heading'] ?? '')) !== ''
                || collect($d['rows'] ?? [])->contains(fn ($r) => trim((string) ($r['time'] ?? '')) !== '' || trim((string) ($r['label'] ?? '')) !== '')
        );
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
        $languages = is_array($languages) ? implode(' - ', $languages) : $languages;
        $countdownToDeadline = $e->submission_open && $e->submission_deadline && $e->submission_deadline->isFuture();
        $cdTarget = $countdownToDeadline ? $e->submission_deadline : $e->date_start;
        $cdLabel = $countdownToDeadline ? 'Clôture des soumissions dans' : 'Le colloque commence dans';
        $hasCountdown = $cdTarget && $cdTarget->isFuture();
    @endphp
    <header class="ukc-hero" id="top">
        @if ($heroVideo)
            {{-- Le type suit l'extension : un .webm annoncé en video/mp4 ne se
                 lit pas. L'image du hero sert d'affiche le temps du chargement. --}}
            <video @if ($heroVideoMuted ?? true) autoplay muted loop @endif playsinline
                   @if ($heroImage) poster="{{ $heroImage }}" @endif
                   style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;">
                <source src="{{ $heroVideo }}"
                        type="{{ str_ends_with(strtolower(parse_url($heroVideo, PHP_URL_PATH) ?? ''), '.webm') ? 'video/webm' : 'video/mp4' }}">
            </video>
            {{-- ⚠️ Voile sur image : `--ukc-hero-navy` (#14205a), le navy bleuté que la
                 charte désigne pour cet usage. Il portait #0f1a33, la teinte quasi
                 noire explicitement écartée - écrite EN LIGNE, elle échappait au cas
                 qui relit les feuilles de style. Elle ne se voyait que sur les rares
                 événements ayant téléversé un visuel ; depuis la bannière par défaut
                 (Réglages › Bannières), ce voile est celui de TOUS les événements. --}}
            <div style="position:absolute;inset:0;z-index:1;background:linear-gradient(135deg, rgba(20,32,90,.86), rgba(20,32,90,.60));"></div>
        @elseif ($heroImage)
            <img src="{{ $heroImage }}" alt="" aria-hidden="true" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;">
            <div style="position:absolute;inset:0;z-index:1;background:linear-gradient(135deg, rgba(20,32,90,.88), rgba(20,32,90,.62));"></div>
        @endif
        <div class="ukc-container" style="position:relative;z-index:2;">
            <div class="ukc-hero-inner">
                <div>
                    <img src="{{ \App\Support\Branding::wideWhite() }}" alt="{{ config('app.name') }}"
                     @class(['ukc-hero-logo', 'logo-auto-white' => \App\Support\Branding::whitensOnDark(\App\Support\Branding::WIDE)])>
                    <div class="ukc-hero-badge">
                        <span class="dot" aria-hidden="true"></span>
                        <span>{{ $e->phase()->label() }}</span>
                    </div>
                    <h1>{{ $e->tr('title') }}</h1>
                    @if ($e->subtitle)
                        <p class="ukc-hero-theme">{{ $e->subtitle }}</p>
                    @endif
                    <div class="ukc-hero-meta">
                        @if ($dateLabel)
                            <div><x-ukc.icon name="calendar" :size="16" /> <strong>{{ $dateLabel }}</strong></div>
                        @endif
                        @if ($e->location)
                            <div><x-ukc.icon name="pin" :size="16" /> {{ str_replace(["\r\n", "\r", "\n"], ' - ', $e->tr('location')) }}</div>
                        @endif
                        @if ($languages)
                            <div><x-ukc.icon name="users" :size="16" /> {{ $languages }}</div>
                        @endif
                    </div>
                    <div class="ukc-hero-cta">
                        @if ($e->submission_open)
                            <a href="{{ route('account.submissions.create', $e->slug) }}" class="ukc-btn ukc-btn--primary">
                                <x-ukc.icon name="upload" :size="18" /> {{ __('Soumettre une communication') }}
                            </a>
                        @endif
                        <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--ghost">{{ __('S’inscrire') }}</a>
                    </div>
                </div>

                @if ($hasCountdown)
                    <div class="ukc-countdown" x-data="ukcCountdown('{{ $cdTarget->toIso8601String() }}')" x-cloak>
                        <div class="ukc-countdown-label">{{ $cdLabel }}</div>
                        <div class="ukc-countdown-grid">
                            <div class="ukc-countdown-item"><div class="num" x-text="String(days).padStart(2,'0')">--</div><div class="unit">{{ __('Jours') }}</div></div>
                            <div class="ukc-countdown-item"><div class="num" x-text="String(hours).padStart(2,'0')">--</div><div class="unit">{{ __('Heures') }}</div></div>
                            <div class="ukc-countdown-item"><div class="num" x-text="String(minutes).padStart(2,'0')">--</div><div class="unit">{{ __('Min') }}</div></div>
                            <div class="ukc-countdown-item"><div class="num" x-text="String(seconds).padStart(2,'0')">--</div><div class="unit">{{ __('Sec') }}</div></div>
                        </div>
                        @if ($countdownToDeadline)
                            <p style="margin-top:16px;font-size:13px;color:rgba(255,255,255,0.7);">
                                {{ __('Dernier délai :') }} <strong style="color:var(--ukc-gold);">{{ $e->submission_deadline->translatedFormat('d F Y') }}</strong>
                            </p>
                        @elseif ($dateLabel)
                            <p style="margin-top:16px;font-size:13px;color:rgba(255,255,255,0.7);">{{ __('Dates :') }} <strong style="color:var(--ukc-gold);">{{ $dateLabel }}</strong></p>
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
                    $cfShort = $cfShort ?: ($e->tr('event_label') ?: \Illuminate\Support\Str::limit($e->series?->name ?? __('Événement'), 22));
                @endphp
                <a href="#top" class="ukc-nav-brand">
                    <span class="dot" aria-hidden="true"></span>
                    {{ $cfShort }} <span>- {{ $e->year ?: $e->date_start?->format('Y') }}</span>
                </a>
                <ul class="ukc-nav-links">
                    @if (count($stats))
                        <li><a href="#chiffres" data-spy>{{ __('Chiffres') }}</a></li>
                    @endif
                    @if ($e->date_start || $e->date_end || $e->submission_deadline)
                        <li><a href="#dates" data-spy>{{ __('Dates') }}</a></li>
                    @endif
                    @if ($e->theme || $intro)
                        <li><a href="#presentation" data-spy>{{ __('Présentation') }}</a></li>
                    @endif
                    @if ($synHasContent)
                        <li><a href="#programme" data-spy>{{ __('Programme') }}</a></li>
                    @endif
                    @if (count($speakers))
                        <li><a href="#intervenants" data-spy>{{ __('Intervenants') }}</a></li>
                    @endif
                    @if (count($fees))
                        <li><a href="#tarifs" data-spy>{{ __('Tarifs') }}</a></li>
                    @endif
                    @if ($formations->isNotEmpty())
                        <li><a href="#formations" data-spy>{{ __('Formations') }}</a></li>
                    @endif
                    @if (array_filter($practical))
                        <li><a href="#infos" data-spy>{{ __('Infos pratiques') }}</a></li>
                    @endif
                    @if (count($faq))
                        <li><a href="#faq" data-spy>{{ __('FAQ') }}</a></li>
                    @endif
                </ul>
                <div class="ukc-nav-actions">
                    <a href="{{ route('home') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">{{ __('Accueil') }}</a>
                    <a href="{{ route('public.deroulement') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">{{ __('Déroulement') }}</a>
                    <a href="{{ route('account.dashboard') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">{{ __('Mon espace') }}</a>
                    @if ($e->submission_open)
                        <a href="{{ route('account.submissions.create', $e->slug) }}" class="ukc-btn ukc-btn--primary ukc-btn--sm">{{ __('Soumettre') }}</a>
                    @endif
                    <x-ukc.lang-switcher />
                </div>
            </div>
        </div>
    </nav>

    <div style="background:var(--ukc-bg);">
        <div style="max-width:1380px;margin:0 auto;padding:clamp(40px,6vw,72px) clamp(16px,4vw,32px);display:flex;flex-direction:column;gap:clamp(40px,6vw,64px);">

            {{-- Bannière officielle : un visuel téléversé par l'organisation,
                 affichable ou non depuis l'onglet « Identité visuelle ».
                 ⚠️ Il y avait ici, à défaut de visuel, un SUBSTITUT : un bloc
                 coloré de 420px intitulé « BANNIÈRE OFFICIELLE » qui répétait le
                 titre et les dates déjà écrits dans le bandeau, 400px plus haut.
                 Aucun des deux colloques n'ayant téléversé de visuel, c'est ce
                 substitut que voyait TOUT visiteur : un emplacement vide montré
                 au public, avec son étiquette d'emplacement. Un emplacement vide
                 ne se montre pas, il ne s'affiche tout simplement pas. --}}
            @if (($e->settings['banner_enabled'] ?? true) && $e->banner_url)
                <section id="banniere">
                    <div class="ukc-banner-frame">
                        <img src="{{ $e->banner_url }}" alt="Bannière du colloque {{ $e->title }}" width="1200" height="514" loading="eager">
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

            {{-- Dates clés (fidèle au mockup : cartes avec statut) - placées avant la présentation --}}
            @php
                $keyDates = [];
                if ($e->submission_deadline) {
                    $past = $e->submission_deadline->isPast();
                    $keyDates[] = ['icon' => 'upload', 'label' => __('Soumissions'), 'value' => $e->submission_deadline->translatedFormat('d M Y'), 'pill' => $past ? 'closed' : 'soon', 'pillLabel' => $past ? __('Clôturé') : __('À venir')];
                }
                if ($e->date_start) {
                    $future = $e->date_start->isFuture();
                    $keyDates[] = ['icon' => 'calendar', 'label' => __('Ouverture'), 'value' => $e->date_start->translatedFormat('d M Y'), 'pill' => $future ? 'soon' : 'info', 'pillLabel' => $future ? __('À venir') : __('En cours')];
                }
                if ($e->date_end && (! $e->date_start || $e->date_end->ne($e->date_start))) {
                    $days = ($e->date_start && $e->date_end) ? ((int) $e->date_start->startOfDay()->diffInDays($e->date_end->startOfDay()) + 1).' '.__('jours') : __('Clôture');
                    $keyDates[] = ['icon' => 'calendar-check', 'label' => __('Événement'), 'value' => $e->date_end->translatedFormat('d M Y'), 'pill' => 'info', 'pillLabel' => $days];
                }
            @endphp
            @if (count($keyDates))
                <section id="dates" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>{{ __('Dates clés') }}</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>{{ __('Retenez les échéances importantes de cet événement.') }}</p>
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

            {{-- Présentation (fidèle au mockup : prose + callout) --}}
            @if ($e->theme || $intro)
                @php
                    $objectives = is_array($e->settings['objectives'] ?? null) ? $e->settings['objectives'] : [];
                @endphp
                <section id="presentation" style="scroll-margin-top:72px;">
                    <div class="ukc-two-col">
                        <div class="ukc-prose">
                            <h3>{{ __('Thème général') }}</h3>
                            @if ($e->theme)<p><strong>{{ $e->tr('theme') }}</strong></p>@endif
                            @if ($intro)<p style="white-space:pre-line;">{{ $intro }}</p>@endif
                            @if (count($objectives))
                                <h3>{{ __('Objectifs') }}</h3>
                                <ul>@foreach ($objectives as $obj)<li>{{ $obj }}</li>@endforeach</ul>
                            @endif
                        </div>
                        <div class="ukc-callout" id="soumettre">
                            <h3>{{ __('Appel à communications') }}</h3>
                            <p>
                                {!! __('Résumé de <strong>250 mots max.</strong> (FR ou EN), structure imposée. Support oral ou poster.') !!}
                                @if ($e->submission_deadline) {{ __('Dernier délai :') }} <strong>{{ $e->submission_deadline->translatedFormat('d F Y') }}</strong>.@endif
                            </p>
                            @if ($e->submission_open)
                                <a href="{{ route('account.submissions.create', $e->slug) }}" class="ukc-btn ukc-btn--primary">
                                    <x-ukc.icon name="upload" :size="18" /> {{ __('Déposer mon résumé') }}
                                </a>
                            @else
                                <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--primary">
                                    <x-ukc.icon name="user" :size="18" /> {{ __('Mon espace') }}
                                </a>
                            @endif
                        </div>
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
                        <h2>{{ __('Ateliers disciplinaires') }}</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>{{ __(':count ateliers regroupant les champs disciplinaires de l’événement.', ['count' => count($disciplines)]) }}</p>
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

            {{-- Programme synthétique : saisi dans l'onglet « Programme » du module
                 (edition.settings['program_synthesis']) et rendu par le partial
                 partagé partials.program-synthesis - l'admin voit donc EXACTEMENT
                 ce qui est publié ici. --}}
            @if ($synHasContent)
                <section id="programme" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>{{ ($programSynthesis['title'] ?? '') !== '' ? $programSynthesis['title'] : __('Programme') }}</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>{{ __('Aperçu du déroulement de l’événement.') }}</p>
                    </div>

                    @include('partials.program-synthesis', ['synthesis' => $programSynthesis])
                </section>
            @endif

            {{-- Intervenants invités (fidèle au mockup) --}}
            @if (count($speakers))
                <section id="intervenants" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>{{ __('Intervenants invités') }}</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>{{ __('Des chercheurs de renom qui partageront leur expertise.') }}</p>
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

            {{-- Votre parcours en 5 étapes (fidèle au mockup : stepper) --}}
            <section id="parcours" style="scroll-margin-top:72px;">
                <div class="ukc-block-head">
                    <h2>{{ __('Votre parcours en 5 étapes') }}</h2>
                    <div class="ukc-accent"><i></i><i></i><i></i></div>
                    <p>{{ __('Du dépôt de résumé jusqu’à l’attestation de participation.') }}</p>
                </div>
                <div class="ukc-stepper">
                    <div class="ukc-step active"><div class="circle">1</div><h4>{{ __('Compte') }}</h4><p>{{ __('Création & vérification') }}</p></div>
                    <div class="ukc-step"><div class="circle">2</div><h4>{{ __('Soumission') }}</h4><p>{{ __('Résumé en ligne') }}</p></div>
                    <div class="ukc-step"><div class="circle">3</div><h4>{{ __('Évaluation') }}</h4><p>{{ __('Relecture en aveugle') }}</p></div>
                    <div class="ukc-step"><div class="circle">4</div><h4>{{ __('Paiement') }}</h4><p>{{ __('Mobile money / carte') }}</p></div>
                    <div class="ukc-step"><div class="circle">5</div><h4>{{ __('Attestation') }}</h4><p>{{ __('Documents & livret') }}</p></div>
                </div>
            </section>

            {{-- Frais de participation.
                 ⚠️ Le participant NE CHOISIT PAS son tarif : il se DÉDUIT de son
                 grade (`Fees::resolveAmount`, par les grades rattachés à chaque
                 catégorie dans l'onglet « Soumissions & tarifs »). Les cartes
                 étaient des cartes d'achat - un bouton « Choisir » par tarif, et
                 une pastille « Recommandé » posée sur la carte du MILIEU, donc
                 sur rien : elles disaient l'inverse de ce que fait le logiciel et
                 invitaient à prendre le moins cher. Elles RENSEIGNENT désormais -
                 qui est concerné, à quel prix.
                 ⚠️ Les trois mêmes avantages répétés sur chaque carte ne
                 distinguaient rien : ils sont dits UNE fois sous la grille, et la
                 carte garde la seule chose qui diffère, le grade couvert.
                 ⚠️ La règle « le tarif se déduit du grade » se lit dans les CARTES
                 elles-mêmes (les grades couverts y figurent) : le bandeau qui la
                 répétait au-dessus de la grille a été retiré, il doublait ce que
                 la grille montre déjà. Ne pas le réintroduire.
                 ⚠️ Rien de personnalisé ici (pas de « votre tarif » pour qui est
                 connecté) : la page reste la même pour tout le monde, donc
                 cacheable en HTTP - cf. EventSeriesController. --}}
            @if (count($fees))
                {{-- ⚠️ Forme BLOC et non `@php(…)` inline : ce fichier contient plus bas
                     des blocs `@php … @endphp`, et Blade extrait les blocs de code AVANT
                     tout le reste - un `@php(` inline s'apparie alors avec le `@endphp`
                     suivant et avale tout ce qui les sépare, directives comprises. --}}
                @php
                    $feesRattaches = collect($fees)
                        ->contains(fn ($fee) => ($fee['grades'] ?? []) !== [] || ($fee['default'] ?? false));
                @endphp
                <section id="tarifs" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>{{ __('Frais de participation') }}</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>{{ __('Tarifs officiels de l’événement (FCFA).') }}@if ($feesNote) {{ $feesNote }}@endif</p>
                    </div>


                    {{-- ⚠️ Une GRILLE DE CARTES, c'est ce qu'il y avait, et c'est la
                         mauvaise forme pour un barème. Mesuré sur le colloque de l'eau
                         (6 catégories, fenêtre de 1280px) : une rangée de 4 cartes puis
                         une rangée ORPHELINE de 2 ; des cartes hautes de 388px dont
                         194px de VIDE sous le contenu, parce qu'une grille égalise les
                         hauteurs de rangée alors que les catégories couvrent 1 à 6
                         grades ; et six montants à six abscisses différentes, donc
                         incomparables - alors que COMPARER est la seule chose qu'on
                         fasse devant un barème.
                         Une liste n'a ni rangée orpheline (2 ou 12 catégories tiennent
                         pareil), ni vide à combler (chaque ligne fait sa hauteur), et
                         elle empile les montants dans une colonne unique. --}}
                    <ul class="ukc-fees">
                        @foreach ($fees as $fee)
                            @php
                                // Le drapeau « catégorie par défaut » se lit comme un
                                // grade couvert de plus : c'est ce qu'il est pour le
                                // visiteur, qui cherche la ligne qui le concerne.
                                $gradesCouverts = $fee['grades'] ?? [];
                                if ($fee['default'] ?? false) {
                                    $gradesCouverts[] = __('Tout autre grade');
                                }
                            @endphp
                            <li class="ukc-fee">
                                <div class="ukc-fee__who">
                                    <h3>{{ $fee['label'] }}</h3>
                                    @if ($gradesCouverts !== [])
                                        <p class="ukc-fee__grades">
                                            <span class="k">{{ __('Grades concernés') }}</span>
                                            <span class="v">{{ implode(', ', $gradesCouverts) }}</span>
                                        </p>
                                    @endif
                                </div>
                                <p class="ukc-fee__amount">
                                    <span class="n">{{ number_format((float) ($fee['amount'] ?? 0), 0, ',', ' ') }}</span>
                                    <span class="c">FCFA</span>
                                </p>
                            </li>
                        @endforeach
                    </ul>

                    {{-- ⚠️ Pas de phrase « tous les tarifs comprennent l'accès aux
                         sessions, les communications et l'attestation ». Elle était
                         écrite en dur, donc affirmée à l'identique pour TOUS les
                         événements, sans qu'aucun réglage ne la soutienne : une
                         promesse que le logiciel ne tient pas. Ce qui vaut pour un
                         événement donné se dit dans la note de tarifs
                         (`settings.fees_note`), affichée sous le titre de la section,
                         où l'organisation l'écrit elle-même. --}}
                    <div class="ukc-fees-foot">
                        {{-- Sans grade rattaché, le visiteur ne peut PAS savoir laquelle des
                             cartes est la sienne : on le lui dit plutôt que de le laisser
                             deviner devant trois prix. --}}
                        @unless ($feesRattaches)
                            <p class="pending">{{ __('Le tarif qui vous sera appliqué vous est confirmé au moment de l’inscription.') }}</p>
                        @endunless
                        <a href="{{ route('account.register') }}" class="ukc-btn ukc-btn--primary">
                            <x-ukc.icon name="user-plus" :size="16" /> {{ __('Créer mon compte et m’inscrire') }}
                        </a>
                        <p class="coauthor">
                            {{ __('Co-auteur d’une communication acceptée ?') }}
                            <a href="{{ route('public.coauthor', $event->slug) }}">{{ __('Réglez vos frais ici') }}</a>.
                        </p>
                    </div>
                </section>
            @endif

{{-- Formations (module activable ; formations publiées et publiques) --}}
            @if ($formations->isNotEmpty())
                <section id="formations" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>{{ __('Formations') }}</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
                        @foreach ($formations as $f)
                            <div class="ukc-card">
                                @if ($f->flyerUrl())
                                    <img src="{{ $f->flyerUrl() }}" alt="{{ $f->title }}" style="width:100%;height:130px;object-fit:cover;border-radius:8px;margin-bottom:10px;">
                                @endif
                                <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
                                    <strong style="font-size:1.05rem;">{{ $f->title }}</strong>
                                    <span class="ukc-pill {{ $f->isFree() ? 'ukc-pill--ok' : 'ukc-pill--muted' }}">{{ $f->isFree() ? __('Gratuite') : number_format((float) $f->fee, 0, ',', ' ').' '.config('payments.currency', 'XOF') }}</span>
                                </div>
                                @if ($f->theme)<div style="color:var(--ukc-muted);font-size:.9rem;">{{ $f->theme }}</div>@endif
                                <div style="font-size:.82rem;color:var(--ukc-light);margin-top:6px;">
                                    @if ($f->starts_at){{ $f->starts_at->format('d/m/Y H:i') }}@endif
                                    @if ($f->location) - {{ $f->location }}@endif @if ($f->room) - {{ $f->room }}@endif
                                </div>
                                @if ($f->objective)<p style="font-size:.9rem;margin:10px 0 0;">{{ \Illuminate\Support\Str::limit($f->objective, 160) }}</p>@endif
                                @if ($f->trainers->isNotEmpty())
                                    <div style="font-size:.82rem;color:var(--ukc-muted);margin-top:8px;">{{ __('Formateur(s)') }} : {{ $f->trainers->pluck('name')->join(', ') }}</div>
                                @endif
                                <a href="{{ route('account.formations') }}" class="ukc-btn ukc-btn--blue" style="margin-top:12px;">
                                    <x-ukc.icon name="book" :size="15" /> {{ __('S’inscrire') }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Infos pratiques (fidèle au mockup : 6 cartes + plan du campus) --}}
            @php
                // Le 4e élément indique un rendu en liste à puces (comme le mockup pour Langues/Hébergement).
                $infos = [
                    ['pin', __('Lieu'), $practical['venue'] ?? null, false],
                    ['users', __('Langues'), $practical['languages'] ?? null, true],
                    ['bed', __('Hébergement'), $practical['accommodation'] ?? null, true],
                    ['video', __('Format hybride'), $practical['hybrid'] ?? null, false],
                    ['shield', __('Visa & formalités'), $practical['visa'] ?? null, false],
                    ['mail', __('Contacts'), $practical['contacts'] ?? null, false],
                    ['bus', __('Transport'), $practical['transport'] ?? null, true],
                ];
                $infos = array_values(array_filter($infos, fn ($i) => ! empty($i[2])));
            @endphp
            @if (count($infos) || ! empty($map['embed']))
                <section id="infos" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>{{ __('Informations pratiques') }}</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>{{ __('Tout ce qu’il faut savoir pour préparer votre venue.') }}</p>
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
                                <h3>{{ __('Plan du campus') }}</h3>
                                @if (! empty($map['link']))
                                    <a href="{{ $map['link'] }}" target="_blank" rel="noopener noreferrer">{{ __('Ouvrir dans OpenStreetMap →') }}</a>
                                @endif
                            </div>
                            <iframe src="{{ $map['embed'] }}" title="Plan du campus - {{ $e->location }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                        </div>
                    @endif
                </section>
            @endif

            {{-- Comités (exact mockup : onglets + carrousel) --}}
            @if ($committees->count())
                @php
                    $comLabels = ['organisation' => __('Comité d’organisation'), 'scientifique' => __('Comité scientifique'), 'supervision' => __('Comité de supervision')];
                    $firstComRole = collect(array_keys($comLabels))->first(fn ($r) => ($committees[$r] ?? collect())->count()) ?? 'organisation';
                @endphp
                <section id="comites" style="scroll-margin-top:72px;" x-data="{ comTab: '{{ $firstComRole }}' }">
                    <div class="ukc-block-head">
                        <h2>{{ __('Comités') }}</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>{{ __('Organisation et évaluation scientifique.') }}</p>
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

            {{-- Partenaires & Sponsors (exact mockup : marquee de logos) --}}
            @if (count($partners))
                <section id="partenaires" class="ukc-partners" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head" style="margin-bottom:0;">
                        <h2>{{ __('Partenaires & Sponsors') }}</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                        <p>{{ __('Ils soutiennent cet événement.') }}</p>
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
                        <h2>{{ __('Questions fréquentes') }}</h2>
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

            {{-- Événements précédents (fidèle au mockup : past-cards) --}}
            @if ($siblings->count())
                <section id="events" style="scroll-margin-top:72px;">
                    <div class="ukc-block-head">
                        <h2>{{ __('Événements précédents') }}</h2>
                        <div class="ukc-accent"><i></i><i></i><i></i></div>
                    </div>
                    <div class="ukc-editions">
                        @foreach ($siblings as $sib)
                            <a href="{{ route('public.archive', $sib->slug) }}" class="ukc-past-card" style="color:inherit;">
                                <div class="ukc-past-thumb" @if ($sib->banner_url) style="background-image:url('{{ $sib->banner_url }}')" @endif></div>
                                <div class="ukc-past-body">
                                    <span class="ukc-past-year">{{ $sib->year ?: $sib->date_start?->format('Y') }}</span>
                                    <h3>{{ \Illuminate\Support\Str::limit($sib->title ?: $sib->series?->name, 70) }}</h3>
                                    @if ($sib->location)<p>{{ $sib->location }}</p>@endif
                                    <span class="ukc-link">{{ __('Voir l’événement →') }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- CTA final (fidèle au mockup : bloc bleu) --}}
            <div class="ukc-final-cta" id="inscription">
                <h2>{{ __('Prêt à participer ?') }}</h2>
                <p>{{ __('Créez votre compte en quelques minutes, soumettez votre résumé ou inscrivez-vous comme auditeur. Tout se passe en ligne.') }}</p>
                <div class="actions">
                    <a href="{{ route('account.register') }}" class="ukc-btn ukc-btn--primary">
                        <x-ukc.icon name="user-plus" :size="18" /> {{ __('Créer mon compte') }}
                    </a>
                    <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--ghost">{{ __('Se connecter') }}</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer PROPRE au colloque (barre d'info), et non le footer plateforme. --}}
    @include('partials.colloque-footer', ['e' => $e])
</x-layouts.app>
