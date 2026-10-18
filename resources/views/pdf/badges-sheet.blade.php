<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    {{-- Polices lues depuis le disque (public/fonts) : dompdf n'ira chercher aucune
         feuille de styles distante, et ce PDF est celui qu'on imprime. --}}
    @include('documents._fonts', ['pdf' => true])
    <style>
        @page { margin: {{ $marginMm }}mm; }
        html, body { margin: 0; padding: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'Jost', DejaVu Sans, sans-serif; }
        /* ⚠️ Saut de page APRÈS chaque planche sauf la dernière : un saut final
           ajouterait une page blanche à la fin de chaque lot. */
        .sheet { page-break-after: always; }
        .sheet.last { page-break-after: auto; }
        /* Chaque badge est DESSINÉ à la taille de sa cellule (cf. BadgeSheet::scaleLayout) :
           aucun transform, que dompdf n'applique pas de façon fiable. */
        .cell { float: left; overflow: hidden; }
        .clear { clear: both; }
    </style>
</head>
<body>
    @foreach ($sheets as $sheet)
        <div class="sheet @if ($loop->last) last @endif" style="width:{{ $sheet['sheetW'] }}px;">
            @foreach ($sheet['badges'] as $i => $badge)<div class="cell" style="width:{{ $sheet['cellW'] }}px;height:{{ $sheet['cellH'] }}px;margin:0 {{ ($i + 1) % $sheet['cols'] === 0 ? 0 : $sheet['gutter'] }}px {{ $sheet['gutter'] }}px 0;">{!! $badge !!}</div>@endforeach
            <div class="clear"></div>
        </div>
    @endforeach
</body>
</html>
