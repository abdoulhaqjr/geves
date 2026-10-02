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

     Les fontes sont listées une seule fois, dans `App\Documents\DocumentFonts`. --}}
@php
    $pdf = $pdf ?? false;
    $faces = App\Documents\DocumentFonts::FACES;
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
@if ($pdf)
{{-- ⚠️ **dompdf ne garde que quatre variantes par famille** (normale, grasse,
     italique, grasse italique) : nos six graisses de Jost s'y écrasaient deux à
     deux, et un texte dessiné en 500 ressortait en 400, un titre en 700 en 900.
     Une famille par graisse, que dompdf n'a plus à départager. --}}
@foreach ($faces as [$file, $family, $weight, $style])
@font-face {
    font-family: '{{ App\Documents\DocumentFonts::pdfFamilyName($family, $weight, $style) }}';
    font-style: normal;
    font-weight: normal;
    src: url('{{ public_path('fonts/'.$file.'.ttf') }}') format('truetype');
}
@endforeach
@endif
</style>
