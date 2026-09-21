{{--
    Éditeur WYSIWYG du résumé — CKEditor 5 (initialisé par app.js : initAbstractEditors).
    Variables : $name (nom du champ), $initial (HTML de départ), $min (MOTS, 0 = pas
    de minimum), $max (MOTS), $help (facultatif).
--}}
@php($wcId = 'wc-'.\Illuminate\Support\Str::random(6))
<div class="ukc-rte">
    <textarea name="{{ $name ?? 'abstract' }}" data-ckeditor
              data-min-words="{{ (int) ($min ?? 0) }}" data-max-words="{{ (int) ($max ?? 0) }}"
              data-count-target="{{ $wcId }}" class="ukc-rte__source">{!! $initial ?? '' !!}</textarea>
    <div class="ukc-rte__foot">
        <span class="hint">
            @if ($help)
                {{ $help }}
            @elseif (($min ?? 0) > 0)
                {{ __('Minimum :min mots · maximum :max mots.', ['min' => (int) $min, 'max' => (int) ($max ?? 300)]) }}
            @else
                {{ __('Maximum :max mots.', ['max' => (int) ($max ?? 300)]) }}
            @endif
        </span>
        <span class="hint ukc-rte__count"><strong id="{{ $wcId }}">0</strong> {{ __('mot(s)') }}</span>
    </div>
</div>
