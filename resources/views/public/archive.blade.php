<x-layouts.app :title="($event->title ?: 'Événement') . ' - Archives '.config('app.name')">
    @php
        // Un axe thématique peut être une chaîne ou un objet { name/title/label }.
        $axeLabel = function ($a) {
            if (is_array($a)) {
                return $a['name'] ?? $a['title'] ?? $a['label'] ?? reset($a);
            }
            return $a;
        };
    @endphp

    @include('partials.public-header')

    <div class="archive-detail">
        <section class="detail-hero">
            @if ($event->banner_url)
                <div class="bg" style="background-image:url('{{ $event->banner_url }}');"></div>
            @endif
            <div class="ukc-container">
                <div class="chips">
                    @if ($event->event_label)<span class="chip">{{ $event->event_label }}</span>@endif
                    <span class="chip">{{ $event->year ?: $event->date_start?->format('Y') }}</span>
                    <span class="chip">Clôturé</span>
                </div>
                <h1>{{ $event->title ?: $event->series?->name }}</h1>
                @if ($event->theme)<p class="theme-line">{{ $event->theme }}</p>@endif
            </div>
        </section>

        <section class="ukc-section">
            <div class="ukc-container layout">
                <div class="main">
                    <div class="stats">
                        <div class="stat"><strong>{{ $stats['communications'] }}</strong><span>Communications</span></div>
                        <div class="stat"><strong>{{ $stats['participants'] }}</strong><span>Participants</span></div>
                        <div class="stat"><strong>{{ $stats['sessions'] }}</strong><span>Sessions</span></div>
                    </div>

                    <div class="card" style="margin-bottom:18px;">
                        <h2>Présentation</h2>
                        <p>{{ $description ?: 'Événement clôturée de l’Université de Kara, sous la coordination de la Direction de la Recherche et de l’Innovation (DRI).' }}</p>
                        @if (! empty($highlights))
                            <ul>
                                @foreach ($highlights as $h)<li>{{ is_array($h) ? ($h['text'] ?? reset($h)) : $h }}</li>@endforeach
                            </ul>
                        @endif
                    </div>

                    @if (! empty($axes))
                        <div class="card" style="margin-bottom:18px;">
                            <h2>Axes thématiques</h2>
                            <ul>
                                @foreach ($axes as $a)<li>{{ $axeLabel($a) }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! empty($gallery))
                        <div class="card">
                            <h2>Galerie</h2>
                            @include('partials.gallery', ['media' => $gallery])
                        </div>
                    @endif
                </div>

                <aside class="side">
                    <div class="card">
                        <h2>Informations</h2>
                        @if ($event->theme)<div class="row"><span>Thème</span><strong>{{ $event->theme }}</strong></div>@endif
                        @if ($event->location)<div class="row"><span>Lieu</span><strong>{{ $event->location }}</strong></div>@endif
                        <div class="row"><span>Dates</span><strong>
                            @if ($event->date_start)
                                {{ $event->dateRangeLabel() }}
                            @else
                                {{ $event->year }}
                            @endif
                        </strong></div>
                        <div class="row"><span>Type</span><strong>{{ $event->series?->name ?: 'Colloque' }}</strong></div>
                        <div class="row"><span>Organisateur</span><strong>DRI · Université de Kara</strong></div>
                    </div>

                    <div class="card docs">
                        <h2>Documents</h2>
                        @forelse ($documents as $doc)
                            <a href="{{ $doc['url'] }}" target="_blank" rel="noopener">{{ $doc['label'] }} <span>↓</span></a>
                        @empty
                            <p style="font-size:13.5px;color:var(--ukc-muted);margin:0;">Aucun document public pour cette événement.</p>
                        @endforelse
                        <a href="mailto:dri@univkara.tg">Demander un document <span>→</span></a>
                    </div>

                    <div class="card">
                        <a href="{{ route('public.archives') }}" class="ukc-btn ukc-btn--outline" style="width:100%;">← Toutes les événements</a>
                    </div>
                </aside>
            </div>
        </section>
    </div>

    @include('partials.public-footer')

    @if (! empty($gallery))
        @include('partials.lightbox')
    @endif
</x-layouts.app>
