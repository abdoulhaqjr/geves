@php
    use App\Support\TutorialGuideCatalog;

    $categories = TutorialGuideCatalog::categories();
    $counts = $guides->countBy(fn ($guide) => TutorialGuideCatalog::categoryFor($guide->key));
    $videosVisible = $guides->filter(fn ($guide) => $guide->hasVideo() && $guide->video_visible_to_participants)->count();
    $actionLinks = [
        'fonctionnement' => [route('account.dashboard'), 'Ouvrir le tableau de bord'],
        'soumission' => [route('account.submissions.index'), 'Voir mes soumissions'],
        'rapport' => [route('account.reports'), 'Ouvrir mes rapports'],
        'moderation' => [route('account.moderation'), 'Ouvrir mes séances'],
        'chat-public' => [route('public.gesco'), 'Ouvrir le chatbot public'],
    ];
@endphp

<x-layouts.participant title="Prise en main" subtitle="Guides adaptés à vos accès et à votre rôle">
    <x-slot:actions><a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('account.dashboard') }}"><x-ukc.icon name="home" :size="14" /> Tableau de bord</a></x-slot:actions>

    <div class="account-page ukc-onb" x-data="{ activeCategory: 'all' }">
        <div class="ukc-stats onb-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="book" :size="20" /></div><div class="lbl">Guides disponibles</div><div class="val blue">{{ $guides->count() }}</div><div class="hint">Selon vos fonctions</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="calendar" :size="20" /></div><div class="lbl">Contexte actif</div><div class="val onb-stat-text">{{ $event ? 'Colloque' : 'Général' }}</div><div class="hint">{{ $event?->title ?: 'Guides communs à la plateforme' }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="video" :size="20" /></div><div class="lbl">Vidéos disponibles</div><div class="val green">{{ $videosVisible }}</div><div class="hint">Dans vos guides</div></div>
        </div>

        <div class="onb-note">
            <span class="onb-note__icon"><x-ukc.icon name="map" :size="18" /></span>
            <div><strong>Votre parcours de prise en main</strong><p>{{ $event ? 'Ces guides correspondent à vos rôles dans le colloque en cours. Changez de colloque depuis le menu pour afficher le parcours associé.' : 'Vous consultez les guides communs à la plateforme. Sélectionnez un colloque depuis le menu pour afficher ceux liés à vos rôles.' }}</p></div>
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
            <div class="onb-grid">
                @foreach ($guides as $guide)
                    @php($category = TutorialGuideCatalog::categoryFor($guide->key))
                    <article class="ukc-card onb-card" x-show="activeCategory === 'all' || activeCategory === '{{ $category }}'">
                        <header class="onb-card__head">
                            <span class="onb-card__icon"><x-ukc.icon :name="TutorialGuideCatalog::iconFor($guide->key)" :size="21" /></span>
                            <div class="onb-card__titles">
                                <h3 class="onb-card__title">{{ $guide->title }}</h3>
                                <span class="onb-card__tag"><x-ukc.icon :name="$categories[$category]['icon'] ?? 'book'" :size="12" /> {{ $categories[$category]['label'] ?? 'Guide' }}</span>
                            </div>
                        </header>
                        <p class="onb-card__intro">{{ $guide->intro }}</p>
                        <ol class="onb-steps">@foreach ($guide->steps ?: [] as $step)<li>{{ $step }}</li>@endforeach</ol>

                        @if ($guide->hasVideo() && $guide->video_visible_to_participants)
                            <div class="onb-media">
                                <video controls preload="metadata" src="{{ $guide->videoUrl() }}"></video>
                                <div class="onb-media__meta"><span class="onb-media__ico"><x-ukc.icon name="check" :size="15" /></span><div><strong>Vidéo de démonstration</strong><small>Disponible pour votre parcours</small></div></div>
                            </div>
                        @endif

                        @if (isset($actionLinks[$guide->key]))
                            <footer class="onb-card__foot"><a class="ukc-btn ukc-btn--outline ukc-btn--sm" href="{{ $actionLinks[$guide->key][0] }}">{{ $actionLinks[$guide->key][1] }} <x-ukc.icon name="chevron" :size="15" /></a></footer>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.participant>
