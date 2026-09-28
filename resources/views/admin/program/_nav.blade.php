{{-- Sous-navigation du module Programme. Source unique : les onglets vivaient en
     double, sur deux pages, en Alpine (donc non partageables par lien). Ils sont
     désormais pilotés par l'URL (`?tab=`), comme le module Soumissions.
     `$current` = clé de l'onglet actif. --}}
@php $navEd = $edition_id ?? ''; @endphp
<div class="ukc-nav-docs">
    @foreach ([
        'planning' => 'Planning',
        'ateliers' => 'Ateliers',
        'sessions' => 'Sessions',
        'creneaux' => 'Créneaux',
        'espaces' => 'Espaces',
        'auto' => 'Affectation auto',
        'options' => 'Options',
    ] as $key => $label)
        @if ($current === $key)
            <button type="button" class="active">
                {{ $label }}
                @if ($key === 'creneaux' && ($stats['toPlan'] ?? 0) > 0)<span class="nav-count">{{ $stats['toPlan'] }}</span>@endif
            </button>
        @else
            <a href="{{ route('admin.program.index', array_filter(['edition_id' => $navEd, 'tab' => $key])) }}">
                {{ $label }}
                @if ($key === 'creneaux' && ($stats['toPlan'] ?? 0) > 0)<span class="nav-count">{{ $stats['toPlan'] }}</span>@endif
            </a>
        @endif
    @endforeach
</div>
