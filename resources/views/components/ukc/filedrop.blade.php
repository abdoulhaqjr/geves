@props([
    'name',                 // name de l'input fichier (soumis avec le formulaire)
    'id' => null,
    'accept' => null,
    'required' => false,
    'hint' => null,          // ex. « PDF/DOC/DOCX, max 8 Mo »
])
@php($id = $id ?? $name)
{{-- Zone de dépôt glisser-déposer (style Tailwise). L'input natif reste la
     source → le fichier part avec le formulaire (envoi synchrone classique).
     Aperçu d'image affiché quand le fichier choisi est une image. --}}
<div class="ukc-filedrop" x-data="ukcFiledrop" :class="{ 'is-over': over, 'has-file': file }"
     @dragover.prevent="over = true" @dragleave.prevent="over = false" @drop.prevent="drop($event)"
     @click="$refs.input.click()" role="button" tabindex="0" @keydown.enter="$refs.input.click()">
    <input type="file" x-ref="input" id="{{ $id }}" name="{{ $name }}"
           @if ($accept) accept="{{ $accept }}" @endif @if ($required) required @endif
           @change="pick($event)" class="ukc-filedrop__input">
    <div class="ukc-filedrop__prompt" x-show="!file">
        <x-ukc.icon name="upload" :size="26" />
        <strong>{{ __('Glissez un fichier ici') }}</strong>
        <span>{{ __('ou cliquez pour parcourir') }}@if ($hint) - {{ $hint }}@endif</span>
    </div>
    {{-- Aperçu image --}}
    <div class="ukc-filedrop__preview" x-show="file && previewUrl" x-cloak style="display:none;">
        <img :src="previewUrl" alt="" style="max-height:130px;max-width:100%;border-radius:8px;">
        <div class="meta"><strong x-text="file?.name"></strong><span x-text="size"></span></div>
        <button type="button" class="rm" @click.stop="clear()" aria-label="{{ __('Retirer') }}">&times;</button>
    </div>
    {{-- Fichier non-image (repli) --}}
    <div class="ukc-filedrop__file" x-show="file && !previewUrl" x-cloak>
        <x-ukc.icon name="file" :size="22" />
        <div class="meta"><strong x-text="file?.name"></strong><span x-text="size"></span></div>
        <button type="button" class="rm" @click.stop="clear()" aria-label="{{ __('Retirer') }}">&times;</button>
    </div>
</div>
