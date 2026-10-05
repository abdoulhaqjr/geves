<x-mail::message>
# {{ __('Communication acceptée') }} 🎉

{{ __('Bonjour') }} {{ $coauthorName ?: __('cher(e) co-auteur(e)') }},

{{ __('La communication « :titre » (:ref), à laquelle vous êtes associé(e) en tant que co-auteur, a été acceptée pour :edition.', ['titre' => $submission->title, 'ref' => $submission->ref_code, 'event' => $submission->event?->title]) }}

{{ __('En tant que co-auteur communiquant, vous êtes invité(e) à régler vos frais de participation, au tarif de votre grade. Le règlement vous ouvre droit à votre attestation de communication.') }}

{{ __('Le règlement se fait depuis votre espace participant, où cette communication vous est déjà rattachée - et où vous retrouverez votre reçu puis votre attestation.') }}

<x-mail::button :url="$paymentUrl">
{{ __('Régler mes frais') }}
</x-mail::button>

{{ __('Si vous n’avez pas encore de compte, le lien ci-dessus vous guide pour le créer avec cette même adresse e-mail : c’est elle qui rattache vos communications.') }}

{{ __('Au plaisir de vous accueillir,') }}
{{ __('Le comité d’organisation') }}
</x-mail::message>
