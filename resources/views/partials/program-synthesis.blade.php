{{--
    Affichage du PROGRAMME SYNTHÉTIQUE saisi dans l'onglet « Programme » du module
    (edition.settings['program_synthesis']). Partagé par la page publique du
    colloque (#programme) ET l'aperçu de l'onglet admin - afin que l'admin voie
    EXACTEMENT ce qui est publié.

    Donnée attendue : $synthesis = ['title' => string, 'days' => [
        ['heading' => string, 'rows' => [['time' => string, 'label' => string, 'highlight' => bool], …]],
    ]]. Le TITRE est rendu par l'appelant (en-tête de section / champ) ; ici, le corps.

    Charte UK (classes de public.css, non scopées → rendent aussi sous .ukc-shell).
--}}
@php
    $synDays = collect($synthesis['days'] ?? [])->map(function ($d) {
        $rows = collect($d['rows'] ?? [])->filter(fn ($r) => trim((string) ($r['time'] ?? '')) !== '' || trim((string) ($r['label'] ?? '')) !== '')->values();

        return ['heading' => trim((string) ($d['heading'] ?? '')), 'rows' => $rows];
    })->filter(fn ($d) => $d['heading'] !== '' || $d['rows']->isNotEmpty())->values();
@endphp
@if ($synDays->isNotEmpty())
    <div class="ukc-prog-syn">
        @foreach ($synDays as $day)
            <div class="ukc-prog-syn__day">
                @if ($day['heading'] !== '')
                    <h3 class="ukc-prog-syn__title">{{ $day['heading'] }}</h3>
                @endif
                @if ($day['rows']->isNotEmpty())
                    <ul class="ukc-prog-syn__rows">
                        @foreach ($day['rows'] as $row)
                            <li @class(['ukc-prog-syn__row', 'is-strong' => ! empty($row['highlight'])])>
                                @if (trim((string) ($row['time'] ?? '')) !== '')
                                    <span class="ukc-prog-syn__time">{{ $row['time'] }}</span>
                                @endif
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
