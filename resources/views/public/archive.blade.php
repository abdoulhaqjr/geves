<x-layouts.app :title="($edition->title ?: 'Édition') . ' — Archives UK Colloques'">
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
            @if ($edition->banner_url)
                <div class="bg" style="background-image:url('{{ $edition->banner_url }}');"></div>
            @endif
            <div class="ukc-container">
                <div class="chips">
                    @if ($edition->edition_label)<span class="chip">{{ $edition->edition_label }}</span>@endif
                    <span class="chip">{{ $edition->year ?: $edition->date_start?->format('Y') }}</span>
                    <span class="chip">Clôturé</span>
                </div>
                <h1>{{ $edition->title ?: $edition->colloque?->name }}</h1>
                @if ($edition->theme)<p class="theme-line">{{ $edition->theme }}</p>@endif
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
                        <p>{{ $description ?: 'Édition clôturée de l’Université de Kara, sous la coordination de la Direction de la Recherche et de l’Innovation (DRI).' }}</p>
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
                        @if ($edition->theme)<div class="row"><span>Thème</span><strong>{{ $edition->theme }}</strong></div>@endif
                        @if ($edition->location)<div class="row"><span>Lieu</span><strong>{{ $edition->location }}</strong></div>@endif
                        <div class="row"><span>Dates</span><strong>
                            @if ($edition->date_start && $edition->date_end)
                                {{ $edition->date_start->translatedFormat('d') }} – {{ $edition->date_end->translatedFormat('d F Y') }}
                            @else
                                {{ $edition->date_start?->translatedFormat('d F Y') ?? $edition->year }}
                            @endif
                        </strong></div>
                        <div class="row"><span>Type</span><strong>{{ $edition->colloque?->name ?: 'Colloque' }}</strong></div>
                        <div class="row"><span>Organisateur</span><strong>DRI · Université de Kara</strong></div>
                    </div>

                    <div class="card docs">
                        <h2>Documents</h2>
                        @forelse ($documents as $doc)
                            <a href="{{ $doc['url'] }}" target="_blank" rel="noopener">{{ $doc['label'] }} <span>↓</span></a>
                        @empty
                            <p style="font-size:13.5px;color:var(--ukc-muted);margin:0;">Aucun document public pour cette édition.</p>
                        @endforelse
                        <a href="mailto:dri@univkara.tg">Demander un document <span>→</span></a>
                    </div>

                    <div class="card">
                        <a href="{{ route('public.archives') }}" class="ukc-btn ukc-btn--outline" style="width:100%;">← Toutes les éditions</a>
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
