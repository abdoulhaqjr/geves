{{--
    Affichage du PROGRAMME SYNTHÉTIQUE saisi dans l'onglet « Programme » du module
    (edition.settings['program_synthesis']). Partagé par la page publique du
    colloque (#programme) ET l'aperçu de l'onglet admin - afin que l'admin voie
    EXACTEMENT ce qui est publié.

    Présentation en ONGLETS JOUR PAR JOUR (charte UK : .ukc-tabs-scroll +
    .ukc-program-day, non scopées → rendent aussi sous .ukc-shell).

    ⚠️ Un onglet = UN JOUR, pas un bloc de saisie. Les blocs d'une même date
    (« Matin », « Après-midi »…) se rangent DANS l'onglet de leur jour : un onglet
    par bloc donnait quatre pastilles « Matin » côte à côte, et le visiteur qui
    cherchait le 3 octobre ne savait pas laquelle ouvrir.

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

    $synBlocs = collect($synthesis['days'] ?? [])->map(function ($d) {
        $rows = collect($d['rows'] ?? [])
            ->filter(fn ($r) => trim((string) ($r['label'] ?? '')) !== '' || trim((string) ($r['start'] ?? '')) !== '' || trim((string) ($r['time'] ?? '')) !== '')
            ->values();

        return ['date' => trim((string) ($d['date'] ?? '')), 'heading' => trim((string) ($d['heading'] ?? '')), 'rows' => $rows];
    })->filter(fn ($d) => $d['heading'] !== '' || $d['date'] !== '' || $d['rows']->isNotEmpty())->values();

    // Regroupement PAR DATE, dans l'ordre où l'admin a rangé ses blocs - jamais
    // trié : c'est lui qui décide de la succession, flèches à l'appui.
    // ⚠️ Un bloc SANS date garde son propre onglet : rien ne dit à quel jour il
    // appartient, et le coller au précédent inventerait une information.
    $synJours = [];
    foreach ($synBlocs as $i => $bloc) {
        $cle = $bloc['date'] !== '' ? 'd:'.$bloc['date'] : 'x:'.$i;
        $synJours[$cle] ??= ['date' => $bloc['date'], 'blocs' => []];
        $synJours[$cle]['blocs'][] = ['heading' => $bloc['heading'], 'rows' => $bloc['rows']];
    }
    $synJours = collect(array_values($synJours))->map(function (array $jour) {
        $jour['compte'] = collect($jour['blocs'])->sum(fn (array $b) => $b['rows']->count());

        return $jour;
    });
@endphp
@if ($synJours->isNotEmpty())
    <div x-data="{ progDay: 0 }" class="ukc-prog-syn">
        <div class="ukc-tabs-scroll" role="tablist">
            @foreach ($synJours as $i => $jour)
                @php
                    $date = $jour['date'] !== '' ? \Illuminate\Support\Carbon::parse($jour['date']) : null;
                    // Sans date, on montre l'intitulé du bloc : c'est tout ce qu'on sait de lui.
                    $principal = $date?->translatedFormat('D d M') ?: ($jour['blocs'][0]['heading'] ?: __('Jour :n', ['n' => $i + 1]));
                @endphp
                <button type="button" class="ukc-tab ukc-tab--day" :class="{ active: progDay==={{ $i }} }"
                        @click="progDay={{ $i }}" role="tab" :aria-selected="progDay==={{ $i }} ? 'true' : 'false'">
                    <span class="j">{{ $date ? __('Jour :n', ['n' => $i + 1]) : __('Bloc') }}</span>
                    <span class="d">{{ $principal }}</span>
                </button>
            @endforeach
        </div>

        @foreach ($synJours as $i => $jour)
            <div class="ukc-program-day" :class="{ active: progDay==={{ $i }} }">
                @if ($jour['date'] !== '')
                    <div class="ukc-prog-syn__head">
                        <span class="ukc-prog-syn__date">{{ \Illuminate\Support\Carbon::parse($jour['date'])->translatedFormat('l d F Y') }}</span>
                        @if ($jour['compte'] > 0)
                            <span class="ukc-prog-syn__count">{{ trans_choice('{1}:count moment|[2,*]:count moments', $jour['compte']) }}</span>
                        @endif
                    </div>
                @endif

                @foreach ($jour['blocs'] as $bloc)
                    {{-- L'intitulé du bloc devient un intertitre : il situe les heures qui
                         suivent sans réclamer un onglet à lui. --}}
                    @if ($bloc['heading'] !== '')
                        <div class="ukc-prog-syn__bloc">{{ $bloc['heading'] }}</div>
                    @endif
                    @if ($bloc['rows']->isNotEmpty())
                        <ul class="ukc-prog-syn__rows">
                            @foreach ($bloc['rows'] as $row)
                                @php $tt = $timeText($row); $type = (string) ($row['type'] ?? ''); @endphp
                                <li @class([
                                    'ukc-prog-syn__row',
                                    'is-strong' => ! empty($row['highlight']),
                                    'is-break' => $type === 'break',
                                    'is-plenary' => $type === 'plenary',
                                    'is-atelier' => $type === 'atelier',
                                ])>
                                    <span class="ukc-prog-syn__time">{{ $tt !== '' ? $tt : '-' }}</span>
                                    <span class="ukc-prog-syn__label">{{ $row['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
@else
    <p style="text-align:center;color:var(--ukc-muted);padding:24px 0;">{{ __('Aucun programme renseigné pour le moment.') }}</p>
@endif
