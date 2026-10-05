<x-mail::message>
# {{ $meta['heading'] }}

{{ __('Bonjour') }} {{ $submission->correspondingAuthorName() }},

{{ $meta['intro'] }}

- **{{ __('Référence :') }}** {{ $submission->ref_code }}
- **{{ __('Titre :') }}** {{ $submission->title }}
- **{{ __('Événement :') }}** {{ $submission->event?->title }}

@if ($note)
<x-mail::panel>
{{ $note }}
</x-mail::panel>
@endif

<x-mail::button :url="route('account.submissions.index')">
{{ __('Voir mes communications') }}
</x-mail::button>

{{ __('Cordialement,') }}
{{ __('Le comité d’organisation') }}
</x-mail::message>
