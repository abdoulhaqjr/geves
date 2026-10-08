@php
    use App\Support\TutorialGuideCatalog;

    $categories = TutorialGuideCatalog::categories();
    $counts = $guides->countBy(fn ($guide) => TutorialGuideCatalog::categoryFor($guide->key));
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

    <div class="account-page account-onboarding" x-data="{ activeCategory: 'all' }">
        <div class="ukc-stats onboarding-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="book" :size="20" /></div><div class="lbl">Guides disponibles</div><div class="val blue">{{ $guides->count() }}</div><div class="hint">Selon vos fonctions</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="calendar" :size="20" /></div><div class="lbl">Contexte actif</div><div class="val account-onboarding__event">{{ $event ? 'Colloque' : 'Général' }}</div><div class="hint">{{ $event?->title ?: 'Guides communs à la plateforme' }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="video" :size="20" /></div><div class="lbl">Vidéos disponibles</div><div class="val green">{{ $guides->filter(fn ($guide) => $guide->hasVideo() && $guide->video_visible_to_participants)->count() }}</div><div class="hint">Dans vos guides</div></div>
        </div>

        <section class="ukc-card onboarding-intro"><div class="onboarding-intro__icon"><x-ukc.icon name="book" :size="22" /></div><div><h2>Votre parcours de prise en main</h2><p>{{ $event ? 'Les guides affichés correspondent à vos rôles dans le colloque en cours. Changez de colloque depuis le menu pour voir le parcours associé.' : 'Vous consultez les guides communs à la plateforme. Sélectionnez un colloque depuis le menu pour afficher les guides liés à vos rôles.' }}</p></div></section>

        @if ($guides->isEmpty())
            <section class="ukc-card account-onboarding__empty"><x-ukc.icon name="book" :size="24" /><h2>Aucun guide pour le moment</h2><p>Sélectionnez un colloque ou rejoignez un événement pour accéder aux guides liés à vos fonctions.</p></section>
        @else
            <div class="ukc-nav-docs" aria-label="Catégories de guides">
                <button type="button" :class="{ active: activeCategory === 'all' }" @click="activeCategory = 'all'">Tous <span class="nav-count">{{ $guides->count() }}</span></button>
                @foreach ($categories as $key => $category)
                    @if (($counts[$key] ?? 0) > 0)<button type="button" :class="{ active: activeCategory === '{{ $key }}' }" @click="activeCategory = '{{ $key }}'">{{ $category['label'] }} <span class="nav-count">{{ $counts[$key] }}</span></button>@endif
                @endforeach
            </div>
            <div class="onboarding-guide-grid">
                @foreach ($guides as $guide)
                    <article class="ukc-card onboarding-guide" x-show="activeCategory === 'all' || activeCategory === '{{ TutorialGuideCatalog::categoryFor($guide->key) }}'">
                        <header class="onboarding-guide__head"><span class="onboarding-guide__icon"><x-ukc.icon :name="TutorialGuideCatalog::iconFor($guide->key)" :size="20" /></span><div><span class="onboarding-guide__number">Guide {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h2>{{ $guide->title }}</h2></div></header>
                        <p class="onboarding-guide__intro">{{ $guide->intro }}</p>
                        <ol class="onboarding-guide__steps">@foreach ($guide->steps ?: [] as $step)<li>{{ $step }}</li>@endforeach</ol>
                        @if ($guide->hasVideo() && $guide->video_visible_to_participants)
                            <div class="onboarding-video onboarding-video--ready"><video controls preload="metadata" src="{{ $guide->videoUrl() }}"></video><div><strong>Vidéo de démonstration</strong><small>Disponible pour votre parcours</small></div></div>
                        @endif
                        <footer>@if (isset($actionLinks[$guide->key]))<a class="ukc-btn ukc-btn--outline ukc-btn--sm" href="{{ $actionLinks[$guide->key][0] }}">{{ $actionLinks[$guide->key][1] }} <x-ukc.icon name="chevron" :size="15" /></a>@endif</footer>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.participant>
