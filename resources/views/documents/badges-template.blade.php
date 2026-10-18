<x-layouts.app :title="'Badges - ' . $event->title">
    @php
        // ⚠️ Impression : on tuile AUTANT de badges DE LA DIMENSION CHOISIE que l'A4
        // peut en contenir, avec des GOUTTIÈRES entre les badges et une marge autour de
        // la feuille (comme le modèle .docx de référence), sans déformer le badge.
        //
        // ⚠️ Pourquoi des gouttières imposent de RÉDUIRE le badge : un A6 (105×148 mm)
        // est EXACTEMENT le quart d'une A4 (210×297 mm), donc 2×2 A6 remplissent la
        // feuille bord à bord - zéro place pour un espace. Le modèle de référence a des
        // badges plus petits que l'A6 plein, d'où ses marges. On réserve donc marge +
        // gouttière et on met le badge à l'échelle pour qu'il tienne dans ce qui reste.
        //
        // Les px CSS s'impriment à 96 dpi : un badge de L px fait L/96 pouce.
        $w = (int) ($width ?? 397);
        $h = (int) ($height ?? 560);
        $pageW = 793.7;   // 210 mm @ 96 dpi
        $pageH = 1122.52; // 297 mm @ 96 dpi
        $mm = 96 / 25.4;              // px par mm à 96 dpi
        $margin = 6 * $mm;           // marge autour de la feuille (6 mm)
        $gutter = 5 * $mm;           // espace entre badges (5 mm)
        // Nombre de colonnes/lignes : combien de badges de taille native tiennent,
        // avec une petite tolérance (l'A6 fait 105,03 mm, soit 2×210,06 mm, à peine
        // au-delà de l'A4 : sans tolérance il ne passerait qu'à une colonne). Le
        // rétrécissement par la suite rend la place aux gouttières.
        $tol = 1.012;
        $cols = max(1, (int) floor($pageW * $tol / $w));
        $rows = max(1, (int) floor($pageH * $tol / $h));
        // Zone imprimable (hors marge) moins les gouttières -> échelle qui fait tenir
        // badges + gouttières + marge sans jamais rogner. Uniforme (pas de déformation).
        $availW = $pageW - 2 * $margin;
        $availH = $pageH - 2 * $margin;
        $scaleW = ($availW - ($cols - 1) * $gutter) / ($cols * $w);
        $scaleH = ($availH - ($rows - 1) * $gutter) / ($rows * $h);
        $scale = round(min($scaleW, $scaleH, 1.0) * 0.998, 4); // marge anti-rognage
        $cellW = (int) floor($w * $scale);
        $cellH = (int) floor($h * $scale);
        $sheetW = $cols * $cellW + ($cols - 1) * $gutter;
        $marginMm = 6;
        $gutterPx = (int) round($gutter);
        $perPage = $cols * $rows;
    @endphp

    <div class="badge-page" style="max-width:900px;margin:0 auto;padding:24px 16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;" class="no-print">
            <p class="ukc-muted" style="margin:0;font-size:13px;">
                {{ $badgeType }} - {{ count($badges) }} badge(s) - {{ $cols }}&times;{{ $rows }} = {{ $perPage }} par page A4
            </p>
            <button onclick="window.print()" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Imprimer / PDF
            </button>
        </div>

        <div class="badge-sheet">
            @forelse ($badges as $badge)
                <div class="badge-cell">{!! $badge !!}</div>
            @empty
                <div class="ukc-empty" style="width:100%;"><h3>Aucun destinataire pour ce badge</h3></div>
            @endforelse
        </div>
    </div>

    @push('head')
        @include('documents._fonts')
        <style>
            /* Écran : galerie souple, chaque badge à sa taille native. */
            .badge-sheet { display:flex; flex-wrap:wrap; gap:14px; }
            .badge-cell { border:1px solid var(--ukc-border); border-radius:8px; overflow:hidden; break-inside:avoid; }

            @media print {
                .no-print { display:none !important; }
                html, body { background:#fff !important; }
                .badge-page { max-width:none !important; margin:0 !important; padding:0 !important; }
                /* Marge autour de la feuille (les badges ne touchent plus le bord). */
                @page { size: A4 portrait; margin: {{ $marginMm }}mm; }

                /* Tuilage : N colonnes de cellules exactement dimensionnées, centrées,
                   séparées par une gouttière ; le badge est réduit par transform pour que
                   {{ $cols }} badges + leurs gouttières tiennent en largeur. */
                .badge-sheet {
                    gap:{{ $gutterPx }}px;
                    width:{{ (int) round($sheetW) }}px;
                    margin:0 auto;
                    justify-content:flex-start;
                }
                .badge-cell {
                    border:0 !important; border-radius:0 !important;
                    width:{{ $cellW }}px; height:{{ $cellH }}px;
                    overflow:hidden; break-inside:avoid;
                    outline:0.2mm dashed #c8c8c8; outline-offset:-0.1mm; /* repère de découpe */
                }
                .badge-cell .ukc-doc-canvas {
                    transform: scale({{ $scale }});
                    transform-origin: top left;
                }
            }
        </style>
    @endpush
</x-layouts.app>
