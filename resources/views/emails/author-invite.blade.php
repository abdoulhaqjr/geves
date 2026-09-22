<x-mail::message>
# {{ __('On vous a associé(e) à une communication') }}

{{ __('Bonjour') }} {{ $recipientName ?: '' }},

{{ __('Vous avez été associé(e) en tant que :role à la communication « :titre » pour :edition.', ['role' => mb_strtolower($roleLabel), 'titre' => $submission->title, 'edition' => $submission->edition?->title]) }}

{{ __('Vous n’avez pas encore de compte sur la plateforme. Créez-le avec cette adresse e-mail : votre communication sera automatiquement rattachée à votre espace, et vous pourrez suivre son évaluation.') }}

<x-mail::button :url="$registerUrl">
{{ __('Créer mon compte') }}
</x-mail::button>

{{ __('Ce n’est pas vous ? Vous pouvez signaler que vous n’êtes pas concerné(e) :') }}

<x-mail::button :url="$declineUrl" color="error">
{{ __('Ce n’est pas moi') }}
</x-mail::button>

{{ __('Cordialement,') }}
{{ __('Le comité d’organisation') }}
</x-mail::message>
