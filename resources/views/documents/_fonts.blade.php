{{-- Polices des documents (badges, attestations), **hébergées par le site**.

     ⚠️ À inclure PARTOUT où une mise en page du concepteur est rendue. Le
     concepteur chargeait Jost et Playfair Display, mais ni l'aperçu, ni
     l'attestation délivrée, ni le PDF ne les chargeaient : la maquette
     ressortait en police système, avec d'autres métriques - du texte qui ne
     tenait plus dans sa boîte, et un document méconnaissable.

     ⚠️ Les fichiers vivent dans `public/fonts`, pas sur un CDN : **dompdf ne va
     pas chercher une feuille de styles distante**, et c'est lui qui produit le
     PDF téléchargé. Deux formats pour cette raison : **woff2** pour les
     navigateurs, **ttf** pour dompdf, qui ne lit pas le woff2.

     Avec `$pdf = true`, on n'émet que des chemins de FICHIER absolus : dompdf
     résout `url()` sur le disque (dans son `chroot`, ici la racine du projet) et
     n'a pas de serveur HTTP à interroger.

     Les graisses reprennent exactement celles du concepteur
     (`admin/documents/template-designer.blade.php`) : une graisse absente serait
     synthétisée par le moteur, ce qui change encore le rendu. --}}
@php
    $pdf = $pdf ?? false;

    // [fichier de base, famille, graisse, style]
    $faces = [
        ['jost-400', 'Jost', 400, 'normal'],
        ['jost-500', 'Jost', 500, 'normal'],
        ['jost-600', 'Jost', 600, 'normal'],
        ['jost-700', 'Jost', 700, 'normal'],
        ['jost-800', 'Jost', 800, 'normal'],
        ['jost-900', 'Jost', 900, 'normal'],
        ['jost-400-italic', 'Jost', 400, 'italic'],
        ['jost-700-italic', 'Jost', 700, 'italic'],
        ['playfair-700', 'Playfair Display', 700, 'normal'],
        ['playfair-900', 'Playfair Display', 900, 'normal'],
        ['playfair-700-italic', 'Playfair Display', 700, 'italic'],
    ];
@endphp
<style>
@foreach ($faces as [$file, $family, $weight, $style])
@font-face {
    font-family: '{{ $family }}';
    font-style: {{ $style }};
    font-weight: {{ $weight }};
    font-display: swap;
@if ($pdf)
    src: url('{{ public_path('fonts/'.$file.'.ttf') }}') format('truetype');
@else
    src: url('{{ asset('fonts/'.$file.'.woff2') }}') format('woff2'),
         url('{{ asset('fonts/'.$file.'.ttf') }}') format('truetype');
@endif
}
@endforeach
</style>
