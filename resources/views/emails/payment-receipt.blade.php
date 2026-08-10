<x-mail::message>
# Paiement reçu ✅

Bonjour {{ $payment->participant?->full_name }},

Nous confirmons la réception de votre paiement pour **{{ $payment->edition?->title }}**.

- **Reçu n° :** {{ $payment->receipt_no }}
- **Montant :** {{ number_format((float) $payment->amount, 0, ',', ' ') }} {{ $payment->currency }}
- **Date :** {{ $payment->paid_at?->format('d/m/Y') }}

Merci pour votre inscription. Vous retrouverez ce reçu dans votre espace participant.

<x-mail::button :url="route('account.dashboard')">
Accéder à mon espace
</x-mail::button>

Le comité d’organisation
</x-mail::message>
