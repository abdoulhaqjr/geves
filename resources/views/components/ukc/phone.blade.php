@props([
    'name' => 'phone',
    'value' => '',
    'id' => null,
    'required' => false,
    'placeholder' => '',
    'autocomplete' => 'tel',
])
{{-- Champ téléphone international (intl-tel-input) : drapeau + indicatif pays,
     saisie nationale, valeur normalisée en E.164 à la soumission (cf. app.js). --}}
<input type="tel" name="{{ $name }}" id="{{ $id ?? $name }}" value="{{ $value }}"
       data-ukc-tel autocomplete="{{ $autocomplete }}" placeholder="{{ $placeholder }}"
       @if ($required) required @endif
       {{ $attributes->merge(['class' => 'ukc-tel']) }}>
