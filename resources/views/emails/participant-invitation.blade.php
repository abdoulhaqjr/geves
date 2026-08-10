<x-mail::message>
# {{ $meta['heading'] }}

Bonjour {{ $participant->full_name }},

{{ $meta['intro'] }}

@if ($participant->edition?->date_start)
- **Dates :** du {{ $participant->edition->date_start->format('d/m/Y') }}@if ($participant->edition->date_end) au {{ $participant->edition->date_end->format('d/m/Y') }}@endif
@endif
@if ($participant->edition?->location)
- **Lieu :** {{ $participant->edition->location }}
@endif

Retrouvez toutes les informations et vos documents dans votre espace participant.

<x-mail::button :url="route('account.dashboard')">
Accéder à mon espace
</x-mail::button>

Au plaisir de vous accueillir,
Le comité d’organisation
</x-mail::message>
