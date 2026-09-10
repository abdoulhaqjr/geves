@props([
    'open' => 'false',     // expression Alpine (booléen/objet) pilotant l'ouverture
    'onClose' => null,      // expression Alpine exécutée à la fermeture (défaut : open = false)
    'title' => null,        // titre simple ; sinon utiliser le slot `header`
    'width' => '440px',
])
@php($close = $onClose ?? ($open.' = false'))
{{-- Panneau latéral coulissant (style Tailwise). Le parent fournit l'état Alpine. --}}
<div class="ukc-slideover" x-show="{{ $open }}" x-cloak style="display:none;"
     @keydown.escape.window="{{ $close }}">
    <div class="ukc-slideover__backdrop" x-show="{{ $open }}" x-transition.opacity @click="{{ $close }}"></div>
    <div class="ukc-slideover__panel" style="width:{{ $width }};" x-show="{{ $open }}"
         x-transition:enter="ukc-so-anim" x-transition:enter-start="ukc-so-off" x-transition:enter-end="ukc-so-on"
         x-transition:leave="ukc-so-anim" x-transition:leave-start="ukc-so-on" x-transition:leave-end="ukc-so-off">
        @if ($title !== null || isset($header))
            <div class="ukc-slideover__head">
                <span>{{ $header ?? $title }}</span>
                <button type="button" class="ukc-slideover__close" @click="{{ $close }}" aria-label="{{ __('Fermer') }}">&times;</button>
            </div>
        @endif
        <div class="ukc-slideover__body">{{ $slot }}</div>
        @isset($footer)
            <div class="ukc-slideover__foot">{{ $footer }}</div>
        @endisset
    </div>
</div>
