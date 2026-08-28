{{--
    Éditeur WYSIWYG du résumé (soumission de formulaire classique, sans Livewire).
    Variables : $name (nom du champ caché), $initial (HTML de départ),
    $min (caractères), $help (facultatif).
--}}
<div x-data="ukcEditor(@js($initial ?? ''))">
    <input type="hidden" name="{{ $name ?? 'abstract' }}" x-ref="hidden">
    <div class="ukc-rte-editor">
        <div class="ukc-rte-toolbar">
            <button type="button" @click="cmd('bold')" title="Gras"><strong>B</strong></button>
            <button type="button" @click="cmd('italic')" title="Italique"><em>I</em></button>
            <button type="button" @click="cmd('underline')" title="Souligné" style="text-decoration:underline;font-weight:600;">U</button>
            <button type="button" @click="cmd('insertUnorderedList')" title="Liste à puces">•</button>
            <button type="button" @click="cmd('insertOrderedList')" title="Liste numérotée" style="font-size:12px;">1.</button>
        </div>
        <div class="ukc-rte-area" contenteditable="true" x-ref="area"
             role="textbox" aria-multiline="true" aria-label="Résumé"
             data-placeholder="Contexte, objectifs, méthode, résultats attendus…"
             @input="sync()" @blur="sync()"></div>
    </div>
    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:6px;">
        <span class="hint">{{ $help ?? ('Minimum '.($min ?? 50).' caractères.') }}</span>
        <span class="hint"><span x-text="count"></span> caractère(s)</span>
    </div>
</div>
