@props(['event'])
{{-- Repère de périmètre : rappelle que les réglages d'une carte s'appliquent
     uniquement à l'événement sélectionnée (documents rattachés à l'événement). --}}
<span class="ukc-scope-cue" title="Ces réglages s'appliquent uniquement à cette événement">
    <x-ukc.icon name="calendar" :size="12" />
    {{ __('Propre à cette événement') }} · {{ $event->title ?: $event->year }}
</span>
