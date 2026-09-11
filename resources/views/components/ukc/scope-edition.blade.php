@props(['edition'])
{{-- Repère de périmètre : rappelle que les réglages d'une carte s'appliquent
     uniquement à l'édition sélectionnée (documents rattachés à l'édition). --}}
<span class="ukc-scope-cue" title="Ces réglages s'appliquent uniquement à cette édition">
    <x-ukc.icon name="calendar" :size="12" />
    {{ __('Propre à cette édition') }} · {{ $edition->title ?: $edition->year }}
</span>
