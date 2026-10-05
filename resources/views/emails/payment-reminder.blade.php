<x-mail::message>
# {{ __('Frais d’inscription à régler') }}

{{ __('Bonjour') }} {{ $participant->full_name }},

{!! __('Votre communication a été **acceptée** pour « :edition ». Pour finaliser votre participation, il reste à régler vos frais d’inscription.', ['event' => $event->title ?: (string) $event->year]) !!}

@if ($amount)
{{ __('Montant applicable :') }} **{{ number_format($amount, 0, ',', ' ') }} FCFA**
@endif

<x-mail::button :url="$payUrl">
{{ __('Régler mes frais') }}
</x-mail::button>

{{ __('Si vous avez déjà réglé, vous pouvez ignorer ce message.') }}

{{ __('Le secrétariat du colloque') }}
</x-mail::message>
