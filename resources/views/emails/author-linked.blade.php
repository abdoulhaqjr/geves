<x-mail::message>
# {{ __('Vous êtes associé(e) à une communication') }}

{{ __('Bonjour') }} {{ $recipientName ?: '' }},

{{ __('Vous avez été associé(e) en tant que :role à la communication « :titre » (:ref) pour :edition.', ['role' => mb_strtolower($roleLabel), 'titre' => $submission->title, 'ref' => $submission->ref_code, 'event' => $submission->event?->title]) }}

{{ __('Elle apparaît désormais dans votre espace, dans « Mes communications ».') }}

<x-mail::button :url="$viewUrl">
{{ __('Voir la communication') }}
</x-mail::button>

{{ __('Ce n’est pas vous ? Vous pouvez retirer cette association :') }}

<x-mail::button :url="$declineUrl" color="error">
{{ __('Décliner l’association') }}
</x-mail::button>

{{ __('Cordialement,') }}
{{ __('Le comité d’organisation') }}
</x-mail::message>
