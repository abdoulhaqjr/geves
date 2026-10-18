{{-- Une planche de badges d'UN format, tuilée sur A4 avec gouttières.
     Partagée par la planche d'une catégorie (badges-template) et la planche de
     tous les badges prêts (badges-all). Variables attendues :
       $badges  collection d'HTML de badges (tous de MÊME format)
       $width/$height  dimensions natives du badge (px)
       $gi      index du groupe (portée des règles d'impression ; défaut 0)
       $caption légende au-dessus de la planche (ex. « Participant »)
       $break   saut de page avant ce groupe (groupes suivants)

     ⚠️ Pourquoi des gouttières imposent de RÉDUIRE le badge : un A6 (105×148 mm)
     est EXACTEMENT le quart d'une A4 (210×297 mm), donc 2×2 A6 remplissent la
     feuille bord à bord - zéro place pour un espace. On réserve marge + gouttière
     et on met le badge à l'échelle pour qu'il tienne dans ce qui reste.
     ⚠️ Ne pas revenir à gap:0 / margin:0 : le modèle .docx de référence veut l'espace. --}}
@php
    $w = (int) ($width ?? 397);
    $h = (int) ($height ?? 560);
    $pageW = 793.7;   // 210 mm @ 96 dpi
    $pageH = 1122.52; // 297 mm @ 96 dpi
    $mm = 96 / 25.4;              // px par mm à 96 dpi
    $margin = 6 * $mm;           // marge autour de la feuille (6 mm)
    $gutter = 5 * $mm;           // espace entre badges (5 mm)
    // Tolérance 1,2 % : l'A6 fait 105,03 mm, soit 2×210,06 mm, à peine au-delà de
    // l'A4 ; sans elle il ne passerait qu'à une colonne. Le rétrécissement rend
    // ensuite la place aux gouttières.
    $tol = 1.012;
    $cols = max(1, (int) floor($pageW * $tol / $w));
    $rows = max(1, (int) floor($pageH * $tol / $h));
    $availW = $pageW - 2 * $margin;
    $availH = $pageH - 2 * $margin;
    $scaleW = ($availW - ($cols - 1) * $gutter) / ($cols * $w);
    $scaleH = ($availH - ($rows - 1) * $gutter) / ($rows * $h);
    $scale = round(min($scaleW, $scaleH, 1.0) * 0.998, 4); // marge anti-rognage
    $cellW = (int) floor($w * $scale);
    $cellH = (int) floor($h * $scale);
    $sheetW = $cols * $cellW + ($cols - 1) * $gutter;
    $gutterPx = (int) round($gutter);
    $perPage = $cols * $rows;
    $gi = $gi ?? 0;
    $caption = $caption ?? null;
    $break = $break ?? false;
@endphp

<div class="badge-group badge-group--g{{ $gi }}">
    @if ($caption)
        <p class="ukc-muted badge-sheet-cap" style="margin:18px 0 10px;font-size:13px;">
            {{ $caption }} - {{ $badges->count() }} badge(s) - {{ $cols }}&times;{{ $rows }} = {{ $perPage }} par page A4
        </p>
    @endif

    <div class="badge-sheet badge-sheet--g{{ $gi }}">
        @forelse ($badges as $badge)
            <div class="badge-cell">{!! $badge !!}</div>
        @empty
            <div class="ukc-empty" style="width:100%;"><h3>Aucun destinataire pour ce badge</h3></div>
        @endforelse
    </div>
</div>

<style>
    @media print {
        @page { size: A4 portrait; margin: 6mm; }
        @if ($break) .badge-group--g{{ $gi }} { break-before:page; } @endif
        .badge-sheet--g{{ $gi }} {
            gap:{{ $gutterPx }}px;
            width:{{ (int) round($sheetW) }}px;
            margin:0 auto;
            justify-content:flex-start;
        }
        .badge-sheet--g{{ $gi }} .badge-cell {
            border:0 !important; border-radius:0 !important;
            width:{{ $cellW }}px; height:{{ $cellH }}px;
            overflow:hidden; break-inside:avoid;
            outline:0.2mm dashed #c8c8c8; outline-offset:-0.1mm; /* repère de découpe */
        }
        .badge-sheet--g{{ $gi }} .ukc-doc-canvas {
            transform: scale({{ $scale }});
            transform-origin: top left;
        }
    }
</style>
