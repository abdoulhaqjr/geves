<x-mail::message>
# {{ $meta['heading'] }}

Bonjour {{ $submission->correspondingAuthorName() }},

{{ $meta['intro'] }}

- **Référence :** {{ $submission->ref_code }}
- **Titre :** {{ $submission->title }}
- **Édition :** {{ $submission->edition?->title }}

@if ($note)
<x-mail::panel>
{{ $note }}
</x-mail::panel>
@endif

<x-mail::button :url="route('account.submissions.index')">
Voir mes communications
</x-mail::button>

Cordialement,
Le comité d’organisation
</x-mail::message>
