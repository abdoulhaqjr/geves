<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    {{-- Polices lues depuis le disque (public/fonts) : dompdf n'ira chercher
         aucune feuille de styles distante, et ce PDF est celui qu'on imprime. --}}
    @include('documents._fonts', ['pdf' => true])
    <style>
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'Jost', DejaVu Sans, sans-serif; }
        /* ⚠️ Saut de page APRÈS chaque attestation sauf la dernière : un saut
           final ajouterait une page blanche à la fin de chaque lot - et on
           imprime ces lots par centaines. */
        .att { page-break-after: always; }
        .att:last-child { page-break-after: auto; }
    </style>
</head>
<body>
    @foreach ($pages as $canvas)
        <div class="att">{!! $canvas !!}</div>
    @endforeach
</body>
</html>
