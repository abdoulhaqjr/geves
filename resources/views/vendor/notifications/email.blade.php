<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# {{ __('Oups !') }}
@else
# {{ __('Bonjour,') }}
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
{{ __('Cordialement,') }}<br>
{{ config('app.name') }}
@endif

{{-- Subcopy : texte de repli localisé (FR par défaut, EN selon le compte).
     ⚠️ Laravel le posait en anglais (clé @lang anglaise, non traduite en FR). --}}
@isset($actionText)
<x-slot:subcopy>
{{ __('Si vous n’arrivez pas à cliquer sur le bouton « :actionText », copiez-collez l’adresse ci-dessous dans votre navigateur :', ['actionText' => $actionText]) }} <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
