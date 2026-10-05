<x-mail::message>
# {{ $meta['heading'] }}

{{ __('Bonjour') }} {{ $participant->full_name }},

{{ $meta['intro'] }}

@if ($participant->event?->date_start)
- **{{ __('Dates :') }}** {{ __('du') }} {{ $participant->event->date_start->format('d/m/Y') }}@if ($participant->event->date_end) {{ __('au') }} {{ $participant->event->date_end->format('d/m/Y') }}@endif
@endif
@if ($participant->event?->location)
- **{{ __('Lieu :') }}** {{ $participant->event->location }}
@endif

{{ __('Retrouvez toutes les informations et vos documents dans votre espace participant.') }}

<x-mail::button :url="route('account.dashboard')">
{{ __('Accéder à mon espace') }}
</x-mail::button>

{{ __('Au plaisir de vous accueillir,') }}
{{ __('Le comité d’organisation') }}
</x-mail::message>
