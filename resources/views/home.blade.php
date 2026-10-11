<x-layouts.app :title="__(':app - Université de Kara', ['app' => config('app.name')])">
    @php
        // Style de bannière d'un événement : image si disponible, sinon dégradé UK.
        $bannerStyle = function ($event) {
            // ⚠️ #0f1a33 est la teinte quasi noire que le commentaire de
            // `--ukc-hero` nomme comme étant À ÉCARTER. Le dégradé de
            // remplacement d'image existe déjà dans `public.css`
            // (`.edition-card .cover.no-img`, `.gallery-item.is-more`) : on
            // reprend le même, au lieu d'en garder un troisième.
            $fallback = 'background:linear-gradient(145deg,#1a3080,#223ea4 55%,#0a7a2f);';
            if ($event && $event->banner_url) {
                return "background-image:linear-gradient(120deg,rgba(15,26,51,.55),rgba(34,62,164,.4)),url('".e($event->banner_url)."');";
            }
            return $fallback;
        };
        $colloqueUrl = fn ($e) => $e && $e->slug && $e->isPublished() ? route('public.colloque', $e->slug) : route('home').'#a-venir';
    @endphp

    @include('partials.public-header')

    {{-- ===================== Hero ===================== --}}
    <section class="hero">
        <div class="ukc-container">
            <div class="hero-grid">
                <div>
                    <div class="hero-kicker">{{ __('Université de Kara · Direction de la Recherche et de l’Innovation') }}</div>
                    <h1>{!! __('Plateforme de gestion de <em>séminaires, colloques et journées scientifiques</em> de l’Université de Kara') !!}</h1>
                    <p class="lead">
                        {{ __('Service institutionnel de la DRI pour piloter l’ensemble du cycle de vie des manifestations scientifiques : appels à communications, évaluation, inscriptions, programme, documents officiels et archives. Un espace unique pour les organisateurs, les comités et la communauté universitaire.') }}
                    </p>
                </div>
                <div class="hero-card">
                    @if ($featured)
                        <h3>
                            {{ __('À la une') }}
                            @if ($featured->submission_open)
                                <span class="ukc-pill ukc-pill--open">{{ __('Soumissions ouvertes') }}</span>
                            @elseif ($ongoing && $featured->is($ongoing))
                                <span class="ukc-pill ukc-pill--live">{{ __('En cours') }}</span>
                            @else
                                <span class="ukc-pill ukc-pill--info">{{ __('À venir') }}</span>
                            @endif
                        </h3>
                        <div class="event-title">{{ $featured->tr('title') ?: $featured->series?->name }}</div>
                        <div class="meta">
                            @if ($featured->theme)<div><strong>{{ __('Thème') }}</strong> - {{ $featured->tr('theme') }}</div>@endif
                            <div><strong>{{ __('Dates') }}</strong> -
                                {{ $featured->date_start?->translatedFormat('F Y') ?? __('à préciser') }}
                                @if ($featured->location) · {{ $featured->tr('location') }}@endif
                            </div>
                            @if ($featured->submission_deadline)
                                <div><strong>{{ __('Soumissions') }}</strong> - {{ __('jusqu’au') }} {{ $featured->submission_deadline->translatedFormat('d F Y') }}</div>
                            @endif
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            <a href="{{ $colloqueUrl($featured) }}" class="ukc-btn ukc-btn--primary ukc-btn--sm">{{ __('Page du colloque') }}</a>
                            @auth('account')
                                <a href="{{ route('account.submissions.create', $featured->slug) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">{{ __('Soumettre un résumé') }}</a>
                            @endauth
                        </div>
                    @else
                        <h3>{{ __('À la une') }}</h3>
                        <div class="event-title">{{ __('Prochaine événement à venir') }}</div>
                        <div class="meta"><div>{{ __('Les appels à communications seront publiés ici dès leur ouverture.') }}</div></div>
                        <a href="{{ route('account.register') }}" class="ukc-btn ukc-btn--primary ukc-btn--sm">{{ __('Créer un compte') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== Indicateurs ===================== --}}
    <div class="ukc-container">
        <div class="stats">
            <div class="stat">
                <div class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg></div>
                <div class="num">{{ $stats['event_series'] }}</div>
                <div class="lbl">{{ __('Événements organisées') }}</div>
            </div>
            <div class="stat">
                <div class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9h-4v4h-2v-4H9V9h4V5h2v4h4v2z"/></svg></div>
                <div class="num">{{ $stats['formats'] }}</div>
                <div class="lbl">{{ __('Colloques & séries') }}</div>
            </div>
            <div class="stat">
                <div class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div>
                <div class="num">{{ number_format($stats['participants'], 0, ',', ' ') }}</div>
                <div class="lbl">{{ __('Participants au total') }}</div>
            </div>
            <div class="stat">
                <div class="ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/></svg></div>
                <div class="num">{{ $stats['etablissements'] }}</div>
                <div class="lbl">{{ __('Établissements représentés') }}</div>
            </div>
        </div>
    </div>

    {{-- ===================== Colloque en cours ===================== --}}
    @if ($ongoing)
        <section class="ukc-section" id="en-cours">
            <div class="ukc-container">
                <div class="block-head">
                    <h2>{{ __('Colloque en cours') }}</h2>
                    <p>{!! __('Manifestation dont le <strong>déroulement</strong> a commencé : sessions, ateliers et communications en direct (présentiel et/ou visio).') !!}</p>
                </div>
                <article class="live-card">
                    <div class="visual" style="{{ $bannerStyle($ongoing) }}background-size:cover;background-position:center;">
                        <span class="tag ukc-pill ukc-pill--live">● {{ __('En déroulement') }}</span>
                    </div>
                    <div class="body">
                        <span class="ukc-pill ukc-pill--live">{{ __('Sessions en cours') }}</span>
                        <h3>{{ $ongoing->tr('title') ?: $ongoing->series?->name }}</h3>
                        <div class="meta">
                            @if ($ongoing->theme)<div>{{ __('Thème :') }} {{ $ongoing->tr('theme') }}</div>@endif
                            @if ($ongoing->location)<div>{{ __('Lieu :') }} {{ $ongoing->tr('location') }}</div>@endif
                            <div>{{ __('Du') }} {{ $ongoing->date_start?->translatedFormat('d F') }} {{ __('au') }} {{ $ongoing->date_end?->translatedFormat('d F Y') }}</div>
                        </div>
                        <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px;">
                            <a href="{{ route('public.deroulement') }}" class="ukc-btn ukc-btn--blue">{{ __('Rejoindre le déroulement') }}</a>
                            <a href="{{ $colloqueUrl($ongoing) }}" class="ukc-btn ukc-btn--outline">{{ __('Infos du colloque') }}</a>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    @endif

    {{-- ===================== Colloques à venir ===================== --}}
    @if ($upcoming->isNotEmpty())
        <section class="ukc-section ukc-section--alt" id="a-venir">
            <div class="ukc-container">
                <div class="block-head">
                    <h2>{{ __('Colloques à venir') }}</h2>
                    <p>{!! __('Événements dont l’<strong>appel est lancé</strong> (soumission, évaluation, inscription…) - la date du colloque n’est pas encore atteinte.') !!}</p>
                </div>
                <div class="colloque-grid">
                    @foreach ($upcoming as $e)
                        <article class="colloque-card">
                            <div class="banner" style="{{ $bannerStyle($e) }}">
                                @if ($e->submission_open)
                                    <span class="ukc-pill ukc-pill--open">{{ __('Appel ouvert') }}</span>
                                @else
                                    <span class="ukc-pill ukc-pill--info">{{ __('À venir') }}</span>
                                @endif
                            </div>
                            <div class="body">
                                <h3>{{ $e->tr('title') ?: $e->series?->name }}</h3>
                                <div class="info">
                                    @if ($e->theme)<div>{{ __('Thème :') }} {{ $e->tr('theme') }}</div>@endif
                                    <div>{{ $e->location ? $e->tr('location').' · ' : '' }}{{ $e->date_start?->translatedFormat('F Y') ?? $e->year }}</div>
                                    @if ($e->submission_deadline)<div>{{ __('Date limite :') }} {{ $e->submission_deadline->translatedFormat('d F Y') }}</div>@endif
                                </div>
                                <div class="foot">
                                    <a href="{{ $colloqueUrl($e) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">{{ __('Page du colloque') }}</a>
                                    @auth('account')
                                        <a href="{{ route('account.submissions.create', $e->slug) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">{{ __('Soumettre') }}</a>
                                    @endauth
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================== Colloques passés ===================== --}}
    @if ($past->isNotEmpty())
        <section class="ukc-section" id="passes">
            <div class="ukc-container">
                <div class="block-head">
                    <h2>{{ __('Colloques passés') }}</h2>
                    <p>{{ __('Mémoire des événements précédentes - programmes, galeries et documents publics.') }}</p>
                </div>
                <div class="colloque-grid">
                    @foreach ($past as $e)
                        <article class="colloque-card">
                            <div class="banner" style="{{ $bannerStyle($e) }}">
                                <span class="ukc-pill ukc-pill--closed">{{ __('Clôturé') }}</span>
                            </div>
                            <div class="body">
                                <h3>{{ $e->tr('title') ?: $e->series?->name }}</h3>
                                <div class="info">
                                    <div>{{ $e->year }}{{ $e->location ? ' · '.$e->tr('location') : '' }}</div>
                                    @if ($e->theme)<div>{{ $e->tr('theme') }}</div>@endif
                                </div>
                                <div class="foot">
                                    <a href="{{ route('public.archive', $e->slug) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">{{ __('Détail') }}</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div style="text-align:center;margin-top:24px;">
                    <a href="{{ route('public.archives') }}" class="ukc-btn ukc-btn--outline">{{ __('Toutes les archives des colloques') }}</a>
                </div>
            </div>
        </section>
    @endif

    {{-- ===================== À propos ===================== --}}
    <section class="ukc-section ukc-section--alt" id="apropos">
        <div class="ukc-container">
            <div class="block-head">
                <h2>{{ __('À propos de :app', ['app' => config('app.name')]) }}</h2>
                <p>{{ __('Plateforme institutionnelle de l’Université de Kara pour la gestion des séminaires, colloques et journées scientifiques.') }}</p>
            </div>
            <div class="about-intro">
                <div class="about-copy">
                    <h2>{{ __('Un service de la Direction de la Recherche et de l’Innovation') }}</h2>
                    <p>{{ __(':app est le portail unique de l’Université de Kara pour organiser, suivre et valoriser les manifestations scientifiques placées sous l’égide de l’établissement. De l’appel à communications à la remise des attestations, la plateforme couvre l’ensemble du cycle de vie.', ['app' => config('app.name')]) }}</p>
                    <p>{!! __('Elle n’a <strong>aucune vocation commerciale</strong> : il s’agit d’un outil institutionnel mis à disposition des chercheurs, doctorants, comités scientifiques et partenaires, sous la responsabilité de la <strong>Direction de la Recherche et de l’Innovation (DRI)</strong>.') !!}</p>
                    <div class="actions">
                        <a href="mailto:dri@univkara.tg" class="ukc-btn ukc-btn--blue ukc-btn--sm">{{ __('Écrire à la DRI') }}</a>
                        <a href="#contact" class="ukc-btn ukc-btn--outline ukc-btn--sm">{{ __('Nous contacter') }}</a>
                    </div>
                </div>
                <aside class="about-side">
                    <div class="mark-lg">DRI</div>
                    <h3>{{ __('Direction de la Recherche et de l’Innovation') }}</h3>
                    <p>{{ __('La DRI pilote la politique scientifique de l’Université de Kara et coordonne les colloques, séminaires et journées scientifiques organisés sur le campus et avec les partenaires.') }}</p>
                    <ul>
                        <li><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>{{ __('Coordination des événements et comités') }}</li>
                        <li><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>{{ __('Suivi des soumissions et évaluations') }}</li>
                        <li><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>{{ __('Valorisation et mémoire scientifique') }}</li>
                    </ul>
                </aside>
            </div>
            <div class="about-pillars">
                <div class="about-pillar">
                    <div class="ico"><svg viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/></svg></div>
                    <h3>{{ __('Mission institutionnelle') }}</h3>
                    <p>{{ __('Accompagner la communauté universitaire dans l’organisation et la traçabilité des événements scientifiques.') }}</p>
                </div>
                <div class="about-pillar">
                    <div class="ico"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg></div>
                    <h3>Cycle complet</h3>
                    <p>Appel à communications, soumission, évaluation, programme, badges, attestations et archives.</p>
                </div>
                <div class="about-pillar">
                    <div class="ico"><svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg></div>
                    <h3>{{ __('Non commercial') }}</h3>
                    <p>{{ __('Outil public de service, sans logique marchande - au service de la recherche et de l’innovation.') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== Fonctionnalités ===================== --}}
    <section class="ukc-section" id="fonctionnalites">
        <div class="ukc-container">
            <div class="block-head">
                <h2>{{ __('Au service des participants et des comités') }}</h2>
                <p>{{ __('Outils mis à disposition pour le cycle de vie des colloques universitaires.') }}</p>
            </div>
            <div class="features">
                <div class="feature">
                    <div class="ico c-doc"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg></div>
                    <h3>{{ __('Soumission & suivi') }}</h3>
                    <p>{{ __('Dépôt de résumé guidé, auteurs multiples, suivi du statut et messages du comité dans votre espace.') }}</p>
                </div>
                <div class="feature">
                    <div class="ico c-pay"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg></div>
                    <h3>{{ __('Inscription & paiement') }}</h3>
                    <p>{{ __('Tarifs par catégorie, Mobile Money (Flooz, T-Money) et carte via FedaPay, reçu PDF automatique.') }}</p>
                </div>
                <div class="feature">
                    <div class="ico c-cal"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg></div>
                    <h3>{{ __('Programme & créneaux') }}</h3>
                    <p>{{ __('Ateliers, sessions, modérateurs et affectation des communications sur le planning officiel.') }}</p>
                </div>
                <div class="feature">
                    <div class="ico c-badge"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12zM6 10h4v4H6v-4zm0-4h12v2H6V6zm6 4h6v2h-6v-2zm0 4h6v2h-6v-2z"/></svg></div>
                    <h3>{{ __('Badges & attestations') }}</h3>
                    <p>{{ __('Badge participant, attestations de soumission, communication et participation avec QR de vérification.') }}</p>
                </div>
                <a href="{{ route('public.gesco') }}" class="feature" style="display:block;">
                    <div class="ico c-ai"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 9V7c0-1.1-.9-2-2-2h-3c0-1.66-1.34-3-3-3S9 3.34 9 5H6c-1.1 0-2 .9-2 2v2c-1.66 0-3 1.34-3 3s1.34 3 3 3v4c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-4c1.66 0 3-1.34 3-3s-1.34-3-3-3zM7.5 11.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5S9.83 13 9 13s-1.5-.67-1.5-1.5zM16 17H8v-1.5c0-1.1 1.79-1.67 4-1.67s4 .57 4 1.67V17zm-.5-4c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg></div>
                    <h3>{{ __('Assistance IA Gesco') }}</h3>
                    <p>{{ __('Chat pour répondre aux questions sur les soumissions, tarifs, programme et documents.') }}</p>
                </a>
                <div class="feature">
                    <div class="ico c-sec"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg></div>
                    <h3>{{ __('Compte sécurisé') }}</h3>
                    <p>{{ __('Profil, notifications, mot de passe et double authentification (OTP SMS ou TOTP).') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== Galerie - dernier colloque ===================== --}}
    @if (! empty($gallery))
        <section class="ukc-section ukc-section--alt" id="galerie">
            <div class="ukc-container">
                <div class="block-head">
                    <h2>{{ __('Galerie - dernier colloque') }}</h2>
                    <p>{{ __('Photos et vidéos') }}{{ $galleryEdition ? ' - '.$galleryEdition->title : '' }}. {{ __('Cliquez pour agrandir ou visionner.') }}</p>
                </div>
                @include('partials.gallery', ['media' => $gallery])
                @if ($galleryEdition)
                    <div style="text-align:center;margin-top:20px;">
                        <a href="{{ route('public.archive', $galleryEdition->slug) }}#galerie" class="ukc-btn ukc-btn--outline">{{ __('Voir toute la galerie') }}</a>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ===================== Témoignages ===================== --}}
    @if ($testimonials->isNotEmpty())
    <section class="ukc-section" id="temoignages">
        <div class="ukc-container">
            <div class="block-head">
                <h2>{{ __('Témoignages') }}</h2>
                <p>{{ __('Retours de participants, communicateurs et membres de comités.') }}</p>
            </div>
            <div class="temo-grid">
                @foreach ($testimonials as $t)
                    <figure class="temo-card">
                        <blockquote class="quote">« {{ $t->quote }} »</blockquote>
                        <figcaption class="who">
                            <div class="av" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div>
                            <div>
                                <strong>{{ $t->author_name }}</strong>
                                <span>{{ collect([$t->author_role, $t->author_affiliation])->filter()->implode(' · ') }}</span>
                            </div>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ===================== Contact ===================== --}}
    <section class="ukc-section ukc-section--alt" id="contact">
        <div class="ukc-container">
            <div class="block-head">
                <h2>{{ __('Contact') }}</h2>
                <p>{{ __('Une question sur un colloque, une soumission ou un document ? Écrivez à la DRI.') }}</p>
            </div>
            <div class="contact-grid">
                <div class="contact-info">
                    <h3>{{ __('Besoin d’assistance ?') }}</h3>
                    <p>{{ __('La Direction de la Recherche et de l’Innovation est à votre disposition pour toute question relative aux événements ou à l’utilisation de la plateforme.') }}</p>
                    <div class="contact-list">
                        <div class="contact-line">
                            <div class="ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/></svg></div>
                            <div class="txt"><strong>{{ __('Adresse') }}</strong><span>{{ __('Campus Sud, Bâtiment de la Présidence') }}<br>BP 404 Kara, Togo</span></div>
                        </div>
                        <div class="contact-line">
                            <div class="ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></div>
                            <div class="txt"><strong>{{ __('Email') }}</strong><a href="mailto:dri@univkara.tg">dri@univkara.tg</a><a href="mailto:colloque@univkara.tg">colloque@univkara.tg</a></div>
                        </div>
                        <div class="contact-line">
                            <div class="ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6.62 10.79a15.15 15.15 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.57.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.57a1 1 0 01-.25 1.02l-2.2 2.2z"/></svg></div>
                            <div class="txt"><strong>{{ __('Téléphone') }}</strong><span>+228 XX XX XX XX</span></div>
                        </div>
                    </div>
                </div>
                <div class="ukc-card" style="padding:24px;">
                    @if ($featured)
                        @if (session('contact_sent'))
                            <div class="ukc-alert ukc-alert--ok" style="margin:0;">
                                <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
                                <div>{{ __('Merci, votre message a bien été envoyé. Nous vous répondrons rapidement.') }}</div>
                            </div>
                        @else
                            <form method="POST" action="{{ route('public.contact', $featured->id) }}"
                                  x-data="{ loadedAt: Math.floor(Date.now() / 1000) }"
                                  style="display:flex;flex-direction:column;gap:12px;">
                                @csrf
                                <input type="hidden" name="loaded_at" :value="loadedAt">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div class="ukc-field" style="margin:0;">
                                        <label for="hc_name">{{ __('Votre nom') }}</label>
                                        <input type="text" id="hc_name" name="name" value="{{ old('name') }}">
                                        @error('name') <span class="error">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="ukc-field" style="margin:0;">
                                        <label for="hc_email">{{ __('Votre e-mail') }}</label>
                                        <input type="email" id="hc_email" name="email" value="{{ old('email') }}">
                                        @error('email') <span class="error">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="ukc-field" style="margin:0;">
                                    <label for="hc_message">{{ __('Message') }}</label>
                                    <textarea id="hc_message" name="message" rows="5">{{ old('message') }}</textarea>
                                    @error('message') <span class="error">{{ $message }}</span> @enderror
                                </div>
                                <div style="position:absolute;left:-9999px;" aria-hidden="true">
                                    <label>{{ __('Ne pas remplir') }} <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                                </div>
                                <div>
                                    <button type="submit" class="ukc-btn ukc-btn--blue">
                                        <x-ukc.icon name="mail" :size="18" /> {{ __('Envoyer le message') }}
                                    </button>
                                </div>
                            </form>
                        @endif
                    @else
                        <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:8px;">{{ __('Écrire à la DRI') }}</h3>
                        <p style="color:var(--ukc-muted);font-size:14px;margin-bottom:16px;">{{ __('Envoyez-nous un e-mail, nous vous répondrons rapidement.') }}</p>
                        <a href="mailto:dri@univkara.tg" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="mail" :size="18" /> dri@univkara.tg</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== CTA final ===================== --}}
    <section class="ukc-section ukc-section--tight">
        <div class="ukc-container">
            <div class="cta-band">
                <div>
                    <h2>{{ __('Prêt à participer au prochain colloque ?') }}</h2>
                    <p>{{ __('Créez votre espace, déposez un résumé ou consultez le programme de l’événement en cours. Un accompagnement est disponible via l’assistante Gesco.') }}</p>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:10px;">
                    @guest('account')
                        <a href="{{ route('account.register') }}" class="ukc-btn ukc-btn--primary">{{ __('Créer un compte') }}</a>
                        <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--ghost">{{ __('Se connecter') }}</a>
                    @else
                        <a href="{{ route('account.dashboard') }}" class="ukc-btn ukc-btn--primary">{{ __('Mon espace participant') }}</a>
                    @endguest
                    @if ($featured)
                        <a href="{{ $colloqueUrl($featured) }}" class="ukc-btn ukc-btn--ghost">{{ __('Événement en cours') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @include('partials.public-footer')

    @if (! empty($gallery))
        @include('partials.lightbox')
    @endif
</x-layouts.app>
