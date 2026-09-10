@props([
    'items' => [],          // [['label'=>…, 'group'=>…, 'url'=>…, 'icon'=>…], …]
    'searchBase' => null,    // URL de repli : « …?search= » (recherche libre)
])
<div x-data="ukcPalette" @keydown.window="hotkey($event)" class="ukc-palette-root">
    {{-- Pastille déclencheur (topbar) --}}
    <button type="button" class="ukc-search-pill" @click="openPalette()" aria-label="{{ __('Recherche rapide') }}">
        <x-ukc.icon name="search" :size="16" />
        <span class="lbl">{{ __('Recherche rapide') }}</span>
        <kbd>Ctrl&nbsp;K</kbd>
    </button>

    {{-- Modale palette --}}
    <div class="ukc-palette" x-show="open" x-cloak x-transition.opacity
         @click.self="close()" @keydown.escape.window="close()" style="display:none;">
        <div class="ukc-palette__box" x-transition
             @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="go()">
            <div class="ukc-palette__search">
                <x-ukc.icon name="search" :size="18" />
                <input type="text" x-model="q" x-ref="input" @input="onInput()"
                       placeholder="{{ __('Aller à une section, chercher…') }}" autocomplete="off" spellcheck="false">
                <span class="esc">ESC</span>
            </div>
            <div class="ukc-palette__list" x-ref="list">
                @foreach ($items as $it)
                    <a href="{{ $it['url'] }}" data-item data-k="{{ \Illuminate\Support\Str::lower(($it['label'] ?? '').' '.($it['group'] ?? '')) }}"
                       class="ukc-palette__item">
                        <span class="ico"><x-ukc.icon :name="$it['icon'] ?? 'dot'" :size="18" /></span>
                        <span class="t">{{ $it['label'] }}</span>
                        @if (! empty($it['group']))<span class="grp">{{ $it['group'] }}</span>@endif
                    </a>
                @endforeach
                <div class="ukc-palette__empty" x-ref="empty" hidden>
                    <x-ukc.icon name="search" :size="28" />
                    <p>{{ __('Aucune section ne correspond.') }}</p>
                    @if ($searchBase)
                        <a x-ref="fallback" data-base="{{ $searchBase }}" href="{{ $searchBase }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                            {{ __('Rechercher « ') }}<span x-text="q"></span>{{ __(' »') }}
                        </a>
                    @endif
                </div>
            </div>
            <div class="ukc-palette__foot">
                <span><kbd>↑</kbd><kbd>↓</kbd> {{ __('naviguer') }}</span>
                <span><kbd>↵</kbd> {{ __('ouvrir') }}</span>
                <span><kbd>esc</kbd> {{ __('fermer') }}</span>
            </div>
        </div>
    </div>
</div>
