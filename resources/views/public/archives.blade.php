@use('App\Http\Controllers\Public\ArchiveController')

<x-layouts.app :title="__('Colloques passés - :app', ['app' => config('app.name')])">
    @include('partials.public-header')

    <section class="page-hero">
        <div class="ukc-container">
            <div class="eyebrow">{{ __('Archives scientifiques') }}</div>
            <h1>{{ __('Colloques passés') }}</h1>
            <p>{!! __('Mémoire des manifestations scientifiques organisées sous l’égide de l’Université de Kara. La plateforme :app est un service institutionnel de la <strong>Direction de la Recherche et de l’Innovation (DRI)</strong> - sans vocation commerciale.', ['app' => config('app.name')]) !!}</p>
            <div class="note">{{ __('Programmes · galeries · documents publics · actes') }}</div>
        </div>
    </section>

    <section class="ukc-section">
        <div class="ukc-container">
            <div class="toolbar-filter">
                <div>
                    <h2>{{ __('Événements archivées') }}</h2>
                    <p>{{ __('Cliquez sur une carte pour consulter le détail de l’événement.') }}</p>
                </div>
                @if ($events->count() > 1)
                    <div class="filters" id="filters">
                        <button type="button" class="active" data-filter="all">{{ __('Toutes') }}</button>
                        <button type="button" data-filter="colloque">{{ __('Colloques') }}</button>
                        <button type="button" data-filter="journee">{{ __('Journées / séminaires') }}</button>
                    </div>
                @endif
            </div>

            @if ($events->isEmpty())
                <div class="ukc-empty">
                    <h3>{{ __('Aucun événement archivé pour le moment') }}</h3>
                    <p>{{ __('Les événements clôturées apparaîtront ici avec leurs programmes, galeries et documents.') }}</p>
                </div>
            @else
                <div class="editions-grid" id="editionsGrid">
                    @foreach ($events as $e)
                        <a class="edition-card" href="{{ route('public.archive', $e->slug) }}" data-type="{{ ArchiveController::kind($e) }}">
                            <div class="cover {{ $e->banner_url ? '' : 'no-img' }}">
                                @if ($e->banner_url)<img src="{{ $e->banner_url }}" alt="{{ $e->title }}" loading="lazy">@endif
                                @if ($e->event_label)<span class="badge">{{ $e->event_label }}</span>@endif
                            </div>
                            <div class="body">
                                <div class="year-line">
                                    <span class="ukc-pill ukc-pill--closed">{{ __('Clôturé') }}</span>
                                </div>
                                <h3>{{ $e->title ?: $e->series?->name }}</h3>
                                @if ($e->theme)<p class="theme">{{ $e->theme }}</p>@endif
                                <div class="meta">
                                    <div class="meta-row">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>
                                        <span>
                                            @if ($e->date_start)
                                                {{ $e->dateRangeLabel() }}
                                            @else
                                                {{ $e->year }}
                                            @endif
                                        </span>
                                    </div>
                                    @if ($e->location)
                                        <div class="meta-row">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/></svg>
                                            <span>{{ $e->location }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="foot">
                                    <span class="cta">{{ __('Voir le détail →') }}</span>
                                    <span class="ukc-pill ukc-pill--info">{{ ArchiveController::kind($e) === 'journee' ? __('Journée') : __('Colloque') }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @include('partials.public-footer')
</x-layouts.app>
