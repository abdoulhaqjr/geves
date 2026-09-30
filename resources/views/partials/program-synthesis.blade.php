{{--
    Affichage du PROGRAMME SYNTHÉTIQUE saisi dans l'onglet « Programme » du module
    (edition.settings['program_synthesis']). Partagé par la page publique du
    colloque (#programme) ET l'aperçu de l'onglet admin - afin que l'admin voie
    EXACTEMENT ce qui est publié.

    Présentation en ONGLETS jour par jour (charte UK : .ukc-tabs-scroll +
    .ukc-program-day, non scopées → rendent aussi sous .ukc-shell).

    Donnée attendue : $synthesis = ['title' => string, 'days' => [
        ['date' => 'Y-m-d'|'', 'heading' => string, 'rows' => [
            ['start' => 'H:i'|'', 'end' => 'H:i'|'', 'type' => 'plenary|atelier|break|other',
             'label' => string, 'highlight' => bool],
        ]],
    ]]. Le TITRE est rendu par l'appelant (en-tête de section) ; ici, les onglets + le corps.
--}}
@php
    $hm = fn ($t) => trim((string) $t) === '' ? '' : str_replace(':', 'h', substr((string) $t, 0, 5));
    // Texte horaire d'une ligne (début - fin) ; repli sur un ancien champ `time` libre.
    $timeText = function (array $r) use ($hm) {
        $s = $hm($r['start'] ?? '');
        $e = $hm($r['end'] ?? '');
        if ($s === '' && $e === '') {
            return trim((string) ($r['time'] ?? ''));
        }

        return $e !== '' ? $s.' - '.$e : $s;
    };
    $synDays = collect($synthesis['days'] ?? [])->map(function ($d) {
        $rows = collect($d['rows'] ?? [])
            ->filter(fn ($r) => trim((string) ($r['label'] ?? '')) !== '' || trim((string) ($r['start'] ?? '')) !== '' || trim((string) ($r['time'] ?? '')) !== '')
            ->values();

        return ['date' => trim((string) ($d['date'] ?? '')), 'heading' => trim((string) ($d['heading'] ?? '')), 'rows' => $rows];
    })->filter(fn ($d) => $d['heading'] !== '' || $d['date'] !== '' || $d['rows']->isNotEmpty())->values();
@endphp
@if ($synDays->isNotEmpty())
    <div x-data="{ progDay: 0 }" class="ukc-prog-syn">
        <div class="ukc-tabs-scroll" role="tablist">
            @foreach ($synDays as $i => $day)
                @php
                    $tabLabel = $day['heading'] !== ''
                        ? $day['heading']
                        : ($day['date'] !== '' ? \Illuminate\Support\Carbon::parse($day['date'])->translatedFormat('D d M') : __('Jour :n', ['n' => $i + 1]));
                @endphp
                <button type="button" class="ukc-tab" :class="{ active: progDay==={{ $i }} }" @click="progDay={{ $i }}" role="tab">{{ $tabLabel }}</button>
            @endforeach
        </div>

        @foreach ($synDays as $i => $day)
            <div class="ukc-program-day" :class="{ active: progDay==={{ $i }} }">
                @if ($day['heading'] !== '' && $day['date'] !== '')
                    <div class="ukc-prog-syn__date">{{ \Illuminate\Support\Carbon::parse($day['date'])->translatedFormat('l d F Y') }}</div>
                @endif
                @if ($day['rows']->isNotEmpty())
                    <ul class="ukc-prog-syn__rows">
                        @foreach ($day['rows'] as $row)
                            @php $tt = $timeText($row); $isBreak = ($row['type'] ?? '') === 'break'; @endphp
                            <li @class(['ukc-prog-syn__row', 'is-strong' => ! empty($row['highlight']), 'is-break' => $isBreak])>
                                <span class="ukc-prog-syn__time">{{ $tt !== '' ? $tt : '-' }}</span>
                                <span class="ukc-prog-syn__label">{{ $row['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
@else
    <p style="text-align:center;color:var(--ukc-muted);padding:24px 0;">{{ __('Aucun programme renseigné pour le moment.') }}</p>
@endif
