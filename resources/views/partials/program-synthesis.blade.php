{{--
    Présentation SYNTHÉTIQUE du programme (onglets par jour + séances), partagée
    par la page publique du colloque (#programme) ET l'onglet « Programme » du
    module admin - afin que l'admin voie EXACTEMENT ce qui est publié.

    Données attendues :
      - $plenaries : collection des séances plénières (type = plenary)
      - $ateliers  : collection des ateliers (chacun avec ->sessions)

    ⚠️ Charte UK (classes de public.css, non scopées → rendent aussi sous
    .ukc-shell). Le public NE montre PAS le détail des séances (pas de
    communications ni de modérateurs/rapporteurs) : heure + type + atelier +
    « En parallèle » + libellé + salle. Ne pas réintroduire $commsBySession ni
    les rôles ici (cf. CLAUDE.md / ProgramPublicTest).
--}}
@php
    $atelierNames = $ateliers->pluck('title', 'id');
    $allSessions = $plenaries->concat($ateliers->flatMap->sessions)
        ->sortBy(fn ($s) => optional($s->starts_at)?->timestamp ?? 0)
        ->values();
    // Regroupe par jour ; les séances non encore datées vont dans un bucket « » affiché en dernier.
    $byDay = $allSessions->groupBy(fn ($s) => optional($s->session_day ?? $s->starts_at)?->format('Y-m-d') ?? '');
    $progDays = $byDay->keys()->sortBy(fn ($d) => $d === '' ? '9999' : $d)->values();
@endphp
@if ($progDays->isNotEmpty())
    <div x-data="{ progDay: '{{ $progDays->first() }}' }">
        <div class="ukc-tabs-scroll" role="tablist">
            @php $dayNo = 0; @endphp
            @foreach ($progDays as $d)
                <button type="button" class="ukc-tab" :class="{ active: progDay==='{{ $d }}' }" @click="progDay='{{ $d }}'" role="tab">@if ($d === ''){{ __('À programmer') }}@else{{ __('Jour :n - :date', ['n' => ++$dayNo, 'date' => \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M')]) }}@endif</button>
            @endforeach
        </div>

        @foreach ($progDays as $d)
            @php
                // Ateliers simultanés : horaires de début partagés par plusieurs
                // séances dans la journée (affichés « en parallèle »).
                $parallelStarts = $byDay[$d]->filter(fn ($s) => $s->starts_at)
                    ->groupBy(fn ($s) => $s->starts_at->format('H:i'))
                    ->filter(fn ($grp) => $grp->count() > 1)->keys();
            @endphp
            <div class="ukc-program-day" :class="{ active: progDay==='{{ $d }}' }">
                @foreach ($byDay[$d] as $s)
                    @php
                        $isPlenary = $s->type === 'plenary';
                        $isParallel = ! $isPlenary && $s->starts_at && $parallelStarts->contains($s->starts_at->format('H:i'));
                        $atelierTitle = $s->atelier_id ? ($atelierNames[$s->atelier_id] ?? null) : null;
                    @endphp
                    <div class="ukc-session">
                        <div class="ukc-session-time">{{ $s->starts_at?->format('H:i') ?: '-' }}@if ($s->ends_at)<br><span style="font-weight:600;color:var(--ukc-light);font-size:12px;">{{ $s->ends_at->format('H:i') }}</span>@endif</div>
                        <div class="ukc-session-body">
                            <span class="type {{ $isPlenary ? 'type--plenary' : 'type--workshop' }}">{{ $isPlenary ? __('Plénière') : __('Atelier') }}</span>
                            @if ($atelierTitle)<span class="type type--poster">{{ $atelierTitle }}</span>@endif
                            @if ($isParallel)<span class="type type--parallel" title="{{ __('Se déroule en même temps que d’autres ateliers') }}">{{ __('En parallèle') }}</span>@endif
                            <h4>{{ $s->label }}</h4>
                            @if ($s->room)
                                <div class="meta">{{ $s->room }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@else
    <p style="text-align:center;color:var(--ukc-muted);padding:24px 0;">{{ __('Aucune séance planifiée pour le moment.') }}</p>
@endif
