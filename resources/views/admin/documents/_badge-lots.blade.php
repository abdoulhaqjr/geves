{{-- Liens de lot : le PDF de badges est BORNÉ (mémoire de dompdf), donc une
     grosse catégorie sort en plusieurs fichiers. Les cacher laisserait croire
     que « Imprimer » sort tout le monde. --}}
<span style="font-size:12.5px;color:var(--ukc-muted);">{{ $total }} badges, {{ $lots }} PDF de {{ \App\Documents\BadgeSheet::LOT }} au plus :</span>
@for ($l = 1; $l <= $lots; $l++)
    <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route($routeName, $params + ['lot' => $l]) }}">
        <x-ukc.icon name="download" :size="16" /> Lot {{ $l }}/{{ $lots }}
    </a>
@endfor
