<x-layouts.app title="Déroulement en direct — UK Colloques">
    @include('partials.public-header')

    @if (! $edition)
        <section class="ukc-section">
            <div class="ukc-container">
                <div class="ukc-empty">
                    <h3>Aucun colloque en cours actuellement</h3>
                    <p>Le déroulement en direct s’affichera ici pendant les journées d’un colloque. Consultez les éditions à venir ou passées.</p>
                    <a href="{{ route('home') }}#a-venir" class="ukc-btn ukc-btn--blue">Colloques à venir</a>
                    <a href="{{ route('public.archives') }}" class="ukc-btn ukc-btn--outline" style="margin-left:8px;">Archives</a>
                </div>
            </div>
        </section>
        @include('partials.public-footer')
    @else
        @php
            $dates = $edition->date_start && $edition->date_end
                ? $edition->date_start->translatedFormat('d').' – '.$edition->date_end->translatedFormat('d F Y')
                : ($edition->date_start?->translatedFormat('d F Y') ?? (string) $edition->year);
            $dayNo = $edition->date_start ? (int) floor($edition->date_start->diffInDays($now)) + 1 : null;
            $iconPin = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/></svg>';
            $iconCal = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>';
            $iconClock = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>';
        @endphp

        <div class="deroulement" x-data="{ modal: null }" @keydown.escape.window="modal = null">
            {{-- ===== Hero live ===== --}}
            <section class="live-hero {{ $edition->banner_url ? 'has-bg' : '' }}"
                     @if ($edition->banner_url) style="--x:0" @endif>
                @if ($edition->banner_url)
                    <style>.deroulement .live-hero.has-bg::before{background-image:url('{{ $edition->banner_url }}');}</style>
                @endif
                <div class="ukc-container">
                    <div class="top">
                        <span class="ukc-pill ukc-pill--live">● En cours</span>
                        @if ($edition->edition_label)
                            <span class="ukc-pill" style="background:rgba(255,255,255,.15);color:#fff;">{{ $edition->edition_label }}</span>
                        @endif
                    </div>
                    <h1>{{ $edition->title ?: $edition->colloque?->name }}</h1>
                    @if ($edition->theme)<p class="sub">{{ $edition->theme }} — suivez le programme, les ateliers en cours et les annonces.</p>@endif
                    <div class="live-meta">
                        @if ($edition->location)<div>{!! $iconPin !!} {{ $edition->location }}</div>@endif
                        <div>{!! $iconCal !!} {{ $dates }}</div>
                        @if ($dayNo && $dayNo >= 1)<div>{!! $iconClock !!} Jour {{ $dayNo }} · en direct</div>@endif
                    </div>
                    <div class="live-actions">
                        <button type="button" class="ukc-btn ukc-btn--primary" @click="modal = 'programme'">Programme du jour</button>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal = 'infos'">Infos du colloque</button>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal = 'indications'">Indications</button>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal = 'annonces'">Annonces</button>
                    </div>
                </div>
            </section>

            {{-- ===== Corps ===== --}}
            <div class="page-body">
                <div class="ukc-container">
                    <div class="layout">
                        <div>
                            <div class="ukc-card">
                                <h2>En ce moment — par atelier <span class="ukc-pill ukc-pill--live">● Live</span></h2>
                                @if ($liveNow->isEmpty())
                                    <div class="ukc-empty" style="padding:24px 12px;">
                                        <p style="margin:0;">Aucune session en cours en ce moment. Consultez le programme du jour pour les prochains créneaux.</p>
                                        <button type="button" class="ukc-btn ukc-btn--outline" style="margin-top:14px;" @click="modal = 'programme'">Voir le programme</button>
                                    </div>
                                @else
                                    <p style="font-size:13.5px;color:var(--ukc-muted);margin:-6px 0 14px;">Cliquez <strong>Rejoindre</strong> pour accéder à la salle de visioconférence de l’atelier.</p>
                                    <div class="atelier-grid">
                                        @foreach ($liveNow as $s)
                                            <div class="atelier-card live">
                                                <div class="top">
                                                    <span class="ukc-pill ukc-pill--live">● En cours</span>
                                                    @if ($s->isLive())
                                                        <span class="ukc-pill ukc-pill--visio">Visio ON</span>
                                                    @else
                                                        <span class="ukc-pill ukc-pill--muted">Présentiel</span>
                                                    @endif
                                                </div>
                                                <h3>{{ $s->atelier?->title ?: ($s->type === 'plenary' ? 'Plénière' : 'Atelier') }}</h3>
                                                <div class="session-title">{{ $s->label }}</div>
                                                <div class="meta">
                                                    @if ($s->room)<span>Salle : {{ $s->room }}</span>@endif
                                                    @if ($s->starts_at && $s->ends_at)<span>{{ $s->starts_at->format('H:i') }} – {{ $s->ends_at->format('H:i') }}</span>@endif
                                                    @if ($s->moderator_name)<span>Modération : {{ $s->moderator_name }}</span>@endif
                                                </div>
                                                <div class="foot">
                                                    @if ($s->isLive() && $s->meetingEmbeddable())
                                                        <a href="{{ route('session.meeting', $s) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">Rejoindre</a>
                                                    @else
                                                        <span class="hint" style="font-size:12px;color:var(--ukc-muted);">Présentiel uniquement</span>
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
                                <h2>Dernières annonces
                                    @if (! empty($announcements))
                                        <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="modal = 'annonces'">Tout voir</button>
                                    @endif
                                </h2>
                                @forelse (array_slice($announcements, 0, 3) as $a)
                                    <div class="annonce {{ ($a['urgent'] ?? false) ? 'urgent' : '' }}">
                                        @if (! empty($a['when']))<div class="when">{{ $a['when'] }}</div>@endif
                                        <strong>{{ $a['title'] ?? '' }}</strong>
                                        <span class="desc">{{ $a['text'] ?? '' }}</span>
                                    </div>
                                @empty
                                    <p style="font-size:13px;color:var(--ukc-muted);margin:0;">Aucune annonce pour l’instant.</p>
                                @endforelse
                            </div>
                            <div class="ukc-card">
                                <h2>Légende</h2>
                                <div style="font-size:13px;display:flex;flex-direction:column;gap:8px;">
                                    <div><span class="ukc-pill ukc-pill--live">● En cours</span> Session active</div>
                                    <div><span class="ukc-pill ukc-pill--visio">Visio ON</span> Salle accessible</div>
                                    <div><span class="ukc-pill ukc-pill--muted">Présentiel</span> En salle uniquement</div>
                                </div>
                            </div>
                            <div class="ukc-card">
                                <a href="{{ route('account.dashboard') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="width:100%;">Espace participant</a>
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
                        <div><h2>Programme du colloque</h2><div class="sub">{{ $edition->title ?: $edition->colloque?->name }} · {{ $dates }}</div></div>
                        <button type="button" class="modal-close" @click="modal = null">×</button>
                    </div>
                    <div class="modal-body">
                        @if ($byDay->isEmpty())
                            <p style="color:var(--ukc-muted);">Le programme détaillé sera publié prochainement.</p>
                        @else
                            <div class="day-tabs">
                                @foreach ($byDay as $date => $daySessions)
                                    <button type="button" :class="day === {{ $loop->index }} ? 'active' : ''" @click="day = {{ $loop->index }}">
                                        Jour {{ $loop->iteration }}@if ($date) · {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M') }}@endif
                                    </button>
                                @endforeach
                            </div>
                            @foreach ($byDay as $date => $daySessions)
                                <div x-show="day === {{ $loop->index }}">
                                    @foreach ($daySessions as $s)
                                        <div class="session {{ ($isNow)($s) ? 'now' : '' }}">
                                            <span class="time">{{ $s->starts_at?->format('H:i') ?? '—' }}@if ($s->ends_at) – {{ $s->ends_at->format('H:i') }}@endif</span>
                                            <h3>{{ $s->label }}</h3>
                                            <div class="meta">
                                                {{ $s->room ?: 'Lieu à préciser' }}
                                                @if (($isNow)($s)) · En cours @elseif ($s->ends_at && $s->ends_at->lt($now)) · Terminé @else · À venir @endif
                                                @if ($s->isLive()) · Visio {{ ucfirst($s->meeting_provider ?: 'en direct') }} @endif
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
                        <div><h2>Informations du colloque</h2><div class="sub">{{ $edition->title ?: $edition->colloque?->name }}</div></div>
                        <button type="button" class="modal-close" @click="modal = null">×</button>
                    </div>
                    <div class="modal-body">
                        @php($set = $edition->settings ?? [])
                        <div class="info-grid">
                            @if ($edition->theme)<div class="info-item"><strong>Thème</strong>{{ $edition->theme }}</div>@endif
                            <div class="info-item"><strong>Dates</strong>{{ $dates }}</div>
                            @if ($edition->location)<div class="info-item"><strong>Lieu</strong>{{ $edition->location }}</div>@endif
                            <div class="info-item"><strong>Organisateurs</strong>{{ $set['organisers'] ?? ($edition->colloque?->name.' · DRI · Université de Kara') }}</div>
                            @if (! empty($set['formats']))<div class="info-item"><strong>Formats</strong>{{ $set['formats'] }}</div>@endif
                            @if (! empty($set['visio']))<div class="info-item"><strong>Visio</strong>{{ $set['visio'] }}</div>@endif
                        </div>
                        <p style="margin-top:16px;font-size:14px;color:var(--ukc-muted);line-height:1.55;">
                            {{ $set['infos_note'] ?? 'Les participants disposent d’un badge et de documents dans l’espace participant. Les communicateurs rejoignent la salle de leur atelier pour présenter lorsque la visio est activée.' }}
                        </p>
                        <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px;">
                            @if ($edition->slug)<a href="{{ route('public.colloque', $edition->slug) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">Page officielle</a>@endif
                            <a href="{{ route('account.dashboard') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">Espace participant</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Modale Indications ===== --}}
            <div class="modal-overlay" x-cloak x-show="modal === 'indications'" x-transition @click.self="modal = null">
                <div class="modal modal--xl">
                    <div class="modal-head">
                        <div><h2>Indications — salles &amp; lieux</h2><div class="sub">Où se déroulent les plénières, ateliers et sessions</div></div>
                        <button type="button" class="modal-close" @click="modal = null">×</button>
                    </div>
                    <div class="modal-body">
                        @php($guide = $edition->settings['venue_guide'] ?? [])
                        @if (! empty($guide))
                            <p style="font-size:13.5px;color:var(--ukc-muted);margin-bottom:14px;line-height:1.5;">
                                {{ $edition->settings['venue_intro'] ?? 'Plan de repérage sur le campus. En cas de changement de salle, une annonce est publiée en temps réel.' }}
                            </p>
                            <div style="overflow-x:auto;">
                                <table class="ind-table">
                                    <thead><tr><th>Type</th><th>Session / atelier</th><th>Salle / lieu</th><th>Repère</th></tr></thead>
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
                                {{ $edition->settings['venue_note'] ?? 'Les sessions en visioconférence indiquent « Visio ON » sur la carte atelier ; le lieu présentiel reste la salle listée ci-dessus.' }}
                            </p>
                        @elseif ($rooms->isEmpty())
                            <p style="color:var(--ukc-muted);">Les salles seront précisées avec le programme.</p>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="ind-table">
                                    <thead><tr><th>Salle / lieu</th><th>Sessions</th><th>Type</th></tr></thead>
                                    <tbody>
                                        @foreach ($rooms as $room => $roomSessions)
                                            <tr>
                                                <td class="room">{{ $room }}</td>
                                                <td>{{ $roomSessions->pluck('label')->filter()->take(3)->implode(' · ') }}</td>
                                                <td><span class="type">{{ $roomSessions->contains('type', 'plenary') ? 'Plénière / atelier' : 'Atelier' }}</span></td>
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
                        <div><h2>Annonces &amp; indications</h2><div class="sub">Changements de salle, horaires, consignes</div></div>
                        <button type="button" class="modal-close" @click="modal = null">×</button>
                    </div>
                    <div class="modal-body">
                        @forelse ($announcements as $a)
                            @php($urgent = $a['urgent'] ?? false)
                            <div class="annonce {{ $urgent ? 'urgent' : '' }}">
                                @if (! empty($a['when']) || $urgent)<div class="when">{{ $a['when'] ?? '' }}@if ($urgent) · Urgent @endif</div>@endif
                                <strong>{{ $a['title'] ?? '' }}</strong>
                                <span class="desc">{{ $a['text'] ?? '' }}</span>
                            </div>
                        @empty
                            <p style="color:var(--ukc-muted);margin:0;">Aucune annonce publiée pour cette édition.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        @include('partials.public-footer')
    @endif
</x-layouts.app>
