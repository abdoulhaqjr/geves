<x-layouts.app :title="__('Déroulement en direct - :app', ['app' => config('app.name')])">
    @include('partials.public-header')

    @if (! $event)
        <section class="ukc-section">
            <div class="ukc-container">
                <div class="ukc-empty">
                    <h3>{{ __('Aucun événement en cours actuellement') }}</h3>
                    <p>{{ __('Le déroulement en direct s’affichera ici pendant les journées d’un événement. Consultez les événements à venir ou passés.') }}</p>
                    @if ($navHasUpcoming ?? false)
                        <a href="{{ route('home') }}#a-venir" class="ukc-btn ukc-btn--blue">{{ __('Événements à venir') }}</a>
                    @endif
                    <a href="{{ route('public.archives') }}" class="ukc-btn ukc-btn--outline" style="margin-left:8px;">{{ __('Archives') }}</a>
                </div>
            </div>
        </section>
        @include('partials.public-footer')
    @else
        @php
            $dates = $event->dateRangeLabel() ?? (string) $event->year;
            $dayNo = $event->date_start ? (int) floor($event->date_start->diffInDays($now)) + 1 : null;
            $iconPin = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/></svg>';
            $iconCal = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>';
            $iconClock = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>';
        @endphp

        <div class="deroulement" x-data="{ modal: null }" @keydown.escape.window="modal = null">
            {{-- ===== Hero live ===== --}}
            <section class="live-hero {{ $event->banner_url ? 'has-bg' : '' }}"
                     @if ($event->banner_url) style="--x:0" @endif>
                @if ($event->banner_url)
                    <style>.deroulement .live-hero.has-bg::before{background-image:url('{{ $event->banner_url }}');}</style>
                @endif
                <div class="ukc-container">
                    <div class="top">
                        <span class="ukc-pill ukc-pill--live">● {{ __('En cours') }}</span>
                        @if ($event->event_label)
                            <span class="ukc-pill" style="background:rgba(255,255,255,.15);color:#fff;">{{ $event->event_label }}</span>
                        @endif
                    </div>
                    <h1>{{ $event->title ?: $event->series?->name }}</h1>
                    @if ($event->theme)<p class="sub">{{ $event->tr('theme') }} - {{ __('suivez le programme, les ateliers en cours et les annonces.') }}</p>@endif
                    <div class="live-meta">
                        @if ($event->location)<div>{!! $iconPin !!} {{ $event->tr('location') }}</div>@endif
                        <div>{!! $iconCal !!} {{ $dates }}</div>
                        @if ($dayNo && $dayNo >= 1)<div>{!! $iconClock !!} {{ __('Jour :n - en direct', ['n' => $dayNo]) }}</div>@endif
                    </div>
                    <div class="live-actions">
                        <button type="button" class="ukc-btn ukc-btn--primary" @click="modal = 'programme'">{{ __('Programme du jour') }}</button>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal = 'infos'">{{ __('Infos de l’événement') }}</button>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal = 'indications'">{{ __('Indications') }}</button>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal = 'annonces'">{{ __('Annonces') }}</button>
                    </div>
                </div>
            </section>

            {{-- ===== Corps ===== --}}
            <div class="page-body">
                <div class="ukc-container">
                    <div class="layout">
                        <div>
                            <div class="ukc-card">
                                <h2>{{ __('En ce moment - par atelier') }} <span class="ukc-pill ukc-pill--live">● Live</span></h2>
                                @if ($liveNow->isEmpty())
                                    <div class="ukc-empty" style="padding:24px 12px;">
                                        <p style="margin:0;">{{ __('Aucune session en cours en ce moment. Consultez le programme du jour pour les prochains créneaux.') }}</p>
                                        <button type="button" class="ukc-btn ukc-btn--outline" style="margin-top:14px;" @click="modal = 'programme'">{{ __('Voir le programme') }}</button>
                                    </div>
                                @else
                                    <p style="font-size:13.5px;color:var(--ukc-muted);margin:-6px 0 14px;">{!! __('Cliquez <strong>Rejoindre</strong> pour accéder à la salle de visioconférence de l’atelier.') !!}</p>
                                    <div class="atelier-grid">
                                        @foreach ($liveNow as $s)
                                            <div class="atelier-card live">
                                                <div class="top">
                                                    <span class="ukc-pill ukc-pill--live">● {{ __('En cours') }}</span>
                                                    @if ($s->isLive())
                                                        <span class="ukc-pill ukc-pill--visio">{{ __('Visio ON') }}</span>
                                                    @else
                                                        <span class="ukc-pill ukc-pill--muted">{{ __('Présentiel') }}</span>
                                                    @endif
                                                </div>
                                                <h3>{{ $s->atelier?->title ?: ($s->type === 'plenary' ? __('Plénière') : __('Atelier')) }}</h3>
                                                <div class="session-title">{{ $s->label }}</div>
                                                <div class="meta">
                                                    {{-- ⚠️ `room` NOMME déjà le lieu (« Salle 5 », mais aussi « Auditorium ») :
                                                         préfixer donnait « Salle : Salle 5 » et « Salle : Auditorium ». --}}
                                                    @if ($s->room)<span>{{ $s->room }}</span>@endif
                                                    @if ($s->starts_at && $s->ends_at)<span>{{ $s->starts_at->format('H:i') }} - {{ $s->ends_at->format('H:i') }}</span>@endif
                                                    @if ($s->moderator_name)<span>{{ __('Modération :') }} {{ $s->moderator_name }}</span>@endif
                                                </div>
                                                <div class="foot">
                                                    @if ($s->isLive() && $s->meetingEmbeddable())
                                                        <a href="{{ route('session.meeting', $s) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">{{ __('Rejoindre') }}</a>
                                                    @else
                                                        <span class="hint" style="font-size:12px;color:var(--ukc-muted);">{{ __('Présentiel uniquement') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <aside>
                            <div class="ukc-card">
                                <h2>{{ __('Dernières annonces') }}
                                    @if (! empty($announcements))
                                        <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="modal = 'annonces'">{{ __('Tout voir') }}</button>
                                    @endif
                                </h2>
                                @forelse (array_slice($announcements, 0, 3) as $a)
                                    <div class="annonce {{ ($a['urgent'] ?? false) ? 'urgent' : '' }}">
                                        @if (! empty($a['when']))<div class="when">{{ $a['when'] }}</div>@endif
                                        <strong>{{ $a['title'] ?? '' }}</strong>
                                        <span class="desc">{{ $a['text'] ?? '' }}</span>
                                    </div>
                                @empty
                                    <p style="font-size:13px;color:var(--ukc-muted);margin:0;">{{ __('Aucune annonce pour l’instant.') }}</p>
                                @endforelse
                            </div>
                            <div class="ukc-card">
                                <h2>{{ __('Légende') }}</h2>
                                <div style="font-size:13px;display:flex;flex-direction:column;gap:8px;">
                                    <div><span class="ukc-pill ukc-pill--live">● {{ __('En cours') }}</span> {{ __('Session active') }}</div>
                                    <div><span class="ukc-pill ukc-pill--visio">{{ __('Visio ON') }}</span> {{ __('Salle accessible') }}</div>
                                    <div><span class="ukc-pill ukc-pill--muted">{{ __('Présentiel') }}</span> {{ __('En salle uniquement') }}</div>
                                </div>
                            </div>
                            <div class="ukc-card">
                                <a href="{{ route('account.dashboard') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="width:100%;">{{ __('Espace participant') }}</a>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>

            {{-- ===== Modale Programme ===== --}}
            @php
                $currentDayIndex = array_search($now->format('Y-m-d'), $byDay->keys()->all());
                $currentDayIndex = $currentDayIndex === false ? 0 : $currentDayIndex;
            @endphp
            <div class="modal-overlay" x-cloak x-show="modal === 'programme'" x-transition @click.self="modal = null">
                <div class="modal modal--lg" x-data="{ day: {{ $currentDayIndex }} }">
                    <div class="modal-head">
                        <div><h2>{{ __('Programme de l’événement') }}</h2><div class="sub">{{ $event->title ?: $event->series?->name }} - {{ $dates }}</div></div>
                        <button type="button" class="modal-close" @click="modal = null">×</button>
                    </div>
                    <div class="modal-body">
                        @if ($byDay->isEmpty())
                            <p style="color:var(--ukc-muted);">{{ __('Le programme détaillé sera publié prochainement.') }}</p>
                        @else
                            <div class="day-tabs">
                                @foreach ($byDay as $date => $daySessions)
                                    <button type="button" :class="day === {{ $loop->index }} ? 'active' : ''" @click="day = {{ $loop->index }}">
                                        {{ __('Jour') }} {{ $loop->iteration }}@if ($date) - {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M') }}@endif
                                    </button>
                                @endforeach
                            </div>
                            @foreach ($byDay as $date => $daySessions)
                                @php
                                    // Ateliers simultanés : mêmes heures de début (affichés « en parallèle »).
                                    $parallelStarts = $daySessions->filter(fn ($s) => $s->starts_at)
                                        ->groupBy(fn ($s) => $s->starts_at->format('H:i'))
                                        ->filter(fn ($grp) => $grp->count() > 1)->keys();
                                @endphp
                                <div x-show="day === {{ $loop->index }}">
                                    @foreach ($daySessions as $s)
                                        <div class="session {{ ($isNow)($s) ? 'now' : '' }}">
                                            <span class="time">{{ $s->starts_at?->format('H:i') ?? '-' }}@if ($s->ends_at) - {{ $s->ends_at->format('H:i') }}@endif</span>
                                            <h3>{{ $s->label }}</h3>
                                            <div class="meta">
                                                {{ $s->room ?: __('Lieu à préciser') }}
                                                @if (($isNow)($s)) - {{ __('En cours') }} @elseif ($s->ends_at && $s->ends_at->lt($now)) - {{ __('Terminé') }} @else - {{ __('À venir') }} @endif
                                                @if ($s->type !== 'plenary' && $s->starts_at && $parallelStarts->contains($s->starts_at->format('H:i'))) - {{ __('En parallèle') }} @endif
                                                @if ($s->isLive()) - {{ __('Visio') }} @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{-- ===== Modale Infos ===== --}}
            <div class="modal-overlay" x-cloak x-show="modal === 'infos'" x-transition @click.self="modal = null">
                <div class="modal">
                    <div class="modal-head">
                        <div><h2>{{ __('Informations de l’événement') }}</h2><div class="sub">{{ $event->title ?: $event->series?->name }}</div></div>
                        <button type="button" class="modal-close" @click="modal = null">×</button>
                    </div>
                    <div class="modal-body">
                        @php($set = $event->settings ?? [])
                        <div class="info-grid">
                            @if ($event->theme)<div class="info-item"><strong>{{ __('Thème') }}</strong>{{ $event->tr('theme') }}</div>@endif
                            <div class="info-item"><strong>{{ __('Dates') }}</strong>{{ $dates }}</div>
                            @if ($event->location)<div class="info-item"><strong>{{ __('Lieu') }}</strong>{{ $event->tr('location') }}</div>@endif
                            <div class="info-item"><strong>{{ __('Organisateurs') }}</strong>{{ $set['organisers'] ?? ($event->series?->name.' - DRI - Université de Kara') }}</div>
                            @if (! empty($set['formats']))<div class="info-item"><strong>{{ __('Formats') }}</strong>{{ $set['formats'] }}</div>@endif
                            @if (! empty($set['visio']))<div class="info-item"><strong>{{ __('Visio') }}</strong>{{ $set['visio'] }}</div>@endif
                        </div>
                        <p style="margin-top:16px;font-size:14px;color:var(--ukc-muted);line-height:1.55;">
                            {{ $set['infos_note'] ?? __('Les participants disposent d’un badge et de documents dans l’espace participant. Les communicateurs rejoignent la salle de leur atelier pour présenter lorsque la visio est activée.') }}
                        </p>
                        <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px;">
                            @if ($event->slug)<a href="{{ route('public.colloque', $event->slug) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">{{ __('Page officielle') }}</a>@endif
                            <a href="{{ route('account.dashboard') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">{{ __('Espace participant') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Modale Indications ===== --}}
            <div class="modal-overlay" x-cloak x-show="modal === 'indications'" x-transition @click.self="modal = null">
                <div class="modal modal--xl">
                    <div class="modal-head">
                        <div><h2>{{ __('Indications - salles & lieux') }}</h2><div class="sub">{{ __('Où se déroulent les plénières, ateliers et sessions') }}</div></div>
                        <button type="button" class="modal-close" @click="modal = null">×</button>
                    </div>
                    <div class="modal-body">
                        @php($guide = $event->settings['venue_guide'] ?? [])
                        @if (! empty($guide))
                            <p style="font-size:13.5px;color:var(--ukc-muted);margin-bottom:14px;line-height:1.5;">
                                {{ $event->settings['venue_intro'] ?? __('Plan de repérage sur le campus. En cas de changement de salle, une annonce est publiée en temps réel.') }}
                            </p>
                            <div style="overflow-x:auto;">
                                <table class="ind-table">
                                    <thead><tr><th>{{ __('Type') }}</th><th>{{ __('Session / atelier') }}</th><th>{{ __('Salle / lieu') }}</th><th>{{ __('Repère') }}</th></tr></thead>
                                    <tbody>
                                        @foreach ($guide as $row)
                                            <tr>
                                                <td><span class="type">{{ $row['type'] ?? '' }}</span></td>
                                                <td>{{ $row['sessions'] ?? '' }}</td>
                                                <td class="room">{{ $row['room'] ?? '' }}</td>
                                                <td>{{ $row['landmark'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p style="margin-top:14px;font-size:12.5px;color:var(--ukc-muted);">
                                {{ $event->settings['venue_note'] ?? __('Les sessions en visioconférence indiquent « Visio ON » sur la carte atelier ; le lieu présentiel reste la salle listée ci-dessus.') }}
                            </p>
                        @elseif ($rooms->isEmpty())
                            <p style="color:var(--ukc-muted);">{{ __('Les salles seront précisées avec le programme.') }}</p>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="ind-table">
                                    <thead><tr><th>{{ __('Salle / lieu') }}</th><th>{{ __('Sessions') }}</th><th>{{ __('Type') }}</th></tr></thead>
                                    <tbody>
                                        @foreach ($rooms as $room => $roomSessions)
                                            <tr>
                                                <td class="room">{{ $room }}</td>
                                                <td>{{ $roomSessions->pluck('label')->filter()->take(3)->implode(', ') }}</td>
                                                <td><span class="type">{{ $roomSessions->contains('type', 'plenary') ? __('Plénière / atelier') : __('Atelier') }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ===== Modale Annonces ===== --}}
            <div class="modal-overlay" x-cloak x-show="modal === 'annonces'" x-transition @click.self="modal = null">
                <div class="modal">
                    <div class="modal-head">
                        <div><h2>{{ __('Annonces & indications') }}</h2><div class="sub">{{ __('Changements de salle, horaires, consignes') }}</div></div>
                        <button type="button" class="modal-close" @click="modal = null">×</button>
                    </div>
                    <div class="modal-body">
                        @forelse ($announcements as $a)
                            @php($urgent = $a['urgent'] ?? false)
                            <div class="annonce {{ $urgent ? 'urgent' : '' }}">
                                @if (! empty($a['when']) || $urgent)<div class="when">{{ $a['when'] ?? '' }}@if ($urgent) - {{ __('Urgent') }} @endif</div>@endif
                                <strong>{{ $a['title'] ?? '' }}</strong>
                                <span class="desc">{{ $a['text'] ?? '' }}</span>
                            </div>
                        @empty
                            <p style="color:var(--ukc-muted);margin:0;">{{ __('Aucune annonce publiée pour cet événement.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        @include('partials.public-footer')
    @endif
</x-layouts.app>
