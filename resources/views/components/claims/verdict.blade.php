@props(['verdict' => 'procedure'])

{{-- La pastille d'issue d'une analyse. Une seule définition : elle est rendue
     en tête de l'analyse dépliée ET dans la ligne repliée de chaque analyse
     précédente, et deux copies finiraient par ne plus dire la même chose. --}}
@if ($verdict === 'resolvable')
    <span class="ukc-pill ukc-pill--ok">L'IA propose une résolution</span>
@elseif ($verdict === 'unresolvable')
    <span class="ukc-pill ukc-pill--err">Irrésolvable en l'état</span>
@else
    <span class="ukc-pill ukc-pill--warn">Procédure à suivre</span>
@endif
