{{-- Sous-navigation du module Soumissions. Source unique : elle était recopiée
     dans chaque vue, et chaque nouvel onglet obligeait à éditer tous les
     fichiers (avec le risque d'en oublier un). `$current` = clé de la page. --}}
@php $navEd = $edition_id ?? ''; @endphp
<div class="ukc-nav-docs">
    @if ($current === 'liste')
        <button type="button" class="active">Soumissions</button>
    @else
        <a href="{{ route('admin.submissions.index', ['edition_id' => $navEd]) }}">Soumissions</a>
    @endif

    @if ($current === 'formulaire')
        <button type="button" class="active">Formulaire de soumission</button>
    @else
        <a href="{{ $navEd !== '' ? route('admin.editions.form', $navEd) : route('admin.editions.index') }}">Formulaire de soumission</a>
    @endif

    @if ($current === 'attribution')
        <button type="button" class="active">Attribution auto</button>
    @else
        <a href="{{ route('admin.submissions.auto-assign', ['edition_id' => $navEd]) }}">Attribution auto</a>
    @endif

    @if ($current === 'relecteurs')
        <button type="button" class="active">Relecteurs &amp; expertise</button>
    @else
        <a href="{{ route('admin.submissions.reviewers', ['edition_id' => $navEd]) }}">Relecteurs &amp; expertise</a>
    @endif

    @if ($current === 'options')
        <button type="button" class="active">Options</button>
    @else
        <a href="{{ route('admin.submissions.options', ['edition_id' => $navEd]) }}">Options</a>
    @endif
</div>
