@props([
    'sort',                  // clé de colonne (doit figurer dans la liste blanche du contrôleur)
    'current' => null,        // colonne triée actuellement
    'dir' => 'asc',           // sens actuel
    'align' => null,          // 'right' | 'center' éventuel
    // Noms des paramètres d'URL. Par défaut `sort`/`dir`/`page` (une seule liste
    // sur la page). Quand DEUX tableaux triables cohabitent (ex. Programme >
    // Sessions et Créneaux), chacun passe ses propres noms (`s_sort`/`s_dir`/
    // `s_page`, `c_sort`/…) pour ne pas se marcher dessus.
    'param' => 'sort',
    'dirParam' => 'dir',
    'pageParam' => 'page',
])
@php
    $isActive = $current === $sort;
    $nextDir = $isActive && $dir === 'asc' ? 'desc' : 'asc';
    // Nouveau tri → on retire la pagination pour revenir à la page 1.
    $url = request()->fullUrlWithQuery([$param => $sort, $dirParam => $nextDir, $pageParam => null]);
@endphp
<th @if ($align) style="text-align:{{ $align }};" @endif>
    <a href="{{ $url }}" @class(['ukc-th-sort', 'is-active' => $isActive, 'is-desc' => $isActive && $dir === 'desc'])
       aria-label="{{ __('Trier par') }} {{ strip_tags($slot) }}">
        <span>{{ $slot }}</span>
        <span class="ind" aria-hidden="true"></span>
    </a>
</th>
