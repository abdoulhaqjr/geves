<x-mail::message>
# {{ __('Inscription à la formation') }}

{{ __('Bonjour') }} {{ $enrollment->user?->name }},

@if ($formation->isFree())
{{ __('Votre inscription à la formation ci-dessous est confirmée.') }}
@elseif ($enrollment->isPaid())
{{ __('Votre inscription à la formation ci-dessous est confirmée (frais réglés).') }}
@else
{{ __('Votre inscription à la formation ci-dessous est enregistrée. Réglez vos frais pour la finaliser.') }}
@endif

- **{{ __('Formation :') }}** {{ $formation->title }}@if ($formation->theme) - {{ $formation->theme }}@endif
@if ($formation->starts_at)
- **{{ __('Date :') }}** {{ $formation->starts_at->format('d/m/Y H:i') }}@if ($formation->ends_at) - {{ $formation->ends_at->format('d/m/Y H:i') }}@endif
@endif
@if ($formation->location)
- **{{ __('Lieu :') }}** {{ $formation->location }}@if ($formation->room) ({{ $formation->room }})@endif
@endif
- **{{ __('Frais :') }}** {{ $formation->isFree() ? __('Gratuite') : number_format((float) $formation->fee, 0, ',', ' ').' '.config('payments.currency', 'XOF') }}

<x-mail::button :url="route('account.formations')">
{{ __('Voir mes formations') }}
</x-mail::button>

{{ __('Le comité d’organisation') }}
</x-mail::message>
