<x-mail::message>
# {{ __('Paiement reçu') }} ✅

{{ __('Bonjour') }} {{ $payment->participant?->full_name }},

{{ __('Nous confirmons la réception de votre paiement pour **:edition**.', ['event' => $payment->event?->title]) }}

- **{{ __('Reçu n° :') }}** {{ $payment->receipt_no }}
- **{{ __('Montant :') }}** {{ number_format((float) $payment->amount, 0, ',', ' ') }} {{ $payment->currency }}
- **{{ __('Date :') }}** {{ $payment->paid_at?->format('d/m/Y') }}

{{ __('Merci pour votre inscription. Vous retrouverez ce reçu dans votre espace participant.') }}

<x-mail::button :url="route('account.dashboard')">
{{ __('Accéder à mon espace') }}
</x-mail::button>

{{ __('Le comité d’organisation') }}
</x-mail::message>
