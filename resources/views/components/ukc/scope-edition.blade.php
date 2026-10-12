@props(['event'])
{{-- Repère de périmètre : rappelle que les réglages d'une carte s'appliquent
     uniquement à l'événement sélectionné (documents rattachés à l'événement). --}}
<span class="ukc-scope-cue" title="Ces réglages s'appliquent uniquement à cet événement">
    <x-ukc.icon name="calendar" :size="12" />
    {{ __('Propre à cet événement') }} - {{ $event->title ?: $event->year }}
</span>
