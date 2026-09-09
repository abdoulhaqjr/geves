<x-mail::message>
# {{ __('Communication acceptée') }} 🎉

{{ __('Bonjour') }} {{ $coauthorName ?: __('cher(e) co-auteur(e)') }},

{{ __('La communication « :titre » (:ref), à laquelle vous êtes associé(e) en tant que co-auteur, a été acceptée pour :edition.', ['titre' => $submission->title, 'ref' => $submission->ref_code, 'edition' => $submission->edition?->title]) }}

{{ __('En tant que co-auteur communiquant, vous êtes invité(e) à régler vos frais de participation, au tarif de votre grade. Le règlement vous ouvre droit à votre attestation de communication.') }}

<x-mail::button :url="$paymentUrl">
{{ __('Régler mes frais de co-auteur') }}
</x-mail::button>

{{ __('Sur la page, vérifiez votre grade (il détermine le montant) puis choisissez votre moyen de paiement.') }}

{{ __('Au plaisir de vous accueillir,') }}
{{ __('Le comité d’organisation') }}
</x-mail::message>
