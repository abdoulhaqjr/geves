@props([
    // Expressions Alpine du composant hôte (par défaut : l'API de `tableFilters`).
    'page' => 'page',
    'pages' => 'pageCount',
    'prev' => 'prev()',
    'next' => 'next()',
])
{{-- Pagination côté client : n'apparaît que s'il y a plus d'une page. --}}
<div class="ukc-pager" x-show="({{ $pages }}) > 1" x-cloak>
    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" :disabled="({{ $page }}) <= 1" @click="{{ $prev }}">‹ Précédent</button>
    <span class="ukc-pager__info">Page <span x-text="Math.min({{ $page }}, {{ $pages }})"></span> / <span x-text="{{ $pages }}"></span></span>
    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" :disabled="({{ $page }}) >= ({{ $pages }})" @click="{{ $next }}">Suivant ›</button>
</div>
