<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    {{-- Polices lues depuis le disque (public/fonts) : dompdf n'ira chercher aucune
         feuille de styles distante, et ce PDF est celui qu'on imprime. --}}
    @include('documents._fonts', ['pdf' => true])
    <style>
        /* ⚠️ Marge de page à ZÉRO, et la marge d'impression portée par les
           coordonnées des cellules : dompdf place un élément absolu par rapport à
           la FEUILLE et ignore `@page { margin }` - l'encre se collait au bord. */
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Jost', DejaVu Sans, sans-serif; }
        /* ⚠️ Saut de page APRÈS chaque planche sauf la dernière : un saut final
           ajouterait une page blanche à la fin de chaque lot. */
        .sheet { position: relative; page-break-after: always; }
        .sheet.last { page-break-after: auto; }
        /* ⚠️⚠️ POSITIONNEMENT ABSOLU, JAMAIS DE `float` : dompdf n'ENROULE PAS les
           flottants - il les alignait tous sur une seule rangée, qui sortait de la
           feuille (un badge coupé à droite, la moitié basse de la page vide). Le
           badge lui-même est déjà dessiné en absolu, et dompdf le rend très bien. */
        .cell { position: absolute; overflow: hidden; }
    </style>
</head>
<body>
    @foreach ($sheets as $sheet)
        <div class="sheet @if ($loop->last) last @endif" style="width:{{ $sheet['pageW'] }}px;height:{{ $sheet['pageH'] - 2 }}px;">
            @foreach ($sheet['badges'] as $i => $badge)
                <div class="cell" style="left:{{ $sheet['marginPx'] + $sheet['offsetLeft'] + ($i % $sheet['cols']) * ($sheet['cellW'] + $sheet['gutter']) }}px;top:{{ $sheet['marginPx'] + $sheet['offsetTop'] + intdiv($i, $sheet['cols']) * ($sheet['cellH'] + $sheet['gutter']) }}px;width:{{ $sheet['cellW'] }}px;height:{{ $sheet['cellH'] }}px;">{!! $badge !!}</div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
