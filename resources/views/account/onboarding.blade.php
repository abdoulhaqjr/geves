@php
    use App\Support\TutorialGuideCatalog;

    $categories = TutorialGuideCatalog::categories();
    $counts = $guides->countBy(fn ($guide) => TutorialGuideCatalog::categoryFor($guide->key));
    $videosVisible = $guides->filter(fn ($guide) => $guide->hasCurrentVideo() && $guide->video_visible_to_participants)->count();
    $actionLinks = [
        'fonctionnement-participant' => [route('account.dashboard'), 'Ouvrir le tableau de bord'],
        'soumission' => [route('account.submissions.index'), 'Voir mes soumissions'],
        'visio' => [route('account.dashboard'), 'Voir mes séances'],
        'rapport' => [route('account.reports'), 'Ouvrir mes rapports'],
        'moderation' => [route('account.moderation'), 'Ouvrir mes séances'],
        'chat-public' => [route('public.gesco'), 'Ouvrir le chatbot public'],
    ];
@endphp

<x-layouts.participant title="Prise en main" subtitle="Guides adaptés à vos accès et à votre rôle">
    <x-slot:actions><a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('account.dashboard') }}"><x-ukc.icon name="home" :size="14" /> Tableau de bord</a></x-slot:actions>

    <div class="account-page ukc-onb ukc-onb--participant" x-data="{ activeCategory: 'all' }">
        <section class="onb-hero" aria-labelledby="onb-hero-title">
            <div class="onb-hero__copy">
                <span class="onb-hero__eyebrow"><x-ukc.icon name="book" :size="15" /> Centre de prise en main</span>
                <h2 id="onb-hero-title">Chaque étape, au bon endroit.</h2>
                <p>Découvrez les fonctions de votre espace, puis suivez les parcours adaptés à votre rôle dans le colloque.</p>
                <a class="onb-hero__link" href="#parcours">Explorer mes guides <x-ukc.icon name="chevron" :size="16" /></a>
            </div>
            <div class="onb-hero__summary" aria-label="Votre parcours en chiffres">
                <div><strong>{{ $guides->count() }}</strong><span>{{ $guides->count() > 1 ? 'guides pour vous' : 'guide pour vous' }}</span></div>
                <div><strong>{{ $videosVisible }}</strong><span>{{ $videosVisible > 1 ? 'vidéos disponibles' : 'vidéo disponible' }}</span></div>
                <p><x-ukc.icon name="calendar" :size="16" /> {{ $event?->title ?: 'Parcours général' }}</p>
            </div>
        </section>

        <div class="onb-section-head" id="parcours">
            <div><span class="onb-section-head__eyebrow">VOTRE BIBLIOTHÈQUE</span><h2>Des guides à suivre à votre rythme</h2><p>{{ $event ? 'Votre sélection tient compte de vos fonctions dans le colloque actif.' : 'Les guides généraux restent disponibles. Sélectionnez un colloque pour afficher ceux liés à vos fonctions.' }}</p></div>
            <span class="onb-section-head__count">{{ $guides->count() }} parcours</span>
        </div>

        @if ($guides->isEmpty())
            <section class="ukc-card onb-empty">
                <span class="onb-empty__icon"><x-ukc.icon name="book" :size="26" /></span>
                <h2>Aucun guide pour le moment</h2>
                <p>Sélectionnez un colloque ou rejoignez un événement pour accéder aux guides liés à vos fonctions.</p>
            </section>
        @else
            <div class="ukc-nav-docs" aria-label="Catégories de guides">
                <button type="button" :class="{ active: activeCategory === 'all' }" @click="activeCategory = 'all'">Tous <span class="nav-count">{{ $guides->count() }}</span></button>
                @foreach ($categories as $key => $category)
                    @if (($counts[$key] ?? 0) > 0)<button type="button" :class="{ active: activeCategory === '{{ $key }}' }" @click="activeCategory = '{{ $key }}'">{{ $category['label'] }} <span class="nav-count">{{ $counts[$key] }}</span></button>@endif
                @endforeach
            </div>
            <div class="onb-grid onb-grid--participant">
                @foreach ($guides as $guide)
                    @php($category = TutorialGuideCatalog::categoryFor($guide->key))
                    @php($hasVisibleVideo = $guide->hasCurrentVideo() && $guide->video_visible_to_participants)
                    <article @class(['ukc-card', 'onb-card', 'onb-card--participant', 'onb-card--featured' => $loop->first && $hasVisibleVideo]) x-show="activeCategory === 'all' || activeCategory === '{{ $category }}'" id="guide-{{ $guide->key }}">
                        <div class="onb-card__body">
                            <header class="onb-card__head">
                                <span class="onb-card__icon"><x-ukc.icon :name="TutorialGuideCatalog::iconFor($guide->key)" :size="21" /></span>
                                <div class="onb-card__titles">
                                    <span class="onb-card__number">PARCOURS {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <h3 class="onb-card__title">{{ $guide->title }}</h3>
                                    <span class="onb-card__tag"><x-ukc.icon :name="$categories[$category]['icon'] ?? 'book'" :size="12" /> {{ $categories[$category]['label'] ?? 'Guide' }}</span>
                                </div>
                            </header>
                            <p class="onb-card__intro">{{ $guide->intro }}</p>
                            <div class="onb-card__course"><span><x-ukc.icon name="list" :size="13" /> {{ count($guide->steps ?: []) }} étapes</span>@if ($hasVisibleVideo)<span><x-ukc.icon name="video" :size="13" /> Vidéo disponible</span>@endif</div>
                            <details class="onb-guide-details" @if($loop->first) open @endif>
                                <summary>Découvrir les étapes <x-ukc.icon name="chevron" :size="15" /></summary>
                                <ol class="onb-steps">@foreach ($guide->steps ?: [] as $step)<li>{{ $step }}</li>@endforeach</ol>
                            </details>
                            @if (isset($actionLinks[$guide->key]))
                                <footer class="onb-card__foot"><a class="ukc-btn ukc-btn--outline ukc-btn--sm" href="{{ $actionLinks[$guide->key][0] }}">{{ $actionLinks[$guide->key][1] }} <x-ukc.icon name="chevron" :size="15" /></a></footer>
                            @endif
                        </div>

                        @if ($hasVisibleVideo)
                            <div class="onb-card__visual">
                                <div class="onb-card__visual-head"><span><x-ukc.icon name="video" :size="15" /> Tutoriel vidéo</span><small>À regarder quand vous le souhaitez</small></div>
                                <video controls preload="metadata" src="{{ $guide->videoUrl() }}" aria-label="Vidéo : {{ $guide->title }}"></video>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.participant>
